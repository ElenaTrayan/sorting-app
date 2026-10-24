<?php

namespace App\Http\Controllers\User;

use App\Components\ColorMatch;
use App\Components\HashtagSuggester;
use App\Components\ImagePalette;
use App\Components\ImageReader;
use App\Components\PostTrash;
use App\Components\TextGroupExport;
use App\Http\Controllers\User\Packages\InstagramParser;
use App\Http\Controllers\Controller;
use App\Http\Controllers\User\Packages\UploadImageController;
use App\Models\Hashtag;
use App\Models\Post;
use App\Models\PostClothes;
use App\Models\PostFilm;
use App\Models\PostsCategory;
use App\Models\UserAiSetting;
use App\Models\WardrobeBrand;
use App\Models\WardrobeColor;
use App\Models\WardrobePrint;
use App\Parsers\BaseParser;
use App\Parsers\EdaRuParser;
use App\Parsers\HdFilmixFunParser;
use App\Parsers\InstagramComParser;
use App\Parsers\PinterestParser;
use App\Parsers\UniversalFilmParser;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Contracts\View\Factory;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use function PHPUnit\Framework\isJson;

class PostController extends Controller
{
    // поле "Дата" заполнено, но это не дата (Post::parseEventDate())
    private const EVENT_DATE_ERROR = 'Не понял дату — введите её как 12.03.2024, 03.2024, «март 2024» или 2024';

    /**
     * Display a listing of the resource.
     *
     * @return Application|Factory|View
     */
    public function index(Request $request)
    {
        //$posts = Post::orderBy('title', 'asc')->get();

        $withoutHashtags = $request->boolean('without_hashtags');
        // фильтр по отметке "Использовано" (posts.is_used): used=no — ещё не использованные, used=yes — использованные
        $activeUsed = in_array($request->get('used'), ['no', 'yes'], true) ? $request->get('used') : null;
        $activeType = in_array($request->get('type'), ['photo', 'video', 'carousel'], true) ? $request->get('type') : 'all';
        // 'date' — по дате пина (event_date, новые даты выше), посты без даты — в конце
        $activeSort = in_array($request->get('sort'), ['old', 'title', 'date'], true) ? $request->get('sort') : 'new';
        // тип поста (колонка posts.type, см. Post::TYPE_LABELS) — отдельный фильтр, не путать с
        // типом медиа выше (вкладки "Фото/Видео/Карусель", параметр type); null — все типы
        $activePostType = array_key_exists((string) $request->get('post_type'), Post::TYPE_LABELS) ? $request->get('post_type') : null;
        // снята галочка "Свернуть группы" (cookie, см. Post::feedGroupsExpanded): посты групп идут в ленте
        // отдельными карточками, а не одной карточкой-стопкой; посты одной группы всё равно стоят рядом (сортировка ниже)
        $ungrouped = Post::feedGroupsExpanded();

        // у типа "Одежда" — ещё и фильтры по данным вещи (post_clothes): цвет, бренд, принт — id из справочников
        // гардероба, параметры clothes_color / clothes_brand / clothes_print (просто color занят поиском по палитре).
        // В списках — только то, что встречается в пинах пользователя; null — фильтров нет (другой тип поста)
        $clothesFilterOptions = null;
        $activeClothes = [];
        $clothesWhere = [];
        if ($activePostType === Post::TYPE_CLOTHES) {
            $usedClothes = PostClothes::whereHas('post', function($q) {
                $q->where('user_id', auth()->id())->where('type', Post::TYPE_CLOTHES);
            })->get(['color_id', 'brand_id', 'print_id']);

            $clothesFilterOptions = [
                'color' => WardrobeColor::whereIn('id', $usedClothes->pluck('color_id')->filter()->unique())->orderBy('name')->get(),
                'brand' => WardrobeBrand::whereIn('id', $usedClothes->pluck('brand_id')->filter()->unique())->orderBy('name')->get(),
                'print' => WardrobePrint::whereIn('id', $usedClothes->pluck('print_id')->filter()->unique())->orderBy('name')->get(),
            ];

            foreach ($clothesFilterOptions as $key => $options) {
                // выбранное значение — только из списка: чужой или устаревший id в адресе фильтром не считается
                $activeClothes[$key] = $options->firstWhere('id', (int) $request->get('clothes_' . $key));
                if ($activeClothes[$key]) {
                    $clothesWhere[$key . '_id'] = $activeClothes[$key]->id;
                }
            }
        }

        $perPage = 50;
        $page = max((int) $request->get('page', 1), 1);

        $withRelations = [
            'category' => function($q) {
                $q->select(['id', 'alias', 'parent_id', 'user_id', 'status']);
            },
            'category.parent' => function($q) {
                $q->select(['id', 'alias', 'parent_id', 'user_id', 'status']);
            },
            'category.parent.parent' => function($q) {
                $q->select(['id', 'alias', 'parent_id', 'user_id', 'status']);
            },
            'hashtags' => function($q) {
                $q->select(['id', 'title', 'parent_id', 'user_id']);
            },
            // сводка фильма на карточке (parts/film_summary.blade.php)
            'film' => function($q) {
                $q->select(['id', 'post_id', 'kind', 'year', 'duration', 'imdb_rating', 'my_rating', 'status', 'genres', 'vibes']);
            },
            // пин "Одежда" уже в гардеробе — в меню карточки "В гардеробе" вместо "В гардероб"
            'wardrobeItems' => function($q) {
                $q->select(['id', 'post_id']);
            },
            // пост прикреплён к фильму (кадр/промо/видео) — чип с названием фильма на карточке
            'filmPost' => function($q) {
                $q->select(['id', 'title']);
            },
        ];

        // лёгкая выборка (без eager loads) — только для подсчёта постов по типу медиа
        // (вкладки "Фото/Видео/Карусель" в шапке). Тип медиа не хранится отдельной
        // колонкой, определяется по форме JSON в *_image (как и cover_image ниже),
        // поэтому посчитать/отфильтровать по нему можно только в PHP, не в SQL.
        // Группа в ленте — одна карточка-стопка (её первый пост, Post::scopeCollapsedGroups): и здесь,
        // и в выборке страницы ниже считаем и листаем карточки, а не посты; фильтры и тип медиа
        // у группы — по её первому посту
        $lightPosts = Post::selectRaw('posts.id, posts.title, posts.created_at, posts.group_id, posts.sort_order, posts.small_image, posts.medium_image, posts.original_image, posts.video, posts.event_date')
            ->where('posts.user_id', auth()->id())
            ->when(!$ungrouped, function($q) {
                $q->collapsedGroups((int)auth()->id());
            })
            ->when($activeUsed, function($q) use ($activeUsed) {
                $q->where('posts.is_used', $activeUsed === 'yes');
            })
            ->when($withoutHashtags, function($q) {
                $q->whereDoesntHave('hashtags');
            })
            ->when($activePostType, function($q) use ($activePostType) {
                $q->where('posts.type', $activePostType);
            })
            ->when($clothesWhere, function($q) use ($clothesWhere) {
                $q->whereHas('clothes', function($q) use ($clothesWhere) {
                    $q->where($clothesWhere);
                });
            })
            ->get()
            ->each(function ($post) {
                $post->media_type = $this->resolvePostMediaType($post);
            });

        $typeCounts = [
            'all' => $lightPosts->count(),
            'photo' => $lightPosts->where('media_type', 'photo')->count(),
            'video' => $lightPosts->where('media_type', 'video')->count(),
            'carousel' => $lightPosts->where('media_type', 'carousel')->count(),
        ];

        $baseSelect = 'posts.id, posts.title, posts.alias, posts.user_id, posts.category_id, posts.type, posts.film_post_id, posts.group_id,
        posts.status, posts.is_used, posts.content, posts.small_image, posts.medium_image, posts.original_image, posts.video, posts.palette,
        posts.event_date, posts.event_date_precision';

        if ($activeType === 'all') {
            // без фильтра по типу медиа — обычная SQL-пагинация, как раньше
            $posts = Post::selectRaw($baseSelect)
                ->with($withRelations)
                ->where('posts.user_id', auth()->id())
                ->when(!$ungrouped, function($q) {
                    $q->collapsedGroups((int)auth()->id());
                })
                ->when($activeUsed, function($q) use ($activeUsed) {
                    $q->where('posts.is_used', $activeUsed === 'yes');
                })
                ->when($withoutHashtags, function($q) {
                    $q->whereDoesntHave('hashtags');
                })
                ->when($activePostType, function($q) use ($activePostType) {
                    $q->where('posts.type', $activePostType);
                })
                ->when($clothesWhere, function($q) use ($clothesWhere) {
                    $q->whereHas('clothes', function($q) use ($clothesWhere) {
                        $q->where($clothesWhere);
                    });
                })
                ->when($activeSort === 'title', function ($q) { $q->orderBy('posts.title'); })
                // по дате пина; у постов одной группы дата общая — дальше они идут вместе, по порядку в группе
                ->when($activeSort === 'date', function ($q) {
                    $q->orderByRaw('posts.event_date IS NULL')
                        ->orderByDesc('posts.event_date')
                        ->orderByRaw('COALESCE(posts.group_id, posts.id) desc')
                        ->orderBy('posts.sort_order', 'asc');
                })
                ->when(!in_array($activeSort, ['title', 'date'], true), function ($q) use ($activeSort) {
                    // group_id = id одного из постов группы (обычно первого созданного), поэтому
                    // COALESCE(group_id, id) ставит карточку группы туда, где ей место по общему
                    // направлению сортировки, даже если первым в группе стоит более новый пост
                    $direction = $activeSort === 'old' ? 'asc' : 'desc';
                    $q->orderByRaw("COALESCE(posts.group_id, posts.id) {$direction}")
                        ->orderBy('posts.sort_order', 'asc');
                })
                ->paginate($perPage);
        } else {
            // с фильтром по типу — тип уже посчитан выше в $lightPosts, дальше просто
            // сортируем/режем на страницы в PHP и подгружаем полные посты по их id
            $filtered = $lightPosts->where('media_type', $activeType);
            // тот же порядок, что и в SQL-ветке выше (COALESCE(group_id, id)), средствами коллекции.
            // Ключ считается один раз на пост: раньше тут была сортировка по списку замыканий, а их
            // коллекция вызывает как сравнение двух постов — порядок выходил случайным, а сама сортировка
            // нескольких тысяч постов занимала минуты
            $groupKey = fn ($post) => $post->group_id ?? $post->id;
            // внутри группы (снята галочка "Свернуть группы") — по sort_order: сортировка в PHP 8 устойчивая,
            // поэтому сначала по sort_order, потом по группе, и порядок внутри группы сохраняется
            if ($activeSort === 'title') {
                $filtered = $filtered->sortBy('title');
            } elseif ($activeSort === 'date') {
                // как в SQL-ветке: по дате пина (новые выше), без даты — в конце; группа вместе, по порядку в ней
                $filtered = $filtered->sortBy('sort_order')
                    ->sortBy($groupKey, SORT_REGULAR, true)
                    ->sortBy(fn ($post) => $post->event_date ? -$post->event_date->getTimestamp() : PHP_INT_MAX);
            } else {
                $filtered = $filtered->sortBy('sort_order')->sortBy($groupKey, SORT_REGULAR, $activeSort !== 'old');
            }
            $orderedIds = $filtered->pluck('id')->values();
            $pageIds = $orderedIds->forPage($page, $perPage);

            $pagedPosts = Post::selectRaw($baseSelect)
                ->with($withRelations)
                ->whereIn('posts.id', $pageIds)
                ->get()
                ->sortBy(fn ($post) => $pageIds->search($post->id))
                ->values();

            $posts = new \Illuminate\Pagination\LengthAwarePaginator(
                $pagedPosts,
                $orderedIds->count(),
                $perPage,
                $page,
                ['path' => $request->url(), 'query' => $request->query()]
            );
        }

        foreach ($posts as $post) {
            //dd($post);

            if (!empty($post->small_image) && isJson($post->small_image)) {
                $smallImage = json_decode($post->small_image, true);
                if (!isset($smallImage['path'])) {
                    $firstPicture = array_shift($smallImage);
                    $post['cover_image'] = $firstPicture['path'] ?? '';
                } else {
                    $post['cover_image'] = $smallImage['path'];
                }
            } elseif (!empty($post->medium_image) && isJson($post->medium_image)) {
                $mediumImage = json_decode($post->medium_image, true);
                $post['cover_image'] = $mediumImage['path'];
            } elseif (!empty($post->original_image) && isJson($post->original_image)) {
                $originalImage = json_decode($post->original_image, true);
                $post['cover_image'] = $originalImage['path'];
            }
        }

        // счётчик постов на карточке-стопке группы; без стопок (снята галочка "Свернуть группы") он не нужен —
        // карточка без group_count показывается обычной, с чипом "Группа" (parts/post_items.blade.php)
        if (!$ungrouped) {
            Post::fillGroupCounts($posts);
            // на стопке — только общие хештеги группы, без собственных хештегов её первого поста
            Post::fillGroupCommonHashtags($posts);
        } else {
            // отдельные карточки постов групп: собственные хештеги — другим цветом
            Post::fillGroupOwnHashtagIds($posts);
        }

        if ($request->ajax()) {
            return view('user.posts.parts.post_items', [
                'posts' => $posts,
            ]);
        }

        return view('user.posts.index', [
            'posts' => $posts,
            'withoutHashtags' => $withoutHashtags,
            'typeCounts' => $typeCounts,
            'activeType' => $activeType,
            'activeSort' => $activeSort,
            'activePostType' => $activePostType,
            'activeUsed' => $activeUsed,
            'ungrouped' => $ungrouped,
            'clothesFilterOptions' => $clothesFilterOptions,
            'activeClothes' => $activeClothes,
            // окно "Сменить категорию" режима выбора
            'categories' => PostsCategory::where('user_id', auth()->id())->orderBy('title', 'DESC')->get(),
            // счётчик на кнопке "Корзина"
            'trashCount' => Post::onlyTrashed()->where('user_id', auth()->id())->count(),
        ]);
    }

    /**
     * Тип медиа поста для вкладок "Фото/Видео/Карусель" на "Все посты" — есть ли
     * видео, и если нет, содержит ли JSON с картинками больше одной (карусель)
     * или ровно одну (фото). Та же логика, что у $post['cover_image'] в index().
     */
    private function resolvePostMediaType(Post $post): string
    {
        if (!empty($post->video)) {
            return 'video';
        }

        $imagesCount = 0;

        if (!empty($post->small_image) && isJson($post->small_image)) {
            $decoded = json_decode($post->small_image, true);
            $imagesCount = isset($decoded['path']) ? 1 : count($decoded);
        } elseif (!empty($post->medium_image) && isJson($post->medium_image)) {
            $decoded = json_decode($post->medium_image, true);
            $imagesCount = isset($decoded['path']) ? 1 : count($decoded);
        } elseif (!empty($post->original_image) && isJson($post->original_image)) {
            $decoded = json_decode($post->original_image, true);
            $imagesCount = isset($decoded['path']) ? 1 : count($decoded);
        }

        return $imagesCount > 1 ? 'carousel' : 'photo';
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return Application|Factory|View
     */
    public function create()
    {
        $categories = PostsCategory::where('user_id', auth()->id())->orderBy('title', 'DESC')->get();
        $hashtags = Hashtag::where('user_id', auth()->id())->orderBy('title', 'DESC')->get();

//        DevHelpersContoller::writeLogToFile($categories);

        return view('user.posts.create', ['categories' => $categories, 'hashtags' => $hashtags]);
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function store(Request $request)
    {
        // дату пина проверяем до того, как что-то сохранено: иначе часть постов успела бы создаться без неё
        if (trim((string)$request->input('event_date')) !== '' && Post::parseEventDate($request->input('event_date')) === null) {
            return response()->json(['status' => false, 'message' => self::EVENT_DATE_ERROR]);
        }

        $user = auth()->user();
        //dd($request);

        if (!empty($request->images)) {

            $images = json_decode($request->images, true);

            // сначала ищем дубликаты среди всех картинок, ничего не сохраняя: если есть — пусть
            // пользователь решит по каждому (окно сравнения в admin.js), а потом форма придёт снова
            $duplicateInfos = $this->findDuplicatesBeforeCreate($request, $images ?: [], (int)$user->id);
            if (!empty($duplicateInfos)) {
                return response()->json([
                    'needs_choice' => true,
                    // 'carousel' — все картинки идут в один пост: сливать его с найденным целиком нельзя,
                    // выбираем только, в каком из постов останется картинка; 'post' — картинка это
                    // отдельный пост, его можно объединить с найденным (текст, название, хештеги)
                    'duplicate_mode' => count($images) > 1 && $request->numberOfPosts === 'one-post' ? 'carousel' : 'post',
                    'duplicate_infos' => $duplicateInfos,
                ]);
            }

            //если картинок больше одной
            if (count($images) > 1) {

                if (!empty($request->numberOfPosts) && $request->numberOfPosts === 'one-post') {
                    // array:2 [
                    //  "239599730-874421156782213-4428682906124005867-n" => array:7 [
                    //    "image_name" => "239599730-874421156782213-4428682906124005867-n"
                    //    "image_extension" => "jpg"
                    //    "image_path" => "temp_directory/239599730-874421156782213-4428682906124005867-n.jpg"
                    //    "s_image_name" => "239599730-874421156782213-4428682906124005867-n_350_438.jpg"
                    //    "s_image_path" => "temp_directory/239599730-874421156782213-4428682906124005867-n_350_438.jpg"
                    //    "m_image_name" => "239599730-874421156782213-4428682906124005867-n_800_1000.jpg"
                    //    "m_image_path" => "temp_directory/239599730-874421156782213-4428682906124005867-n_800_1000.jpg"
                    //  ]
                    //  "3117x4500-0xac120003-11617019221616755779" => array:7 [
                    //    "image_name" => "3117x4500-0xac120003-11617019221616755779"
                    //    "image_extension" => "jpg"
                    //    "image_path" => "temp_directory/3117x4500-0xac120003-11617019221616755779.jpg"
                    //    "s_image_name" => "3117x4500-0xac120003-11617019221616755779_350_505.jpg"
                    //    "s_image_path" => "temp_directory/3117x4500-0xac120003-11617019221616755779_350_505.jpg"
                    //    "m_image_name" => "3117x4500-0xac120003-11617019221616755779_800_1155.jpg"
                    //    "m_image_path" => "temp_directory/3117x4500-0xac120003-11617019221616755779_800_1155.jpg"
                    //  ]
                    //]
                    $newPost = $this->createPost($request, $user->id, $images);

                    if ($newPost !== true) {
                        return response()->json([
                            'errors' => $newPost['errors'],
                            'duplicate_infos' => $newPost['duplicate_infos'] ?? null,
                        ]);
                    }
                } else {
                    $count = 0;
                    $errors = [];
                    $duplicateInfos = [];

                    // "Группировать посты" (галочка group-posts) — посты остаются отдельными
                    // записями (в отличие от "Один пост", где картинки объединяются в один),
                    // но получают общий group_id (= id первого созданного поста группы) и
                    // sort_order по порядку загрузки — см. posts.groups.show/reorder.
                    // Группируются посты с картинками (Post::groupKind()): текст формы становится общим
                    // текстом группы — он и так уходит в каждый пост. С выбранным вручную типом фильм,
                    // рецепт или одежда посты создаются без группы; новость группируется
                    $shouldGroup = $request->boolean('groupPosts')
                        && (!in_array($request->input('pin_type'), Post::MANUAL_TYPES, true) || $request->input('pin_type') === Post::TYPE_NEWS);
                    $groupId = null;
                    $sortOrder = 0;
                    // картинки, из которых посты уже созданы: если на какой-то из следующих случится ошибка,
                    // форма уберёт их у себя — иначе повторное сохранение споткнулось бы о них же
                    $createdKeys = [];

                    foreach ($images as $key => $image) {
                        $count++;

                        $createdId = null;
                        $newPost = $this->createPost($request, $user->id, $image, '', $groupId, $shouldGroup ? $sortOrder : null, $createdId);

                        if ($newPost === true) {
                            $createdKeys[] = $key;
                        }

                        if ($newPost !== true) {
                            $errors[] = $newPost['errors'];
                            if (!empty($newPost['duplicate_infos'])) {
                                $duplicateInfos = array_merge($duplicateInfos, $newPost['duplicate_infos']);
                            }
                            continue;
                        }

                        if ($shouldGroup) {
                            if ($groupId === null) {
                                // группы отдельной таблицей не заводим — id первого поста
                                // группы и есть group_id для всех её постов, включая его самого
                                $groupId = $createdId;
                                Post::whereKey($createdId)->update(['group_id' => $groupId]);
                            }
                            $sortOrder++;
                        }
                    }

                    if (!empty($errors)) {
                        return response()->json([
                            'errors' => $errors,
                            'duplicate_infos' => $duplicateInfos,
                            'created_keys' => $createdKeys,
                        ]);
                    }

                    return response()->json([
                        'status' => true,
                        'message' => 'Посты были успешно добавлены',
                    ]);
                }

            }

            //TODO
            $image = array_shift($images);
            //dd($image);
            $newPost = $this->createPost($request, $user->id, $image);
            if ($newPost === true) {
                //return back()->with('success','Item created successfully!');
                return response()->json([
                    'status' => true,
                    'message' => 'Пост был успешно добавлен',
                    //'redirect' => route('posts.index'),
                ]);
            }

            return response()->json([
                'errors' => $newPost['errors'],
                'duplicate_infos' => $newPost['duplicate_infos'] ?? null,
            ]);
        }

        $newPost = $this->createPost($request, $user->id);
        if ($newPost === true) {
            return response()->json([
                'status' => true,
                'message' => 'Пост был успешно добавлен',
            ]);
        }

        return response()->json([
            'errors' => $newPost['errors'],
        ]);
    }

    /**
     * Загружает видео во временную папку пользователя (аналог upload-image-to-temp-directory,
     * но без ресайза/хеширования — видео не пережимаем). Возвращает данные для отправки
     * вместе с формой создания поста (см. submitCreationForm() в admin.js).
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function uploadVideoToTempDirectory(Request $request)
    {
        try {
            $request->validate([
                'video' => 'required|mimes:mp4,mov,webm,avi,mkv,m4v|max:512000',
            ]);

            $file = $request->file('video');
            $userId = auth()->id();
            $extension = strtolower($file->getClientOriginalExtension()) ?: 'mp4';
            $name = \Illuminate\Support\Str::random(20);
            $tempDir = 'temp_directory/' . $userId;

            Storage::disk('local')->makeDirectory($tempDir);
            $file->storeAs($tempDir, $name . '.' . $extension, 'local');

            return response()->json([
                'status' => true,
                'video' => [
                    'video_name' => $name,
                    'video_extension' => $extension,
                    'video_path' => $tempDir . '/' . $name . '.' . $extension,
                ],
            ]);
        } catch (\Throwable $e) {
            return response()->json(['status' => false, 'message' => $e->getMessage()]);
        }
    }

    /**
     * Переносит видео из временной папки в постоянное хранилище поста.
     *
     * @param array $videoData {video_name, video_extension, video_path} — см. uploadVideoToTempDirectory()
     * @param string $userId
     * @return array{name: string, extension: string, path: string}|null
     */
    private function saveVideoForPost(array $videoData, string $userId): ?array
    {
        if (empty($videoData['video_path']) || empty($videoData['video_name']) || empty($videoData['video_extension'])) {
            return null;
        }

        $oldPath = $videoData['video_path'];
        if (!Storage::disk('local')->exists($oldPath)) {
            return null;
        }

        $newDir = 'videos/' . $userId . '/' . now()->format('Y-m-d');
        $newName = $videoData['video_name'] . '.' . $videoData['video_extension'];
        $newPath = $newDir . '/' . $newName;

        Storage::disk('local')->makeDirectory($newDir);

        if (Storage::move($oldPath, $newPath) !== true) {
            return null;
        }

        return [
            'name' => $videoData['video_name'],
            'extension' => $videoData['video_extension'],
            'path' => '/' . $newPath,
        ];
    }

    /**
     * Display the specified resource.
     *
     * @param Request $request
     * @param Post $post
     * @return Application|Factory|View
     */
    public function show(Request $request, Post $post)
    {
        // у поста группы своей страницы нет — его смотрят и правят на странице группы (showGroup());
        // превью в поп-апе на "Все посты" (?fragment=1) остаётся
        if (!$request->boolean('fragment') && $post->group_id && $post->groupKind() !== null) {
            return redirect($this->groupPostUrl($post));
        }

        // $post уже привязан роутом только к постам текущего пользователя (RouteServiceProvider);
        // ниже перечитываем его с нужными связями
        $post = Post::where('id', $post->id)->with([
            'category' => function($q) {
                $q->select(['id', 'title', 'alias', 'parent_id', 'user_id', 'status']);
            },
            'category.parent' => function($q) {
                $q->select(['id', 'title', 'alias', 'parent_id', 'user_id', 'status']);
            },
            'category.parent.parent' => function($q) {
                $q->select(['id', 'title', 'alias', 'parent_id', 'user_id', 'status']);
            },
            'hashtags' => function($q) {
                $q->select(['id', 'title', 'parent_id', 'user_id']);
            },
            'film',
            'clothes.brand',
            // фильм, к которому прикреплён этот пост (кадр/промо/видео) — чип "Фильм" на странице и в превью
            'filmPost' => function($q) {
                $q->select(['id', 'title']);
            },
        ])->firstOrFail();

        $categories = PostsCategory::where('user_id', auth()->id())->orderBy('title', 'DESC')->get();

        $originalImage = json_decode($post->original_image, true);
        $mediumImage = json_decode($post->medium_image, true);

        // приводим одиночную картинку ({path:...}) и карусель ({0:{path:...}, 1:{...}})
        // к единому плоскому списку — вьюхе (галерее) без разницы, сколько картинок
        $galleryImages = [];
        if (!empty($originalImage)) {
            if (isset($originalImage['path'])) {
                $galleryImages[] = [
                    'full' => $originalImage['path'],
                    'medium' => $mediumImage['path'] ?? $originalImage['path'],
                ];
            } else {
                foreach ($originalImage as $key => $image) {
                    if (empty($image['path'])) {
                        continue;
                    }
                    $galleryImages[] = [
                        'full' => $image['path'],
                        'medium' => $mediumImage[$key]['path'] ?? $image['path'],
                    ];
                }
            }
        }

        $groupCount = $post->group_id ? Post::where('group_id', $post->group_id)->where('user_id', auth()->id())->count() : 0;

        // компактный превью-фрагмент для галереи-просмотрщика на "Все посты" (клик по
        // посту открывает Fancybox с этим фрагментом внутри, ?fragment=1 в href триггера —
        // см. post_items.blade.php и @push('footer-scripts') в posts/index.blade.php)
        if ($request->boolean('fragment')) {
            return view('user.posts.parts.post_preview', [
                'post' => $post,
                'galleryImages' => $galleryImages,
                'groupCount' => $groupCount,
            ]);
        }

        return view('user.posts.show', [
            'categories' => $categories,
            'post' => $post,
            'galleryImages' => $galleryImages,
            'groupCount' => $groupCount,
            'filmMaterials' => $post->type === Post::TYPE_FILM ? $this->filmMaterialsBySection($post) : null,
        ]);
    }

    /**
     * Материалы фильма (посты с film_post_id = id фильма) по разделам блока "Материалы" на странице фильма:
     * [раздел => коллекция постов], разделы — все из Post::MATERIAL_SECTION_LABELS, в том числе пустые
     *
     * @param Post $film
     * @return array
     */
    private function filmMaterialsBySection(Post $film): array
    {
        $materials = Post::where('film_post_id', $film->id)
            ->where('user_id', auth()->id())
            ->orderBy('id')
            ->get(['id', 'title', 'film_material_kind', 'small_image', 'medium_image', 'original_image', 'video']);

        foreach ($materials as $material) {
            $material->cover_image = $this->resolveCoverImagePath($material);
        }

        $sections = [];
        foreach (array_keys(Post::MATERIAL_SECTION_LABELS) as $section) {
            $sections[$section] = $materials->filter(fn (Post $material) => $material->filmMaterialSection() === $section)->values();
        }

        return $sections;
    }

    /**
     * Посты, которые можно прикрепить к фильму — список для окна "Добавить материалы" на странице фильма
     * (film-materials.js): поиск по названию и хештегу, следующие страницы — ?page=N.
     * Не предлагаем сам фильм, другие пины "Фильм / Сериал" и посты, уже прикреплённые к этому фильму
     *
     * @param Request $request
     * @param Post $post
     * @return \Illuminate\Http\JsonResponse
     */
    public function filmMaterialCandidates(Request $request, Post $post)
    {
        abort_unless($post->type === Post::TYPE_FILM, 404);

        $search = ltrim(trim((string) $request->get('q', '')), '#');

        $candidates = Post::where('user_id', auth()->id())
            ->where('id', '!=', $post->id)
            ->where('type', '!=', Post::TYPE_FILM)
            ->where(function ($q) use ($post) {
                $q->whereNull('film_post_id')->orWhere('film_post_id', '!=', $post->id);
            })
            ->when($search !== '', function ($q) use ($search) {
                $like = '%' . addcslashes($search, '%_\\') . '%';
                $q->where(function ($q) use ($like) {
                    $q->where('title', 'like', $like)
                        ->orWhereHas('hashtags', function ($q) use ($like) {
                            $q->where('title', 'like', $like);
                        });
                });
            })
            ->with(['filmPost' => function ($q) {
                $q->select(['id', 'title']);
            }])
            ->orderByDesc('id')
            ->simplePaginate(40, ['id', 'title', 'film_post_id', 'small_image', 'medium_image', 'original_image', 'video']);

        return response()->json([
            'items' => collect($candidates->items())->map(function (Post $candidate) {
                $cover = $this->resolveCoverImagePath($candidate);

                return [
                    'id' => $candidate->id,
                    'title' => (string) $candidate->title,
                    'cover' => $cover ? '/storage' . $cover : null,
                    'video' => $candidate->video_url,
                    // уже материал другого фильма — прикрепление перенесёт его сюда
                    'film' => $candidate->filmPost ? ($candidate->filmPost->title ?: 'другой фильм') : null,
                ];
            })->values(),
            'has_more' => $candidates->hasMorePages(),
        ]);
    }

    /**
     * Прикрепляет выбранные посты к фильму (окно "Добавить материалы")
     *
     * @param Request $request
     * @param Post $post
     * @return \Illuminate\Http\JsonResponse
     */
    public function attachFilmMaterials(Request $request, Post $post)
    {
        abort_unless($post->type === Post::TYPE_FILM, 404);

        $data = $request->validate([
            'ids' => ['required', 'array', 'max:500'],
            'ids.*' => ['integer'],
            'kind' => ['required', 'in:' . implode(',', array_keys(Post::MATERIAL_KIND_LABELS))],
        ]);

        // toBase(): не трогаем updated_at — сам пост не менялся
        $attached = Post::where('user_id', auth()->id())
            ->whereIn('id', $data['ids'])
            ->where('id', '!=', $post->id)
            ->where('type', '!=', Post::TYPE_FILM)
            ->toBase()
            ->update(['film_post_id' => $post->id, 'film_material_kind' => $data['kind']]);

        return response()->json(['attached' => $attached]);
    }

    /**
     * Открепляет пост от фильма — сам пост остаётся
     *
     * @param Post $post
     * @param int $materialId
     * @return \Illuminate\Http\JsonResponse
     */
    public function detachFilmMaterial(Post $post, $materialId)
    {
        $detached = Post::where('user_id', auth()->id())
            ->where('id', $materialId)
            ->where('film_post_id', $post->id)
            ->toBase()
            ->update(['film_post_id' => null, 'film_material_kind' => null]);

        abort_unless($detached, 404);

        return response()->json(['success' => true]);
    }

    /**
     * Страница группы постов — на ней группу и смотрят, и редактируют (своих страниц у постов группы нет,
     * см. show()/edit()): общие название, категория и хештеги, посты в порядке sort_order (перетаскиванием,
     * reorderGroup()), у группы текстов — текст каждого поста. Здесь же посты добавляют в группу и убирают
     * из неё. Вьюха — user/posts/group.blade.php, поведение — /admin/post-group.js.
     *
     * @param int $groupId
     * @return Application|Factory|View
     */
    public function showGroup($groupId)
    {
        $posts = $this->groupPosts($groupId);
        $head = $posts->first();
        $kind = $this->groupKind($posts);

        // хештеги: общие (есть у всех постов) — в блоке "Общее", собственные — на карточке каждого поста
        $commonIds = Post::commonHashtagIds($posts);
        foreach ($posts as $post) {
            $post->own_hashtags = $post->hashtags->whereNotIn('id', $commonIds)->values();
        }

        return view('user.posts.group', [
            'groupId' => $groupId,
            'posts' => $posts,
            'kind' => $kind,
            // у группы текстов два режима: просмотр (тексты только читают, карточки можно переставлять) и
            // редактирование (?edit=1 — общие поля, тексты, состав группы). Группа изображений всегда редактируется
            'editing' => $kind !== Post::TYPE_TEXT || request()->boolean('edit'),
            // общие поля — по первому посту
            'title' => $head->title,
            // у группы изображений текст общий (у группы текстов он у каждого поста свой)
            'groupText' => $kind === Post::TYPE_PICTURE ? $this->resolvePictureGroupText($posts, collect())[0] : '',
            'eventDateInput' => $head->eventDateInput(),
            'eventDateLabel' => $head->eventDateLabel(),
            // отметка "Использовано" у группы одна на всех — по первому посту
            'isUsed' => (bool)$head->is_used,
            'headId' => $head->id,
            'categoryId' => $head->category_id,
            'hashtags' => $head->hashtags->whereIn('id', $commonIds)->values(),
            'categories' => PostsCategory::where('user_id', auth()->id())->orderBy('title', 'DESC')->get(),
        ]);
    }

    /**
     * Посты группы в порядке sort_order, с хештегами и картинками для страницы группы; 404 — группы нет
     *
     * @param int $groupId
     * @return \Illuminate\Database\Eloquent\Collection
     */
    private function groupPosts($groupId)
    {
        $posts = Post::where('group_id', $groupId)
            ->where('user_id', auth()->id())
            ->with(['hashtags' => function ($q) {
                $q->select(['id', 'title', 'parent_id', 'user_id']);
            }])
            ->orderBy('sort_order')
            ->orderBy('id')
            ->get();

        abort_if($posts->isEmpty(), 404);

        foreach ($posts as $post) {
            $this->fillGroupImages($post);
        }

        return $posts;
    }

    /**
     * Картинки поста для карточки на странице группы: обложка, она же в полном размере (для просмотра)
     * и её имя — по нему картинку поворачивают (transformImage())
     *
     * @param Post $post
     * @return void
     */
    private function fillGroupImages(Post $post): void
    {
        $original = UploadImageController::normalizeImages($post->original_image);
        $firstOriginal = reset($original);

        $post->cover_image = $this->resolveCoverImagePath($post);
        $post->full_image = $firstOriginal['path'] ?? $post->cover_image;
        $post->image_name = array_key_first($original);
    }

    /**
     * Вид группы: тексты (у каждого поста свой текст) — когда в ней только посты "Текст"; иначе — изображения
     *
     * @param \Illuminate\Support\Collection $posts
     * @return string Post::TYPE_TEXT или Post::TYPE_PICTURE
     */
    private function groupKind($posts): string
    {
        return $posts->every(fn (Post $post) => $post->type === Post::TYPE_TEXT) ? Post::TYPE_TEXT : Post::TYPE_PICTURE;
    }

    /**
     * Сохраняет страницу группы: название, категория и дата — одни на все посты, хештеги — общие (hashtags) и
     * собственные у каждого поста (own_hashtags); текст у группы изображений общий (group_text), у группы
     * текстов — свой у каждого поста (texts[id поста])
     *
     * @param Request $request
     * @param int $groupId
     * @return \Illuminate\Http\JsonResponse
     */
    public function updateGroup(Request $request, $groupId)
    {
        $posts = $this->groupPosts($groupId);
        $isTextGroup = $this->groupKind($posts) === Post::TYPE_TEXT;

        $data = $request->validate([
            'title' => ['nullable', 'string', 'max:255'],
            'category_id' => ['nullable', 'integer'],
            'event_date' => ['nullable', 'string', 'max:40'],
            // общий текст группы изображений
            'group_text' => ['nullable', 'string'],
            'hashtags' => ['nullable', 'string'],
            'own_hashtags' => ['nullable', 'string'],
            'texts' => ['nullable', 'array'],
            'texts.*' => ['nullable', 'string'],
        ]);

        // дата пина у группы общая, как название; поле не пришло — не трогаем
        $hasEventDate = $request->has('event_date');
        if ($hasEventDate && trim((string)$request->input('event_date')) !== '' && Post::parseEventDate($request->input('event_date')) === null) {
            return response()->json(['status' => false, 'message' => self::EVENT_DATE_ERROR], 422);
        }

        $userHashtagIds = fn (array $ids) => Hashtag::where('user_id', auth()->id())->whereIn('id', $ids)->pluck('id')->all();

        // общие хештеги приходят как на форме поста: JSON {id: название}; не пришли — не трогаем
        $hashtagIds = null;
        if (isset($data['hashtags'])) {
            $requestHashtags = json_decode($data['hashtags'], true);
            if (is_array($requestHashtags)) {
                $hashtagIds = $userHashtagIds(array_keys($requestHashtags));
            }
        }

        // собственные хештеги постов: JSON {id поста: [id хештегов]}; поста там нет — его собственные не меняем
        $ownIds = [];
        $requestOwn = json_decode($data['own_hashtags'] ?? '', true);
        if (is_array($requestOwn)) {
            foreach ($requestOwn as $postId => $ids) {
                if ($posts->contains('id', (int)$postId) && is_array($ids)) {
                    $ownIds[(int)$postId] = $userHashtagIds(array_map('intval', $ids));
                }
            }
        }

        foreach ($posts as $post) {
            $post->title = (string)($data['title'] ?? '');
            $post->category_id = ($data['category_id'] ?? 0) ?: 0;
            if ($hasEventDate) {
                $post->setEventDateFromInput($request->input('event_date'));
            }
            if ($isTextGroup && array_key_exists($post->id, $data['texts'] ?? [])) {
                $post->content = $data['texts'][$post->id];
            }
            // группа изображений: текст общий — один и тот же у всех её постов (Post::groupKind())
            if (!$isTextGroup && $request->has('group_text')) {
                $post->content = (string)$request->input('group_text');
            }
            // cover_image/full_image/image_name — только для вьюхи (fillGroupImages()), колонок таких нет
            unset($post->cover_image, $post->full_image, $post->image_name);
            $post->save();
        }

        if ($hashtagIds !== null || $ownIds) {
            Post::syncGroupHashtags($posts, $hashtagIds ?? Post::commonHashtagIds($posts), $ownIds);
        }

        return response()->json(['status' => true, 'message' => 'Группа сохранена']);
    }

    /**
     * Скачать картинки и видео поста — пункт "Скачать" в меню карточки. Один файл отдаётся как есть,
     * несколько (карусель, картинки и видео) — zip-архивом
     *
     * @param Post $post
     * @return \Symfony\Component\HttpFoundation\BinaryFileResponse
     */
    public function download(Post $post)
    {
        $files = $this->postMediaFiles($post);
        abort_if(empty($files), 404);

        if (count($files) === 1) {
            return response()->download($files[0]['path'], $files[0]['name']);
        }

        return $this->downloadZip(
            array_column($files, 'path', 'name'),
            \Illuminate\Support\Str::slug((string)$post->title) ?: 'post-' . $post->id
        );
    }

    /**
     * Скачать группу архивом: картинки и видео всех её постов в порядке группы, имена файлов
     * с номером впереди ("01-...", "02-...") — чтобы порядок сохранился и в папке
     *
     * @param int $groupId
     * @return \Symfony\Component\HttpFoundation\BinaryFileResponse
     */
    public function downloadGroup($groupId)
    {
        $posts = $this->groupPosts($groupId);

        $files = [];
        foreach ($posts as $post) {
            foreach ($this->postMediaFiles($post) as $file) {
                $files[] = $file;
            }
        }
        abort_if(empty($files), 404);

        $digits = max(2, strlen((string)count($files)));
        $entries = [];
        foreach ($files as $i => $file) {
            $entries[str_pad((string)($i + 1), $digits, '0', STR_PAD_LEFT) . '-' . $file['name']] = $file['path'];
        }

        return $this->downloadZip(
            $entries,
            \Illuminate\Support\Str::slug((string)$posts->first()->title) ?: 'group-' . $groupId
        );
    }

    /**
     * Файлы поста для скачивания: оригиналы картинок по порядку, затем видео; только те, что есть на диске
     *
     * @param Post $post
     * @return array [['name' => 'имя.расширение', 'path' => абсолютный путь], ...] — имена не повторяются
     */
    private function postMediaFiles(Post $post): array
    {
        $disk = Storage::disk('local');
        $found = [];

        foreach (UploadImageController::normalizeImages($post->original_image) as $name => $image) {
            if (!empty($image['path'])) {
                $found[] = [$name, $image['extension'] ?? pathinfo($image['path'], PATHINFO_EXTENSION), $image['path']];
            }
        }
        if (!empty($post->video_data['path'])) {
            $video = $post->video_data;
            $found[] = [$video['name'] ?? 'video', $video['extension'] ?? pathinfo($video['path'], PATHINFO_EXTENSION), $video['path']];
        }

        $files = [];
        $taken = [];
        foreach ($found as [$name, $extension, $path]) {
            $absolutePath = $disk->path($path);
            if (!file_exists($absolutePath)) {
                continue;
            }

            // у части постов расширение уже входит в сохранённое имя картинки
            if (strtolower(pathinfo((string)$name, PATHINFO_EXTENSION)) === strtolower((string)$extension)) {
                $name = pathinfo((string)$name, PATHINFO_FILENAME);
            }

            $fileName = $name . '.' . $extension;
            for ($i = 1; isset($taken[$fileName]); $i++) {
                $fileName = $name . '-' . $i . '.' . $extension;
            }
            $taken[$fileName] = true;

            $files[] = ['name' => $fileName, 'path' => $absolutePath];
        }

        return $files;
    }

    /**
     * Собирает zip во временной папке пользователя и отдаёт его; файл удаляется после отправки
     *
     * @param array $entries [имя в архиве => абсолютный путь]
     * @param string $filename имя архива без расширения
     * @return \Symfony\Component\HttpFoundation\BinaryFileResponse
     */
    private function downloadZip(array $entries, string $filename)
    {
        $tempDir = 'temp_directory/' . auth()->id();
        Storage::disk('local')->makeDirectory($tempDir);
        $zipPath = Storage::disk('local')->path($tempDir . '/' . $filename . '-' . time() . '.zip');

        $zip = new \ZipArchive();
        abort_unless($zip->open($zipPath, \ZipArchive::CREATE | \ZipArchive::OVERWRITE) === true, 500, 'Не удалось создать архив');
        foreach ($entries as $entryName => $absolutePath) {
            $zip->addFile($absolutePath, $entryName);
        }
        $zip->close();

        return response()->download($zipPath, $filename . '.zip')->deleteFileAfterSend(true);
    }

    /**
     * Экспорт группы текстов в файл — кнопки "DOCX" / "PDF" на странице группы: название группы и тексты
     * постов по порядку, каждый пост — один абзац (App\Components\TextGroupExport)
     *
     * @param int $groupId
     * @param string $format docx или pdf
     * @return \Symfony\Component\HttpFoundation\StreamedResponse
     */
    public function exportGroup($groupId, string $format)
    {
        $posts = $this->groupPosts($groupId);
        abort_unless($this->groupKind($posts) === Post::TYPE_TEXT, 404);

        $title = trim((string)$posts->first()->title);
        $paragraphs = TextGroupExport::paragraphs($posts->pluck('content'));

        $file = $format === 'pdf' ? TextGroupExport::pdf($title, $paragraphs) : TextGroupExport::docx($title, $paragraphs);
        $name = (\Illuminate\Support\Str::slug($title) ?: 'group-' . $groupId) . '.' . $format;

        return response()->streamDownload(function () use ($file) {
            echo $file;
        }, $name, [
            'Content-Type' => $format === 'pdf'
                ? 'application/pdf'
                : 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
        ]);
    }

    /**
     * Посты, которые можно добавить в группу — список для окна "Добавить посты" (post-group.js): того же вида,
     * что и группа (изображения или тексты), и ещё не в группе; в группу изображений — без текста или с тем же
     * текстом, что у группы. Поиск по названию, хештегу и тексту, ?page=N
     *
     * @param Request $request
     * @param int $groupId
     * @return \Illuminate\Http\JsonResponse
     */
    public function groupCandidates(Request $request, $groupId)
    {
        $groupPosts = $this->groupPosts($groupId);
        $kind = $this->groupKind($groupPosts);
        // у группы изображений текст общий — посты со своим, другим текстом не предлагаем (resolvePictureGroupText())
        $groupText = $kind === Post::TYPE_PICTURE ? $this->resolvePictureGroupText($groupPosts, collect())[0] : '';
        $search = ltrim(trim((string) $request->get('q', '')), '#');

        $candidates = Post::where('user_id', auth()->id())
            ->whereIn('type', $this->groupKindTypes($kind))
            ->whereNull('group_id')
            ->when($search !== '', function ($q) use ($search) {
                $like = '%' . addcslashes($search, '%_\\') . '%';
                $q->where(function ($q) use ($like) {
                    $q->where('title', 'like', $like)
                        ->orWhere('content', 'like', $like)
                        ->orWhereHas('hashtags', function ($q) use ($like) {
                            $q->where('title', 'like', $like);
                        });
                });
            })
            ->orderByDesc('id')
            ->simplePaginate(40, ['id', 'type', 'title', 'content', 'small_image', 'medium_image', 'original_image', 'video']);

        $items = collect($candidates->items())->filter(function (Post $candidate) use ($kind, $groupText) {
            return $candidate->groupKind() === $kind
                && ($groupText === '' || $this->postPlainText($candidate->content) === '' || $this->isSamePostText($groupText, $candidate->content));
        });

        return response()->json([
            'items' => $items->map(function (Post $candidate) {
                $cover = $this->resolveCoverImagePath($candidate);
                $text = trim(html_entity_decode(strip_tags((string)$candidate->content), ENT_QUOTES | ENT_HTML5, 'UTF-8'));

                return [
                    'id' => $candidate->id,
                    // у текста без названия — начало самого текста
                    'title' => (string)$candidate->title !== '' ? (string)$candidate->title : \Illuminate\Support\Str::limit($text, 90),
                    'cover' => $cover ? '/storage' . $cover : null,
                    'video' => $candidate->video_url,
                ];
            })->values(),
            'has_more' => $candidates->hasMorePages(),
        ]);
    }

    /**
     * Добавляет выбранные посты в конец группы (окно "Добавить посты"). Название и категория у них становятся
     * общими с группой, а их собственные хештеги добавляются к хештегам группы — то есть ко всем её постам
     *
     * @param Request $request
     * @param int $groupId
     * @return \Illuminate\Http\JsonResponse
     */
    public function attachToGroup(Request $request, $groupId)
    {
        $data = $request->validate([
            'ids' => ['required', 'array', 'max:500'],
            'ids.*' => ['integer'],
        ]);

        $posts = $this->groupPosts($groupId);
        $kind = $this->groupKind($posts);

        $added = Post::where('user_id', auth()->id())
            ->whereIn('id', $data['ids'])
            ->whereIn('type', $this->groupKindTypes($kind))
            ->whereNull('group_id')
            ->with('hashtags:id')
            ->orderBy('id')
            ->get()
            ->filter(fn (Post $post) => $post->groupKind() === $kind)
            ->values();

        // у группы изображений текст общий: пост со своим, другим текстом не добавляем — его текст пропал бы
        $skipped = 0;
        $groupText = '';
        if ($kind === Post::TYPE_PICTURE) {
            [$groupText, $added, $rejected] = $this->resolvePictureGroupText($posts, $added);
            $skipped = $rejected->count();
        }

        $this->addPostsToGroup($posts, $added, $groupId, Post::commonHashtagIds($posts));
        if ($groupText !== '') {
            $this->applyPictureGroupText($posts->pluck('id')->concat($added->pluck('id'))->all(), $groupText);
        }

        return response()->json(['attached' => $added->count(), 'skipped' => $skipped]);
    }

    /**
     * Типы постов, которые могут быть в группе этого вида (для выборки; точнее — Post::groupKind())
     *
     * @param string $kind Post::TYPE_PICTURE или Post::TYPE_TEXT
     * @return string[]
     */
    private function groupKindTypes(string $kind): array
    {
        return $kind === Post::TYPE_TEXT
            ? [Post::TYPE_TEXT]
            : [Post::TYPE_PICTURE, Post::TYPE_TEXT_PICTURE, Post::TYPE_NEWS];
    }

    /**
     * Текст группы изображений общий (Post::groupKind()): у каждого её поста он один и тот же. Определяет его
     * по постам группы и добавляемым: пустой текст ничему не мешает, первый непустой становится текстом группы,
     * а пост с другим текстом в группу не берём — иначе его текст пропал бы
     *
     * @param \Illuminate\Support\Collection $members посты группы (у новой группы — её первый пост)
     * @param \Illuminate\Support\Collection $added добавляемые посты
     * @return array [string $text ('' — текста у группы нет), Collection $accepted, Collection $rejected]
     */
    private function resolvePictureGroupText($members, $added): array
    {
        $text = '';
        foreach ($members as $member) {
            if ($this->postPlainText($member->content) !== '') {
                $text = (string)$member->content;
                break;
            }
        }

        $accepted = collect();
        $rejected = collect();
        foreach ($added as $post) {
            $own = $this->postPlainText($post->content);
            if ($own !== '' && $text !== '' && !$this->isSamePostText($text, $post->content)) {
                $rejected->push($post);
                continue;
            }
            if ($text === '' && $own !== '') {
                $text = (string)$post->content;
            }
            $accepted->push($post);
        }

        return [$text, $accepted, $rejected];
    }

    /**
     * Ставит текст группы изображений всем её постам, у которых он не такой (пустой или иначе размечен).
     * Через свежие модели и save(): тип поста по содержимому пересчитывается (Post::booted) — картинка с текстом
     * становится "Текст + Изображение"
     *
     * @param int[] $postIds
     * @param string $text
     * @return void
     */
    private function applyPictureGroupText(array $postIds, string $text): void
    {
        Post::where('user_id', auth()->id())->whereIn('id', $postIds)->get()->each(function (Post $post) use ($text) {
            if ((string)$post->content !== $text) {
                $post->content = $text;
                $post->save();
            }
        });
    }

    /**
     * Кладёт посты в конец группы: название и категория у них становятся как у первого поста группы,
     * общие хештеги группы ($commonHashtagIds) добавляются к их хештегам, а их прежние хештеги остаются
     * у них собственными. У прежних постов группы хештеги не меняются
     *
     * @param \Illuminate\Database\Eloquent\Collection $members посты группы по порядку
     * @param \Illuminate\Database\Eloquent\Collection $added новые посты
     * @param int $groupId
     * @param int[] $commonHashtagIds общие хештеги группы; у новой группы — пусто
     * @return void
     */
    private function addPostsToGroup($members, $added, $groupId, array $commonHashtagIds): void
    {
        if ($added->isEmpty()) {
            return;
        }

        $head = $members->first();
        $sortOrder = (int)$members->max('sort_order');
        foreach ($added as $post) {
            $post->group_id = $groupId;
            $post->sort_order = ++$sortOrder;
            $post->title = (string)$head->title;
            $post->category_id = $head->category_id ?: 0;
            // дата у группы общая, как название; у новой группы (первый пост без даты) — свою не теряем
            if ($head->event_date) {
                $post->event_date = $head->event_date;
                $post->event_date_precision = $head->event_date_precision;
            }
            // отметка "Использовано" у группы тоже общая
            $post->is_used = (bool)$head->is_used;
            $post->save();

            if ($commonHashtagIds) {
                $post->hashtags()->syncWithoutDetaching($commonHashtagIds);
            }
        }
    }

    /**
     * Группирует отмеченные посты — режим "Выбрать" на "Все посты" (/admin/posts-bulk.js). Все они должны быть
     * одного вида (Post::groupKind()): только изображения или только тексты. Если среди них есть посты одной
     * уже существующей группы, остальные добавляются в неё; иначе создаётся новая группа, первый пост
     * (с меньшим id) задаёт ей название и категорию
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function bulkGroup(Request $request)
    {
        $data = $request->validate([
            'ids' => ['required', 'array', 'max:500'],
            'ids.*' => ['integer'],
        ]);

        $fail = fn (string $message) => response()->json(['status' => false, 'message' => $message], 422);

        $posts = Post::where('user_id', auth()->id())->whereIn('id', $data['ids'])->with('hashtags:id')->orderBy('id')->get();

        if ($posts->contains(fn (Post $post) => $post->groupKind() === null)) {
            return $fail('Группировать можно посты с картинками (в том числе с текстом) или посты из одного текста — фильмы, рецепты, одежду и новости без картинки уберите из выбора');
        }
        if ($posts->map(fn (Post $post) => $post->groupKind())->unique()->count() > 1) {
            return $fail('Посты с картинками и посты из одного текста группируются отдельно — выберите посты одного вида');
        }
        $kind = $posts->first()->groupKind();

        $groupIds = $posts->pluck('group_id')->filter()->unique()->values();
        if ($groupIds->count() > 1) {
            return $fail('Среди выбранных — посты из разных групп');
        }

        if ($groupIds->count() === 1) {
            $groupId = $groupIds->first();
            $members = Post::where('group_id', $groupId)->where('user_id', auth()->id())->with('hashtags:id')->orderBy('sort_order')->orderBy('id')->get();
            $added = $posts->whereNull('group_id')->values();

            if ($this->groupKind($members) !== $kind) {
                return $fail('Группа другого вида: посты с картинками и посты из одного текста группируются отдельно');
            }
            if ($added->isEmpty()) {
                return $fail('Все выбранные посты уже в одной группе');
            }
        } else {
            if ($posts->count() < 2) {
                return $fail('Выберите хотя бы два поста');
            }

            $members = $posts->take(1);
            $added = $posts->slice(1)->values();
        }

        // у группы изображений текст общий: посты с разным текстом не объединяем — чей-то текст пропал бы
        $groupText = '';
        if ($kind === Post::TYPE_PICTURE) {
            [$groupText, , $rejected] = $this->resolvePictureGroupText($members, $added);
            if ($rejected->isNotEmpty()) {
                return $fail('У выбранных постов разный текст, а у группы картинок текст общий. Уберите из выбора посты с другим текстом или сделайте текст одинаковым');
            }
        }

        if ($groupIds->count() === 0) {
            // группы отдельной таблицей не заводим — id первого поста и есть group_id
            $head = $members->first();
            $groupId = $head->id;
            $head->group_id = $groupId;
            $head->sort_order = 0;
            $head->save();
        }

        // у новой группы общих хештегов заранее нет: общими сами станут те, что есть у всех её постов
        $this->addPostsToGroup($members, $added, $groupId, $groupIds->count() === 1 ? Post::commonHashtagIds($members) : []);
        if ($groupText !== '') {
            $this->applyPictureGroupText($members->pluck('id')->concat($added->pluck('id'))->all(), $groupText);
        }

        return response()->json([
            'status' => true,
            // новую группу сразу открываем — в режиме редактирования
            'redirect' => route('posts.groups.show', [$groupId, 'edit' => 1]),
        ]);
    }

    /**
     * Меняет категорию сразу нескольким постам — режим "Выбрать" на "Все посты" (/admin/posts-bulk.js).
     * У постов из групп категория общая, поэтому её получает вся группа
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function bulkSetCategory(Request $request)
    {
        $data = $request->validate([
            'ids' => ['required', 'array', 'max:2000'],
            'ids.*' => ['integer'],
            // пусто — снять категорию
            'category_id' => ['nullable', 'integer'],
        ]);

        $categoryId = 0;
        if (!empty($data['category_id'])) {
            $categoryId = PostsCategory::where('user_id', auth()->id())->findOrFail($data['category_id'])->id;
        }

        $posts = Post::where('user_id', auth()->id())->whereIn('id', $data['ids'])->get(['id', 'type', 'group_id']);
        $groupIds = $posts->filter(fn (Post $post) => $post->group_id && $post->groupKind() !== null)->pluck('group_id')->unique();

        $updated = Post::where('user_id', auth()->id())
            ->where(function ($q) use ($posts, $groupIds) {
                $q->whereIn('id', $posts->pluck('id'))->orWhereIn('group_id', $groupIds);
            })
            ->update(['category_id' => $categoryId]);

        return response()->json(['status' => true, 'updated' => $updated]);
    }

    /**
     * Загружает картинку прямо на странице группы изображений — она становится новым постом в конце группы
     * (с общими названием, категорией и хештегами).
     *
     * Если похожая картинка уже есть в каком-то посте, ничего не сохраняется: в ответе needs_choice и данные
     * для окна сравнения (post-group.js). Решение приходит вторым запросом — уже без файла, со staged_image
     * (картинка ждёт во временной папке) и decision: "replace" — новая лучше, старую из её поста убрать,
     * "add" — это разные картинки, добавить как есть
     *
     * @param Request $request
     * @param int $groupId
     * @return \Illuminate\Http\JsonResponse
     */
    public function uploadToGroup(Request $request, $groupId)
    {
        try {
            $posts = $this->groupPosts($groupId);
            abort_unless($this->groupKind($posts) === Post::TYPE_PICTURE, 404);

            $uploadController = new UploadImageController();
            $userId = (string)auth()->id();
            $categoryId = (string)($posts->first()->category_id ?: 0);
            $replacedNote = null;

            if ($request->has('staged_image')) {
                $request->validate([
                    'staged_image' => 'required|array',
                    'staged_image.image_name' => 'required|string',
                    'staged_image.image_extension' => 'required|string',
                    'staged_image.image_path' => 'required|string',
                    'decision' => 'required|in:replace,add',
                    'duplicate_post_id' => 'required_if:decision,replace|integer',
                    'duplicate_image_name' => 'required_if:decision,replace|string',
                ]);

                $stagedImage = $request->input('staged_image');
                // пути приходят с фронтенда — только свои временные файлы
                abort_unless($this->isOwnStagedImage($stagedImage, (int)$userId), 422);

                if ($request->input('decision') === 'replace') {
                    $replacedNote = $this->removeReplacedImage((int)$request->input('duplicate_post_id'), (string)$request->input('duplicate_image_name'));
                }

                // решение принято — повторно на дубликат не проверяем
                $saved = $uploadController->saveImageForPost($stagedImage, $userId, $categoryId, false, true);
            } else {
                $request->validate([
                    'image' => 'required|file|mimes:jpeg,png,jpg,gif,svg,webp,avif',
                ]);

                $staged = $uploadController->stageFileToTempDirectory($request->file('image'));
                // allowDuplicateChoice: похожую картинку не отклоняем молча, а спрашиваем
                $saved = $uploadController->saveImageForPost($staged['data'], $userId, $categoryId, true);

                if (!empty($saved['duplicate_choice']) || !empty($saved['duplicate_reject_choice'])) {
                    $duplicate = $saved['duplicate_choice'] ?? $saved['duplicate_reject_choice'];
                    $inThisGroup = $posts->contains('id', $duplicate->post_id);
                    $where = $inThisGroup ? 'в этой группе' : 'в посте #' . $duplicate->post_id;

                    $disk = Storage::disk('local');
                    $newFileSize = $disk->exists($saved['staged_image']['image_path']) ? $disk->size($saved['staged_image']['image_path']) : 0;
                    $oldFileSize = $disk->exists($duplicate->path) ? $disk->size($duplicate->path) : 0;
                    // лучше качество — больше пикселей, при равном разрешении — больший файл (как в окне формы создания)
                    $newArea = $saved['new_width'] * $saved['new_height'];
                    $oldArea = $duplicate->width * $duplicate->height;
                    $newIsBetter = $newArea !== $oldArea ? $newArea > $oldArea : $newFileSize > $oldFileSize;

                    return response()->json([
                        'status' => false,
                        'needs_choice' => true,
                        // новая лучше — можно заменить ею старую; не лучше — возможно, это просто
                        // другая картинка, её можно добавить отдельным постом
                        'new_is_better' => $newIsBetter,
                        'message' => $newIsBetter
                            ? 'Похожая картинка, но хуже качеством, уже есть ' . $where . '. Заменить её новой?'
                            : 'Похоже, такая картинка уже есть ' . $where . '. Если это другая картинка — добавьте её отдельным постом.',
                        'duplicate' => $this->formatDuplicateForFrontend($duplicate) + ['file_size' => $oldFileSize],
                        'new_image' => [
                            'thumbnail_url' => '/storage/' . $saved['staged_image']['image_path'],
                            'width' => $saved['new_width'],
                            'height' => $saved['new_height'],
                            'file_size' => $newFileSize,
                        ],
                        'staged_image' => $saved['staged_image'],
                    ]);
                }
            }

            if (!empty($saved['errors'])) {
                $errors = $saved['errors'];
                throw new \Exception(is_array($errors) ? implode(' ', $errors) : $errors);
            }

            $post = $this->newGroupPost($posts, auth()->id() . '_' . ($posts->first()->category_id ?: 0) . '_' . $saved['original_image']['name']);
            // текст у группы изображений общий; у группы новостей и новый пост — новость
            $post->content = $this->resolvePictureGroupText($posts, collect())[0];
            if ($posts->first()->type === Post::TYPE_NEWS) {
                $post->type = Post::TYPE_NEWS;
            }
            // сохраняет пост и хеш картинки (поиск дубликатов)
            $this->attachSavedImageToPost($post, $saved);
            // новый пост группы получает её общие хештеги, собственных у него пока нет
            $post->hashtags()->sync(Post::commonHashtagIds($posts));

            return response()->json([
                'status' => true,
                'html' => $this->renderGroupItem($post, $groupId, Post::TYPE_PICTURE, $posts->count() + 1),
                // старую картинку заменили: что стало с её постом — и какую плитку убрать со страницы
                'replaced' => $replacedNote,
            ]);
        } catch (\Symfony\Component\HttpKernel\Exception\HttpException $e) {
            throw $e;
        } catch (\Exception $e) {
            return response()->json(['status' => false, 'message' => $e->getMessage()]);
        }
    }

    /**
     * Убирает из поста картинку, которую заменили новой (лучшей) копией в другом посте. Пост, в котором после
     * этого не осталось ни картинок, ни видео, ни текста, уходит в корзину — пустая плитка никому не нужна
     *
     * @param int $postId
     * @param string $imageName
     * @return array|null ['post_id' => ..., 'trashed' => bool]; null — поста уже нет
     */
    private function removeReplacedImage(int $postId, string $imageName): ?array
    {
        $post = Post::where('user_id', auth()->id())->find($postId);
        if (!$post) {
            return null;
        }

        (new UploadImageController)->swapPostImage($post, $imageName, null);
        $post->refresh();

        $text = html_entity_decode(strip_tags((string)$post->content), ENT_QUOTES | ENT_HTML5, 'UTF-8');
        $isEmpty = $post->coverImagePath() === null && empty($post->video_data) && preg_replace('/[\s\x{00A0}]+/u', '', $text) === '';
        if ($isEmpty) {
            $post->delete();
        }

        return ['post_id' => $post->id, 'trashed' => $isEmpty];
    }

    /**
     * Новый пустой пост "Текст" в конце группы текстов (кнопка "Новый текст" на странице группы)
     *
     * @param int $groupId
     * @return \Illuminate\Http\JsonResponse
     */
    public function addTextToGroup($groupId)
    {
        $posts = $this->groupPosts($groupId);
        abort_unless($this->groupKind($posts) === Post::TYPE_TEXT, 404);

        $post = $this->newGroupPost($posts, auth()->id() . '_' . ($posts->first()->category_id ?: 0) . '_' . time());
        $post->save();
        // новый пост группы получает её общие хештеги, собственных у него пока нет
        $post->hashtags()->sync(Post::commonHashtagIds($posts));

        return response()->json(['status' => true, 'html' => $this->renderGroupItem($post, $groupId, Post::TYPE_TEXT, $posts->count() + 1)]);
    }

    /**
     * Заготовка нового поста в конце группы — с её общими названием, категорией и датой; ещё не сохранена
     *
     * @param \Illuminate\Database\Eloquent\Collection $posts посты группы (groupPosts())
     * @param string $alias
     * @return Post
     */
    private function newGroupPost($posts, string $alias): Post
    {
        $head = $posts->first();

        $post = new Post();
        $post->user_id = auth()->id();
        $post->group_id = $head->group_id;
        $post->sort_order = (int)$posts->max('sort_order') + 1;
        $post->title = (string)$head->title;
        $post->category_id = $head->category_id ?: 0;
        $post->event_date = $head->event_date;
        $post->event_date_precision = $head->event_date_precision;
        $post->is_used = (bool)$head->is_used;
        $post->alias = $this->freeAlias($alias);

        return $post;
    }

    /**
     * Карточка поста для страницы группы — для постов, добавленных без перезагрузки страницы
     *
     * @param Post $post
     * @param int $groupId
     * @param string $kind
     * @param int $position
     * @return string
     */
    private function renderGroupItem(Post $post, $groupId, string $kind, int $position): string
    {
        $this->fillGroupImages($post);

        return view('user.posts.parts.group_item', [
            'post' => $post,
            'groupId' => $groupId,
            'kind' => $kind,
            'position' => $position,
            // посты добавляют только в режиме редактирования
            'editing' => true,
        ])->render();
    }

    /**
     * Убирает пост из группы (сам пост остаётся — с названием и хештегами, какие были у группы), а с ?delete=1 —
     * удаляет его: в корзину, из неё он вернётся на своё место в группе. Если группа после этого сменила ключ
     * или в ней не осталось постов, в ответе redirect — куда уйти
     *
     * @param Request $request
     * @param int $groupId
     * @param int $postId
     * @return \Illuminate\Http\JsonResponse
     */
    public function detachFromGroup(Request $request, $groupId, $postId)
    {
        $post = Post::where('user_id', auth()->id())->where('group_id', $groupId)->findOrFail($postId);

        // ключ группы без этого поста; null — других постов в ней нет
        $newGroupId = Post::groupIdWithout((int)$groupId, (int)$post->id, (int)$post->user_id);

        if ($request->boolean('delete')) {
            $deleted = $this->destroy($post)->getData(true);
            if (empty($deleted['status'])) {
                return response()->json(['status' => false, 'message' => $deleted['error'] ?? 'Ошибка при удалении поста']);
            }
            // пост в корзине остаётся постом группы, ключ у неё прежний — уходить надо, только если она опустела
            if ($newGroupId !== null) {
                $newGroupId = (int)$groupId;
            }
        } else {
            $post->group_id = null;
            $post->sort_order = null;
            $post->save();
            Post::rekeyGroupWithout((int)$groupId, (int)$post->id, (int)$post->user_id);
        }

        $redirect = null;
        if ($newGroupId === null) {
            $redirect = route('posts.index');
        } elseif ($newGroupId !== (int)$groupId) {
            // убирают и удаляют посты только в режиме редактирования — в нём и остаёмся
            $redirect = route('posts.groups.show', [$newGroupId, 'edit' => 1]);
        }

        return response()->json(['status' => true, 'redirect' => $redirect]);
    }

    /**
     * Разгруппировать: посты остаются (с общими названием и хештегами), группы больше нет
     *
     * @param int $groupId
     * @return \Illuminate\Http\JsonResponse
     */
    public function dissolveGroup($groupId)
    {
        abort_unless(Post::where('group_id', $groupId)->where('user_id', auth()->id())->exists(), 404);

        // вместе с постами группы из корзины — восстановленные, они не должны оказаться в группе, которой уже нет.
        // toBase(): не трогаем updated_at — сами посты не менялись
        Post::withTrashed()
            ->where('group_id', $groupId)
            ->where('user_id', auth()->id())
            ->toBase()
            ->update(['group_id' => null, 'sort_order' => null]);

        return response()->json(['status' => true, 'redirect' => route('posts.index')]);
    }

    /**
     * Создаёт группу из одного поста ("Создать группу" в меню карточки) и открывает её страницу —
     * остальные посты добавляют уже там
     *
     * @param Post $post
     * @return \Illuminate\Http\RedirectResponse
     */
    public function startGroup(Post $post)
    {
        if (!$post->group_id) {
            abort_unless($post->groupKind() !== null, 404);

            // группы отдельной таблицей не заводим — id первого поста и есть group_id
            $post->group_id = $post->id;
            $post->sort_order = 0;
            $post->save();
        }

        // новую группу сразу собирают — открываем в режиме редактирования
        return redirect()->route('posts.groups.show', [$post->group_id, 'edit' => 1]);
    }

    /**
     * Адрес страницы группы с якорем на карточку этого поста
     *
     * @param Post $post
     * @param bool $edit открыть группу текстов сразу в режиме редактирования (см. showGroup())
     * @return string
     */
    private function groupPostUrl(Post $post, bool $edit = false): string
    {
        return route('posts.groups.show', [$post->group_id] + ($edit ? ['edit' => 1] : [])) . '#post-' . $post->id;
    }

    /**
     * Сохраняет новый порядок постов внутри группы после drag&drop на странице группы.
     *
     * @param Request $request
     * @param int $groupId
     * @return \Illuminate\Http\JsonResponse
     */
    public function reorderGroup(Request $request, $groupId)
    {
        $user = auth()->user();
        $order = $request->input('order', []);

        if (!is_array($order) || empty($order)) {
            return response()->json(['status' => false, 'message' => 'Некорректный порядок'], 422);
        }

        $posts = Post::where('group_id', $groupId)->where('user_id', $user->id)->get()->keyBy('id');

        foreach (array_values($order) as $index => $postId) {
            if (isset($posts[$postId])) {
                $posts[$postId]->sort_order = $index;
                $posts[$postId]->save();
            }
        }

        return response()->json(['status' => true, 'message' => 'Порядок сохранён']);
    }

    /**
     * Обложка поста (первая/единственная картинка) для карточки в списке группы —
     * та же логика, что у $post['cover_image'] в index(), но как отдельный метод,
     * т.к. тут не нужен полный набор колонок/eager-load, только путь к картинке.
     *
     * @param Post $post
     * @return string|null
     */
    private function resolveCoverImagePath(Post $post): ?string
    {
        return $post->coverImagePath();
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param Post $post
     * @return Application|Factory|View
     */
    public function edit(Post $post)
    {
        // пост группы редактируют на странице группы, см. show()
        if ($post->group_id && $post->groupKind() !== null) {
            return redirect($this->groupPostUrl($post, true));
        }

        $post = Post::where('id', $post->id)
            ->where('posts.user_id', auth()->id())
            ->with([
                'hashtags' => function($q) {
                    $q->select(['id', 'title', 'parent_id', 'user_id']);
                },
                'film',
                'clothes.brand',
            ])->first();

        $categories = PostsCategory::where('user_id', auth()->id())->orderBy('title', 'DESC')->get();

        // приводим original_image к единому виду — коллекции {name: {name, extension, path}},
        // независимо от того, один это файл (старый формат) или несколько
        $returnArray = [
            'categories' => $categories,
            'post' => $post,
            'postImages' => UploadImageController::normalizeImages($post->original_image),
        ];

        return view('user.posts.edit', $returnArray);
    }

    /**
     * Update the specified resource in storage.
     *
     * @param Request $request
     * @param Post $post
     * @return \Illuminate\Http\JsonResponse
     */
    public function update(Request $request, Post $post)
    {
        $addHashtags = [];
        $deleteHashtags = [];

        $user = auth()->user(); //$post->user_id

        //dd($request);

        // "Добавить тег" на карточке-стопке в "Все посты" (group_common=1): на ней показаны общие хештеги группы —
        // их и меняем, собственные хештеги постов группы остаются. С отдельной карточки поста группы
        // хештеги меняются ниже как обычно — только у этого поста (его собственные)
        if (!empty($request->hashtags) && $request->boolean('group_common') && $post->group_id && $post->groupKind() !== null) {
            $requestHashtags = json_decode($request->hashtags, true);
            $commonIds = Hashtag::where('user_id', auth()->id())
                ->whereIn('id', is_array($requestHashtags) ? array_keys($requestHashtags) : [])
                ->pluck('id')
                ->all();
            $members = Post::where('group_id', $post->group_id)->where('user_id', auth()->id())->with('hashtags:id')->get();
            Post::syncGroupHashtags($members, $commonIds);

            return response()->json([
                'status' => true,
                'message' => 'Хештеги группы обновлены',
                'type' => $post->type,
                'type_label' => $post->typeLabel(),
            ]);
        }

        if (!empty($request->hashtags)) {

            //dd($request->hashtags);

            //$this->addHashtagsToPost($new_post->getAttribute('id'), array_keys($requestHashtags));

            $requestHashtags = json_decode($request->hashtags, true);

            if (is_array($requestHashtags)) {
                $postHashtags = [];

                if (!empty($post->hashtags)) {
                    foreach ($post->hashtags as $hashtag) {
                        $postHashtags[] = $hashtag->id;

                        if (!in_array($hashtag->id, array_keys($requestHashtags))) {
                            //удаляем хештег у поста
                            $deleteHashtags[] = $hashtag->id;
                        }
                    }
                }

                foreach ($requestHashtags as $id => $requestHashtag) {
                    if (!in_array($id, $postHashtags)) {
                        //добавляем хештег к посту
                        $addHashtags[] = (int)$id;
                    }
                }
            }
        }

        if (!empty($request->title)) {
            $post->title = $request->title;
        }

        if ($request->has('category_id')) {
            // "" (категория не выбрана) — сохраняем как 0, а не пропускаем, как было раньше
            // (иначе выбор "-" на форме не давал реально снять категорию)
            $post->category_id = $request->category_id ?: 0;
        }

        if (!empty($request->alias)) {
            $post->alias = $request->alias;
        }

        // has(), а не !empty(): иначе текст поста нельзя стереть целиком
        if ($request->has('content')) {
            $post->content = $request->content;
        }

        // переключатель типа на форме: фильм/рецепт — как выбрали, "auto" — тип по содержимому
        // (сбрасываем, модель определит его при сохранении, см. Post::booted)
        if ($request->has('post_type')) {
            $post->type = in_array($request->input('post_type'), Post::MANUAL_TYPES, true)
                ? $request->input('post_type')
                : null;
        }

        // дата пина (поле "Дата"); пусто — даты нет. Не дата — ничего не сохраняем, а говорим об этом
        if ($request->has('event_date') && !$post->setEventDateFromInput($request->input('event_date'))) {
            return response()->json(['status' => false, 'message' => self::EVENT_DATE_ERROR, 'errors' => self::EVENT_DATE_ERROR]);
        }

        // Добавление/замена/переименование/удаление изображений поста больше не идёт через
        // этот общий save — теперь это отдельные немедленные AJAX-действия панели
        // "Изображения поста" (см. PostController::addImage(), ::renameImage(), ::deletePostFile()).

        $postSave = $post->save();

        if ($postSave === true) {
            // поля фильма есть на форме редактирования всегда (parts/film_fields.blade.php, скрытый film-pros
            // приходит и при пустых полях), но видны только у типа "Фильм / Сериал": у него всё очищено —
            // данные фильма удаляем; пост перестал быть фильмом — тоже (форма предупреждает об этом)
            if ($post->type === Post::TYPE_FILM) {
                if ($request->has('film-pros')) {
                    PostFilm::saveForPost($post, PostFilm::attributesFromRequest($request));
                }
            } elseif ($post->film) {
                PostFilm::saveForPost($post, null);
            }

            // поля вещи — так же: сохраняем только у типа "Одежда" (скрытый clothes-fields — блок был на форме),
            // пост перестал быть одеждой — данные вещи удаляем
            if ($post->type === Post::TYPE_CLOTHES) {
                if ($request->has('clothes-fields')) {
                    PostClothes::saveForPost($post, PostClothes::attributesFromRequest($request, (int)$post->user_id));
                }
            } elseif ($post->clothes) {
                PostClothes::saveForPost($post, null);
            }

            if (!empty($addHashtags)) {
                $this->addHashtagsToPost($post->id, $addHashtags);
            }
            if (!empty($deleteHashtags)) {
                $this->removeHashtagsFromPost($post->id, $deleteHashtags);
            }

            return response()->json([
                'status' => true,
                'message' => 'Пост был успешно обновлен',
                'type' => $post->type,
                'type_label' => $post->typeLabel(),
            ]);
        }

        return response()->json([
            'errors' => 'Не удалось обновить пост',
        ]);
    }

    /**
     * Добавляет хештеги сразу нескольким постам — режим "Выбрать" на "Все посты" (/admin/posts-bulk.js).
     * Только добавляет: хештеги, которые у постов уже есть, остаются. Отмеченная карточка-стопка — это вся
     * группа (её id приходит в group_ids): хештеги получают все её посты, то есть они становятся общими.
     * Отмеченная отдельная карточка поста группы (снята галочка "Свернуть группы") — хештеги только этому посту
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function bulkAddHashtags(Request $request)
    {
        $data = $request->validate([
            'ids' => ['required', 'array', 'max:2000'],
            'ids.*' => ['integer'],
            'group_ids' => ['nullable', 'array', 'max:2000'],
            'group_ids.*' => ['integer'],
            // как на форме поста: JSON {id: название}
            'hashtags' => ['required', 'string'],
        ]);

        $requestHashtags = json_decode($data['hashtags'], true);
        $hashtags = Hashtag::where('user_id', auth()->id())
            ->whereIn('id', is_array($requestHashtags) ? array_keys($requestHashtags) : [])
            ->get(['id', 'title']);

        if ($hashtags->isEmpty()) {
            return response()->json(['status' => false, 'message' => 'Не выбрано ни одного хештега'], 422);
        }

        $posts = Post::where('user_id', auth()->id())->whereIn('id', $data['ids'])->get(['id', 'type', 'group_id']);

        $stackGroupIds = array_map('intval', $data['group_ids'] ?? []);
        $groupIds = $posts->filter(fn (Post $post) => $post->group_id && $post->groupKind() !== null && in_array((int)$post->group_id, $stackGroupIds, true))
            ->pluck('group_id')
            ->unique();
        if ($groupIds->isNotEmpty()) {
            $posts = $posts->concat(
                Post::where('user_id', auth()->id())->whereIn('group_id', $groupIds)->whereNotIn('id', $posts->pluck('id'))->get(['id', 'type', 'group_id'])
            );
        }

        foreach ($posts as $post) {
            $post->hashtags()->syncWithoutDetaching($hashtags->pluck('id')->all());
        }

        return response()->json([
            'status' => true,
            'message' => 'Хештеги добавлены',
            // все посты, у которых могли появиться хештеги (вместе с постами групп) — обновить карточки
            'post_ids' => $posts->pluck('id')->values(),
            'hashtags' => $hashtags->map(fn (Hashtag $hashtag) => ['id' => $hashtag->id, 'title' => $hashtag->title])->values(),
        ]);
    }

    /**
     * Ставит или снимает отметку "Использовано" (posts.is_used) у одного или нескольких постов: пункт меню
     * карточки и панель режима "Выбрать" на "Все посты" (/admin/posts-bulk.js), кнопка на странице группы
     * (/admin/post-group.js). У группы отметка одна на всех — её получают все посты группы
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function bulkSetUsed(Request $request)
    {
        $data = $request->validate([
            'ids' => ['required', 'array', 'max:2000'],
            'ids.*' => ['integer'],
            'used' => ['required', 'boolean'],
        ]);

        $posts = Post::where('user_id', auth()->id())->whereIn('id', $data['ids'])->get(['id', 'type', 'group_id']);
        $groupIds = $posts->filter(fn (Post $post) => $post->group_id && $post->groupKind() !== null)->pluck('group_id')->unique();

        $query = Post::where('user_id', auth()->id())
            ->where(function ($q) use ($posts, $groupIds) {
                $q->whereIn('id', $posts->pluck('id'))->orWhereIn('group_id', $groupIds);
            });

        $postIds = (clone $query)->pluck('id');
        // toBase(): отметка — не правка поста, updated_at не трогаем
        $query->toBase()->update(['is_used' => (bool)$data['used']]);

        return response()->json([
            'status' => true,
            'used' => (bool)$data['used'],
            // все посты, у которых отметка изменилась (вместе с постами групп) — обновить карточки
            'post_ids' => $postIds->values(),
        ]);
    }

    /**
     * Удаляет сразу несколько постов — в корзину; режим "Выбрать" на "Все посты" (/admin/posts-bulk.js).
     * Отмеченная карточка-стопка — это вся группа: её посты приходят в group_ids и удаляются все
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function bulkDelete(Request $request)
    {
        $data = $request->validate([
            'ids' => ['required', 'array', 'max:2000'],
            'ids.*' => ['integer'],
            'group_ids' => ['nullable', 'array', 'max:2000'],
            'group_ids.*' => ['integer'],
        ]);

        $posts = Post::where('user_id', auth()->id())
            ->where(function ($q) use ($data) {
                $q->whereIn('id', $data['ids'])->orWhereIn('group_id', $data['group_ids'] ?? []);
            })
            ->get();

        // по одному, а не одним запросом: у удаления есть обработчики в модели (Post::booted)
        foreach ($posts as $post) {
            $post->delete();
        }

        return response()->json([
            'status' => true,
            'deleted' => $posts->count(),
            'post_ids' => $posts->pluck('id')->values(),
            'trash_count' => Post::onlyTrashed()->where('user_id', auth()->id())->count(),
        ]);
    }

    /**
     * Корзина: удалённые посты (PostTrash) — вернуть или удалить навсегда. Вьюха — user/posts/trash.blade.php,
     * поведение — /admin/posts-trash.js
     *
     * @return Application|Factory|View
     */
    public function trash()
    {
        // Посты одной группы в корзине — одна плитка-стопка, как группа в ленте: показываем первый из них
        // (по порядку в группе), на плитке — сколько их и срок того, который удалится раньше всех
        $trashedGroups = Post::onlyTrashed()
            ->where('user_id', auth()->id())
            ->whereNotNull('group_id')
            ->orderBy('sort_order')
            ->orderBy('id')
            ->get(['id', 'group_id', 'deleted_at'])
            ->groupBy('group_id');

        $posts = Post::onlyTrashed()
            ->where('user_id', auth()->id())
            ->where(function ($q) use ($trashedGroups) {
                $q->whereNull('group_id')->orWhereIn('id', $trashedGroups->map->first()->pluck('id'));
            })
            ->orderByDesc('deleted_at')
            ->orderByDesc('id')
            ->paginate(60);

        foreach ($posts as $post) {
            $members = $post->group_id ? $trashedGroups[$post->group_id] : collect([$post]);

            $post->cover_image = $post->coverImagePath();
            $post->days_left = PostTrash::daysLeftSince($members->min('deleted_at'));
            // 0 — пост не из группы; 1 — из группы, но в корзине он один (остальные её посты на месте)
            $post->group_trashed = $post->group_id ? $members->count() : 0;
        }

        return view('user.posts.trash', [
            'posts' => $posts,
            'keepDays' => PostTrash::KEEP_DAYS,
        ]);
    }

    /**
     * Возвращает из корзины все посты пользователя (кнопка "Вернуть всё")
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function restoreAllTrashed()
    {
        $restored = $this->restorePosts(Post::onlyTrashed()->where('user_id', auth()->id())->get());

        return response()->json(['status' => true, 'restored' => $restored]);
    }

    /**
     * Возвращает из корзины все посты одной группы (кнопка "Вернуть группу" на плитке её поста)
     *
     * @param int $groupId
     * @return \Illuminate\Http\JsonResponse
     */
    public function restoreTrashedGroup($groupId)
    {
        $posts = Post::onlyTrashed()->where('user_id', auth()->id())->where('group_id', $groupId)->get();
        abort_if($posts->isEmpty(), 404);

        return response()->json(['status' => true, 'restored' => $this->restorePosts($posts), 'message' => 'Группа восстановлена']);
    }

    /**
     * Возвращает посты из корзины. Пост группы возвращается в неё на прежнее место; если в группе больше
     * никого, даже в корзине (остальные её посты удалены навсегда) — становится обычным постом
     *
     * @param iterable $posts посты из корзины
     * @return int сколько постов возвращено
     */
    private function restorePosts(iterable $posts): int
    {
        $restored = 0;

        // сначала все: посты одной группы должны "увидеть" друг друга при проверке ниже
        foreach ($posts as $post) {
            $post->restore();
            $restored++;
        }

        foreach ($posts as $post) {
            if ($post->group_id && !Post::withTrashed()->where('group_id', $post->group_id)->where('id', '!=', $post->id)->exists()) {
                $post->group_id = null;
                $post->sort_order = null;
                $post->save();
            }
        }

        return $restored;
    }

    /**
     * Возвращает пост из корзины. Пост группы возвращается в неё на прежнее место; если группы уже нет
     * (её разгруппировали или остальные посты удалили) — становится обычным постом
     *
     * @param int $postId
     * @return \Illuminate\Http\JsonResponse
     */
    public function restoreTrashed($postId)
    {
        $this->restorePosts([Post::onlyTrashed()->where('user_id', auth()->id())->findOrFail($postId)]);

        return response()->json(['status' => true, 'message' => 'Пост восстановлен']);
    }

    /**
     * Удаляет пост из корзины навсегда — вместе с файлами
     *
     * @param int $postId
     * @return \Illuminate\Http\JsonResponse
     */
    public function purgeTrashed($postId)
    {
        $post = Post::onlyTrashed()->where('user_id', auth()->id())->findOrFail($postId);

        try {
            PostTrash::purge($post);
        } catch (\Throwable $e) {
            report($e);

            return response()->json(['status' => false, 'message' => 'Не удалось удалить пост']);
        }

        return response()->json(['status' => true, 'message' => 'Пост удалён навсегда']);
    }

    /**
     * Удаляет навсегда все посты группы, лежащие в корзине (плитка-стопка на странице корзины)
     *
     * @param int $groupId
     * @return \Illuminate\Http\JsonResponse
     */
    public function purgeTrashedGroup($groupId)
    {
        $posts = Post::onlyTrashed()->where('user_id', auth()->id())->where('group_id', $groupId)->orderBy('id')->get();
        abort_if($posts->isEmpty(), 404);

        $failed = 0;
        foreach ($posts as $post) {
            try {
                PostTrash::purge($post);
            } catch (\Throwable $e) {
                report($e);
                $failed++;
            }
        }

        return response()->json([
            'status' => $failed === 0,
            'message' => $failed === 0 ? 'Группа удалена навсегда' : 'Не удалось удалить постов: ' . $failed,
        ]);
    }

    /**
     * Очищает корзину: все посты в ней удаляются навсегда
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function emptyTrash()
    {
        $failed = 0;

        foreach (Post::onlyTrashed()->where('user_id', auth()->id())->orderBy('id')->get() as $post) {
            try {
                PostTrash::purge($post);
            } catch (\Throwable $e) {
                report($e);
                $failed++;
            }
        }

        return response()->json([
            'status' => $failed === 0,
            'message' => $failed === 0 ? 'Корзина очищена' : 'Не удалось удалить постов: ' . $failed,
        ]);
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param Post $post
     * @return \Illuminate\Http\JsonResponse
     */
    public function destroy(Post $post)
    {
        try {
            // в корзину (SoftDeletes): картинки, видео, хештеги, данные фильма/вещи остаются — пост можно вернуть.
            // Удаление навсегда — со страницы корзины или само через PostTrash::KEEP_DAYS дней, см. PostTrash::purge()
            if ($post->delete() === true) {
                return response()->json([
                    'status' => true,
                    'message' => 'Пост перемещён в корзину',
                ]);
            }

            return response()->json([
                'status' => false,
                'error' => 'Ошибка при удалении поста.',
            ]);

        } catch (\Exception $exception) {
            //TODO - Добавить запись ошибок в логи в БД

            return response()->json([
                'status' => false,
                'error' => 'Ошибка при удалении поста',
            ]);
        }
    }

    /**
     * @param Request $request
     * @param string $userId
     * @param array $image
     * @param string $imageAlias
     * @param int|null $groupId группа постов ("Группировать посты"), null — без группы
     * @param int|null $sortOrder порядок внутри группы, null — без группы
     * @param int|null $createdId ссылка-out: id только что созданного поста (для сборки группы в store())
     * @return array|bool|bool[]|string|string[]
     */
    private function createPost(Request $request, string $userId, array $image = [], string $imageAlias = '', ?int $groupId = null, ?int $sortOrder = null, ?int &$createdId = null)
    {
        //TODO описать что происходит в методе

        //dd($request->hashtags);

        $title = '';
        // хеши сохранённых изображений — записываем в image_hashes уже после того,
        // как известен id поста (после $new_post->save())
        $imageHashesToStore = [];
        // ключи (image_name) изображений, для которых пользователь уже подтвердил
        // "это не дубликат, добавить всё равно" при повторной отправке формы
        // (см. PostController::store() и JS-обработчик #creationform)
        $forceDuplicateKeys = json_decode($request->force_duplicate_keys ?? '[]', true) ?: [];
        // картинки, про которые при загрузке сказали "это разные" с похожей картинкой этой же формы (окно
        // #modal-form-duplicate, admin.js): иначе вторая из них споткнулась бы о первую, уже сохранённую в пост
        // этой же отправки. С картинками постов, что были до неё, их уже сравнил findDuplicatesBeforeCreate()
        $forceDuplicateKeys = array_merge($forceDuplicateKeys, json_decode($request->form_distinct_keys ?? '[]', true) ?: []);
        // {image_name: {post_id, image_name}} — у этих картинок лучшая копия идёт в новый пост,
        // а старую копию из другого поста удаляем, когда новый пост сохранён (окно сравнения
        // дубликатов в режиме карусели, см. admin.js)
        $replaceDuplicates = json_decode($request->replace_duplicates ?? '{}', true) ?: [];
        $savedImageKeys = [];

        $categoryId = $request->category_id ?? 0;
        $categoryParentId = (new PostsCategory)->getCategoryParentId($categoryId);

        $new_post = new Post();
        $new_post->user_id = $userId;
        $new_post->category_id = $request->category_id ?: 0;

        // фильм/рецепт — как выбрали на форме; остальные типы модель определит по содержимому (Post::booted)
        if (in_array($request->input('pin_type'), Post::MANUAL_TYPES, true)) {
            $new_post->type = $request->input('pin_type');
        }

        // дата пина — одна на все посты этой отправки формы (проверена в store())
        $new_post->setEventDateFromInput($request->input('event_date'));

        if ($groupId !== null) {
            $new_post->group_id = $groupId;
        }
        if ($sortOrder !== null) {
            $new_post->sort_order = $sortOrder;
        }

        if (isset($request->text) && !empty(strip_tags($request->text))) {
            $new_post->content = $request->text;
        }

        // видео поста — независимо от картинок, см. saveVideoForPost()
        if (!empty($request->video)) {
            $videoData = json_decode($request->video, true);
            if (is_array($videoData)) {
                $savedVideo = $this->saveVideoForPost($videoData, $userId);
                if ($savedVideo) {
                    $new_post->video = json_encode($savedVideo);
                }
            }
        }

        if (!empty($image) && !isset($image['image_path']) && !empty($request->numberOfPosts) && $request->numberOfPosts === 'one-post') {
            //dd('ssss');
            $errors = [];
            $duplicateInfos = [];
            $originalImages = [];
            $mediumImages = [];
            $smallImages = [];
            $lastImage = [];

            //var_dump($image);
            //var_dump('---------------');

            foreach ($image as $key => $img) {
                //dd($img);
                //array:7 [
                //  "image_name" => "243283257-579176736665198-7143984547937799899-n"
                //  "image_extension" => "jpg"
                //  "image_path" => "temp_directory/243283257-579176736665198-7143984547937799899-n.jpg"
                //  "s_image_name" => "243283257-579176736665198-7143984547937799899-n_350_435.jpg"
                //  "s_image_path" => "temp_directory/243283257-579176736665198-7143984547937799899-n_350_435.jpg"
                //  "m_image_name" => "243283257-579176736665198-7143984547937799899-n_800_994.jpg"
                //  "m_image_path" => "temp_directory/243283257-579176736665198-7143984547937799899-n_800_994.jpg"
                //]

                $forceThisImage = !empty($img['image_name']) && in_array($img['image_name'], $forceDuplicateKeys, true);
                $saveImageForPost = (new UploadImageController)->saveImageForPost($img, $userId, $categoryId, false, $forceThisImage);

                $lastImage = $saveImageForPost;
                //dd($saveImageForPost);

                if (!empty($saveImageForPost['errors'])) {
                    $errors[] = $saveImageForPost['errors'];
                    $duplicateInfos[] = [
                        'image_key' => $img['image_name'] ?? null,
                        'duplicate' => $this->formatDuplicateForFrontend($saveImageForPost['duplicate'] ?? null),
                        'staged_image' => $saveImageForPost['staged_image'] ?? $img,
                    ];
                } elseif (!empty($saveImageForPost)) {
                    $savedImageKeys[] = $img['image_name'] ?? null;
                    $originalImages[$saveImageForPost['original_image']['name']] = $saveImageForPost['original_image'];
                    if (!empty($saveImageForPost['medium_image'])) {
                        $mediumImages[$saveImageForPost['medium_image']['name']] = $saveImageForPost['medium_image'];
                    }
                    if (!empty($saveImageForPost['small_image'])) {
                        $smallImages[$saveImageForPost['small_image']['name']] = $saveImageForPost['small_image'];
                    }
                    if (!empty($saveImageForPost['phash'])) {
                        $imageHashesToStore[] = [
                            'name' => $saveImageForPost['original_image']['name'],
                            'path' => $saveImageForPost['original_image']['path'],
                            'width' => $saveImageForPost['width'],
                            'height' => $saveImageForPost['height'],
                            'phash' => $saveImageForPost['phash'],
                            'perceptual_hash' => $saveImageForPost['perceptual_hash'] ?? null,
                        ];
                    }
                }

                //var_dump($originalImages);
            }

            //dd($originalImages);
            // array:2 [
            //  "7b55e55ab8be71ed1ebfa4330c" => array:3 [
            //    "name" => "7b55e55ab8be71ed1ebfa4330c"
            //    "extension" => "jpg"
            //    "path" => "/images/1/0/0_0_7b55e55ab8be71ed1ebfa4330c.jpg"
            //  ]
            //  "6007c754f8969269fca41b1220" => array:3 [
            //    "name" => "6007c754f8969269fca41b1220"
            //    "extension" => "jpg"
            //    "path" => "/images/1/0/0_0_6007c754f8969269fca41b1220.jpg"
            //  ]
            //]

            if (!empty($errors)) {
                return ['errors' => $errors, 'duplicate_infos' => $duplicateInfos];
            } else {
                $new_post->original_image = json_encode($originalImages);
                $new_post->medium_image = json_encode($mediumImages);
                $new_post->small_image = json_encode($smallImages);
            }

            if (!empty($request->title)) {
                $title = $request->title;
                $alias = str_slug($request->title);
            } else {
                $alias = $userId . '_' . $categoryId . '_' . time();
            }

            // elseif (!empty($lastImage['original_image']['name'])) {
            //     $alias = $userId . '_' . $categoryId . '_' . $lastImage['original_image']['name'];
            // }

            $new_post->title = $title;
            $new_post->alias = $this->freeAlias($alias);

        } else {
            //dd('tttt');
            if (!empty($image)) {
                $forceThisImage = !empty($image['image_name']) && in_array($image['image_name'], $forceDuplicateKeys, true);
                $saveImageForPost = (new UploadImageController)->saveImageForPost($image, $userId, $categoryId, false, $forceThisImage);
                //dd($saveImageForPost);
                if (!empty($saveImageForPost['errors'])) {
                    return [
                        'errors' => $saveImageForPost['errors'],
                        'duplicate_infos' => [[
                            'image_key' => $image['image_name'] ?? null,
                            'duplicate' => $this->formatDuplicateForFrontend($saveImageForPost['duplicate'] ?? null),
                            'staged_image' => $saveImageForPost['staged_image'] ?? $image,
                        ]],
                    ];
                } else {
                    $savedImageKeys[] = $image['image_name'] ?? null;
                    $new_post->original_image = json_encode($saveImageForPost['original_image']);
                    if (!empty($saveImageForPost['medium_image'])) {
                        $new_post->medium_image = json_encode($saveImageForPost['medium_image']);
                    }
                    if (!empty($saveImageForPost['small_image'])) {
                        $new_post->small_image = json_encode($saveImageForPost['small_image']);
                    }
                    if (!empty($saveImageForPost['phash'])) {
                        $imageHashesToStore[] = [
                            'name' => $saveImageForPost['original_image']['name'],
                            'path' => $saveImageForPost['original_image']['path'],
                            'width' => $saveImageForPost['width'],
                            'height' => $saveImageForPost['height'],
                            'phash' => $saveImageForPost['phash'],
                            'perceptual_hash' => $saveImageForPost['perceptual_hash'] ?? null,
                        ];
                    }
                }
            }

            //dd($saveImageForPost);

            if (!empty($request->title)) {
                $title = $request->title;
                $alias = str_slug($request->title);
            } elseif (!empty($saveImageForPost['original_image']['name'])) {
                $alias = $userId . '_' . $categoryId . '_' . $saveImageForPost['original_image']['name'];
            } else {
                $alias = $userId . '_' . $categoryId . '_' . time();
            }

            $new_post->title = $title;
            $new_post->alias = $this->freeAlias($alias);
        }

        $newPost = $new_post->save();

        if ($newPost === true) {
            if ($request->hashtags) {
                $requestHashtags = json_decode($request->hashtags, true);
                $this->addHashtagsToPost($new_post->getAttribute('id'), array_keys($requestHashtags));
            }

            foreach ($imageHashesToStore as $imageHash) {
                UploadImageController::storeImageHash(
                    $new_post->id,
                    $imageHash['name'],
                    $imageHash['path'],
                    $imageHash['width'],
                    $imageHash['height'],
                    $imageHash['phash'],
                    $imageHash['perceptual_hash']
                );
            }

            foreach ($savedImageKeys as $imageKey) {
                $replace = $replaceDuplicates[$imageKey] ?? null;
                if (empty($replace['post_id']) || empty($replace['image_name'])) {
                    continue;
                }
                $oldPost = Post::where('id', $replace['post_id'])->where('user_id', $userId)->first();
                if ($oldPost) {
                    (new UploadImageController)->swapPostImage($oldPost, (string)$replace['image_name'], null);
                }
            }

            // данные фильма — только для типа пина "Фильм / Сериал": у остальных типов блок полей
            // скрыт, но парсер мог его заполнить
            if ($request->input('pin_type') === 'film') {
                $filmAttributes = PostFilm::attributesFromRequest($request);
                if ($filmAttributes !== null) {
                    // хештеги из полей фильма (жанры, год...) здесь не добавляем: форма уже положила их
                    // в выбранные (film-fields.js) и прислала в hashtags вместе с остальными
                    PostFilm::saveForPost($new_post, $filmAttributes);
                }
            }

            // данные вещи — только для типа "Одежда"
            if ($request->input('pin_type') === Post::TYPE_CLOTHES) {
                PostClothes::saveForPost($new_post, PostClothes::attributesFromRequest($request, (int)$userId));
            }

            $createdId = $new_post->id;

            return true;
        }

        return false;
    }

//    public function updatePostsHashtags(Request $request)
//    {
//        if ($request->ajax() && !empty($request->hashtags)) {
//            $requestHashtags = json_decode($request->hashtags);
//
//            if (is_array($requestHashtags)) {
//                $postHashtags = [];
//
//                if (!empty($post->hashtags)) {
//                    foreach ($post->hashtags as $hashtag) {
//                        $postHashtags[] = $hashtag->id;
//
//                        if (!in_array($hashtag->id, $requestHashtags)) {
//                            //удаляем хештег у поста
//                            $deleteHashtags[] = $hashtag->id;
//                        }
//                    }
//                }
//
//                foreach ($requestHashtags as $requestHashtag) {
//                    if (!in_array($requestHashtag, $postHashtags)) {
//                        //добавляем хештег к посту
//                        $addHashtags[] = (int)$requestHashtag;
//                    }
//                }
//            }
//
//            if (!empty($addHashtags)) {
//                $this->addHashtagsToPost($post->id, $addHashtags);
//            }
//            if (!empty($deleteHashtags)) {
//                $this->removeHashtagsFromPost($post->id, $deleteHashtags);
//            }
//
//            return response()->json([
//                'status' => true,
//                'message' => 'Хештеги были успешно добавлены к посту',
//            ]);
//        }
//
//        return response()->json([
//            'status' => false,
//            'message' => 'Хештеги не были добавлены к посту',
//        ]);
//    }

    /**
     * @param $postId
     * @param array $hashtagsIds
     */
    private function addHashtagsToPost($postId, array $hashtagsIds)
    {
        $post = Post::where('id', $postId)->where('user_id', auth()->id())->firstOrFail();
        $hashtags = Hashtag::where('user_id', auth()->id())->whereIn('id', $hashtagsIds)->get();
        $post->hashtags()->attach($hashtags);
    }

    /**
     * @param $postId
     * @param array $hashtagsIds
     * @return void
     */
    private function removeHashtagsFromPost($postId, array $hashtagsIds)
    {
        $post = Post::where('id', $postId)->where('user_id', auth()->id())->firstOrFail();
        $hashtags = Hashtag::where('user_id', auth()->id())->whereIn('id', $hashtagsIds)->get();
        $post->hashtags()->detach($hashtags);
    }

    public function getParsedPostInfo(Request $request)
    {
        $result = [];

        if ($request->ajax() && !empty($request->link)) {
            //TODO Добавить определение вызываемого класса по link
            $parser = $this->getParser($request->link);
            //dd($parser);
            //$parser = new SweetTvParser($request->link);
            $result = $parser->parse();

            // дата публикации страницы — форма подставит её в поле "Дата" пина (event-date.js)
            if (is_array($result) && empty($result['published_date'])) {
                $publishedDate = $parser->getPublishedDate();
                if ($publishedDate !== null) {
                    $result['published_date'] = $publishedDate;
                }
            }
        }

        return $result;
    }

    /**
     * Кнопка "Считать текст с изображения" на странице создания поста: распознаёт текст на
     * картинках, уже загруженных во временную папку пользователя (temp_directory/{user_id}/),
     * и отдаёт его JSON-ом — фронт вставляет в редактор. Принимает image_paths[] — пути из
     * ответа загрузки (image_path, например "temp_directory/1/foo.jpg"); за пределы своей
     * временной папки выйти нельзя.
     */
    public function getTextFromImage(Request $request)
    {
        $request->validate([
            'image_paths' => 'required|array|min:1|max:10',
            'image_paths.*' => 'string|max:500',
        ]);

        $userDir = 'temp_directory/' . auth()->id() . '/';
        $disk = Storage::disk('local');
        $reader = new ImageReader();
        $texts = [];

        foreach ($request->input('image_paths') as $path) {
            $path = ltrim(str_replace('\\', '/', $path), '/');

            if (strpos($path, $userDir) !== 0 || strpos($path, '..') !== false || !$disk->exists($path)) {
                return response()->json(['status' => false, 'message' => 'Изображение не найдено. Загрузите его заново.']);
            }

            try {
                $texts[] = $reader->recognizeText($this->prepareImageForOcr($disk->get($path)));
            } catch (\Throwable $e) {
                \Illuminate\Support\Facades\Log::error('ImageReader::recognizeText failed: ' . $e->getMessage());

                if (strpos($e->getMessage(), 'отключена администратором') !== false) {
                    $message = 'Распознавание текста сейчас отключено администратором.';
                } elseif (strpos($e->getMessage(), 'OCR_SPACE_API_KEY') !== false) {
                    $message = 'Распознавание текста не настроено: нет ключа OCR.space.';
                } else {
                    $message = 'Не удалось распознать текст на изображении.';
                }

                return response()->json(['status' => false, 'message' => $message]);
            }
        }

        $text = trim(implode("\n\n", array_filter($texts, function ($text) { return $text !== ''; })));

        if ($text === '') {
            return response()->json(['status' => false, 'message' => 'Текст на изображении не найден.']);
        }

        return response()->json(['status' => true, 'text' => $text]);
    }

    /**
     * Кнопка "Подобрать хештеги" на странице создания поста: подсказки из существующих хештегов
     * пользователя по картинкам, уже загруженным во временную папку (см. HashtagSuggester).
     * Принимает image_paths[] — как getTextFromImage() — и selected_ids[] — уже выбранные теги,
     * их не предлагаем.
     */
    public function suggestHashtags(Request $request)
    {
        $request->validate([
            'image_paths' => 'required|array|min:1|max:10',
            'image_paths.*' => 'string|max:500',
            'selected_ids' => 'nullable|array',
            'selected_ids.*' => 'integer',
        ]);

        $userDir = 'temp_directory/' . auth()->id() . '/';
        $disk = Storage::disk('local');
        $absolutePaths = [];

        foreach ($request->input('image_paths') as $path) {
            $path = ltrim(str_replace('\\', '/', $path), '/');

            if (strpos($path, $userDir) !== 0 || strpos($path, '..') !== false || !$disk->exists($path)) {
                return response()->json(['status' => false, 'message' => 'Изображение не найдено. Загрузите его заново.']);
            }

            $absolutePaths[] = $disk->path($path);
        }

        return $this->hashtagSuggestionsResponse($absolutePaths, $request->input('selected_ids', []));
    }

    /**
     * То же для уже сохранённого поста (страница редактирования): картинки берутся из самого поста
     * (средние копии, если есть, — их хватает и они легче), selected_ids[] — теги поста в форме
     */
    public function suggestHashtagsForPost(Request $request, Post $post)
    {
        $request->validate([
            'selected_ids' => 'nullable|array',
            'selected_ids.*' => 'integer',
        ]);

        $images = UploadImageController::normalizeImages($post->medium_image)
            ?: UploadImageController::normalizeImages($post->original_image);

        $disk = Storage::disk('local');
        $absolutePaths = [];
        foreach (array_slice($images, 0, 10) as $image) {
            if (!empty($image['path']) && $disk->exists($image['path'])) {
                $absolutePaths[] = $disk->path($image['path']);
            }
        }

        if (!$absolutePaths) {
            return response()->json(['status' => false, 'message' => 'У поста нет изображений.']);
        }

        return $this->hashtagSuggestionsResponse($absolutePaths, $request->input('selected_ids', []));
    }

    private function hashtagSuggestionsResponse(array $absolutePaths, array $selectedIds)
    {
        $suggester = new HashtagSuggester();
        $hashtags = $suggester->suggest($absolutePaths, auth()->id(), array_map('intval', $selectedIds));

        return response()->json([
            'status' => true,
            'hashtags' => $hashtags,
            'warning' => $suggester->aiError(),
            // режим "Добавлять сразу" из Настроек ИИ — admin.js ставит теги в выбранные без клика
            'auto_apply' => UserAiSetting::forUser(auth()->id())->autoApplyTags(),
        ]);
    }

    /**
     * Бесплатный ключ OCR.space принимает файлы до 1 МБ — большие оригиналы заранее
     * уменьшаем и пережимаем в JPEG (для распознавания хватает).
     */
    private function prepareImageForOcr(string $bytes): string
    {
        if (strlen($bytes) <= 1000000) {
            return $bytes;
        }

        return (string) \Intervention\Image\Facades\Image::make($bytes)
            ->resize(1800, 1800, function ($constraint) {
                $constraint->aspectRatio();
                $constraint->upsize();
            })
            ->encode('jpg', 80);
    }

    public function deletePostFile(Request $request)
    {
        try {
            //получаем пост
            if ($request->ajax() && !empty($request->postId) && !empty($request->name) && !empty($request->path)) {

                $user = auth()->user();
                $post = Post::where('id', $request->postId)->where('posts.user_id', $user->id)->first();

                $deletePostImages = (new UploadImageController)->deletePostImages($post, $request->name);

                if ($deletePostImages !== true) {
                    $errorMessage = 'Ошибка при удалении изображения: ' . $request->name;
                    if (!empty($deletePostImages['errors'])) {
                        throw new \Exception($errorMessage . '. ' . $deletePostImages['errors']);
                    }

                    throw new \Exception($errorMessage);
                }

                UploadImageController::deleteImageHash($post->id, $request->name);

                return response()->json([
                    'status' => true,
                    'message' => 'Изображение ' . $request->name . ' успешно удалено',
                ]);
            }

        } catch (\Exception $e) {
            return response()->json(['status' => false, 'msg' => $e->getMessage()]);
        }
    }

    /**
     * Добавить/заменить видео поста (страница редактирования, панель "Видео поста").
     * У поста только одно видео — если уже есть, старый файл удаляется.
     *
     * @param Request $request
     * @param Post $post
     * @return \Illuminate\Http\JsonResponse
     */
    public function addVideo(Request $request, Post $post)
    {
        try {
            $request->validate([
                'video' => 'required|file|mimes:mp4,mov,webm,avi,mkv,m4v|max:512000',
            ]);

            $this->deletePostVideoFile($post);

            $file = $request->file('video');
            $extension = strtolower($file->getClientOriginalExtension()) ?: 'mp4';
            $name = \Illuminate\Support\Str::random(20);
            $dir = 'videos/' . auth()->id() . '/' . now()->format('Y-m-d');

            Storage::disk('local')->makeDirectory($dir);
            $file->storeAs($dir, $name . '.' . $extension, 'local');

            $video = [
                'name' => $name,
                'extension' => $extension,
                'path' => '/' . $dir . '/' . $name . '.' . $extension,
            ];

            $post->video = json_encode($video);
            $post->save();

            return response()->json([
                'status' => true,
                'message' => 'Видео добавлено',
                'video' => $video,
            ]);
        } catch (\Exception $e) {
            return response()->json(['status' => false, 'message' => $e->getMessage()]);
        }
    }

    /**
     * Сохранить палитру, поправленную вручную (страница редактирования, блок "Палитра", palette-editor.js):
     * цвета в нужном порядке (первый — главный), у каждого — фон или нет. Доли цветов считаем заново
     * по обложке (ImagePalette::sharesFor): так у цвета, добавленного пипеткой, настоящая доля, а удалённый
     * фон не раздувает остальные. После этого палитра не пересчитывается сама, пока не сменится обложка.
     *
     * @param Request $request colors: [{hex: "#D8C3A5", background: false}, ...]
     * @param Post $post
     * @return \Illuminate\Http\JsonResponse
     */
    public function updatePalette(Request $request, Post $post)
    {
        $request->validate([
            'colors' => 'required|array|min:1|max:8',
            'colors.*.hex' => ['required', 'regex:/^#[0-9a-fA-F]{6}$/'],
            'colors.*.background' => 'boolean',
        ]);

        $coverPath = $post->coverImagePath();
        if ($coverPath === null) {
            return response()->json(['status' => false, 'message' => 'У поста нет картинки — палитру не из чего считать'], 422);
        }

        // без повторов (два раза взяли пипеткой один цвет)
        $colors = collect($request->input('colors'))
            ->map(function ($color) {
                return ['hex' => strtoupper($color['hex']), 'background' => !empty($color['background'])];
            })
            ->unique('hex')
            ->values();

        $shares = ImagePalette::sharesFor(Storage::disk('local')->path($coverPath), $colors->pluck('hex')->all());
        if ($shares === null) {
            return response()->json(['status' => false, 'message' => 'Не удалось прочитать картинку поста'], 422);
        }

        $palette = $colors->map(function ($color, $i) use ($shares) {
            $item = [
                'hex' => $color['hex'],
                // цвет, который оставили вручную, должен находиться поиском по цвету, даже если на картинке
                // он — мелкая деталь: не меньше порога поиска (ColorMatch::MIN_SCORE)
                'share' => $color['background'] ? $shares[$i] : max($shares[$i], ColorMatch::MIN_SCORE),
            ];
            if ($color['background']) {
                $item['background'] = true;
            }
            return $item;
        })
            // порядок — как выбрали (первый — главный), фон — в конце, как у автоматической палитры
            ->sortBy(function ($item, $i) {
                return [!empty($item['background']), $i];
            })
            ->values()
            ->all();

        $post->palette = $palette;
        $post->palette_edited = true;
        $post->save();

        return response()->json(['status' => true, 'palette' => $post->palette, 'edited' => true]);
    }

    /**
     * Текущая палитра и обложка — блок "Палитра" перечитывает их, когда в панели "Изображения поста"
     * что-то поменяли (обложка могла смениться — тогда палитра уже пересчитана сервером)
     *
     * @param Post $post
     * @return \Illuminate\Http\JsonResponse
     */
    public function showPalette(Post $post)
    {
        return response()->json([
            'status' => true,
            'palette' => $post->palette ?? [],
            'edited' => (bool)$post->palette_edited,
            // с меткой версии — после обрезки/поворота файл тот же по пути, но другой по содержимому
            'cover_url' => $post->coverImageUrl(),
        ]);
    }

    /**
     * Вернуть автоматическую палитру (кнопка "Пересчитать автоматически" в блоке "Палитра")
     *
     * @param Post $post
     * @return \Illuminate\Http\JsonResponse
     */
    public function resetPalette(Post $post)
    {
        $post->palette = $post->computePalette() ?: null;
        $post->palette_edited = false;
        $post->save();

        return response()->json(['status' => true, 'palette' => $post->palette ?? [], 'edited' => false]);
    }

    /**
     * Удалить видео поста.
     *
     * @param Post $post
     * @return \Illuminate\Http\JsonResponse
     */
    public function deleteVideo(Post $post)
    {
        $this->deletePostVideoFile($post);

        $post->video = null;
        $post->save();

        return response()->json(['status' => true, 'message' => 'Видео удалено']);
    }

    private function deletePostVideoFile(Post $post): void
    {
        if (empty($post->video_data['path'])) {
            return;
        }

        Storage::disk('local')->delete($post->video_data['path']);
    }

    /**
     * Загрузить и сразу привязать новое изображение к посту, не затрагивая уже существующие
     * (пост может иметь несколько изображений — original_image/medium_image/small_image
     * хранятся как коллекция, keyed по имени файла).
     *
     * @param Request $request
     * @param Post $post
     * @return \Illuminate\Http\JsonResponse
     */
    public function addImage(Request $request, Post $post)
    {
        try {
            $request->validate([
                'image' => 'required|file|mimes:jpeg,png,jpg,gif,svg,webp,avif',
            ]);

            $user = auth()->user();

            $uploadController = new UploadImageController();
            $staged = $uploadController->stageFileToTempDirectory($request->file('image'));

            // allowDuplicateChoice = true: если найден дубликат МЕНЬШЕГО размера в другом
            // посте — не отклоняем автоматически, а спрашиваем пользователя, что делать
            $saveImageForPost = $uploadController->saveImageForPost($staged['data'], $user->id, $post->category_id, true);

            if (!empty($saveImageForPost['duplicate_choice']) || !empty($saveImageForPost['duplicate_reject_choice'])) {
                $isBigger = !empty($saveImageForPost['duplicate_choice']);
                $duplicate = $isBigger ? $saveImageForPost['duplicate_choice'] : $saveImageForPost['duplicate_reject_choice'];

                return response()->json([
                    'status' => false,
                    'needs_choice' => true,
                    // 'replace_or_cancel': новое изображение лучше — можно заменить старое или отменить
                    // 'add_anyway_or_cancel': новое не лучше — возможно, это ложное совпадение,
                    //   можно всё равно добавить (оставив обе картинки) или отменить
                    'mode' => $isBigger ? 'replace_or_cancel' : 'add_anyway_or_cancel',
                    'message' => $isBigger
                        ? 'Похожее изображение меньшего размера уже есть в посте #' . $duplicate->post_id . '. Что сделать?'
                        : 'Похоже, такое изображение уже есть в посте #' . $duplicate->post_id . '. Это точно другая картинка?',
                    'duplicate' => [
                        'post_id' => $duplicate->post_id,
                        'image_name' => $duplicate->image_name,
                        'thumbnail_url' => '/storage' . $duplicate->path,
                        'width' => $duplicate->width,
                        'height' => $duplicate->height,
                    ],
                    'new_image' => [
                        'thumbnail_url' => '/storage/' . $saveImageForPost['staged_image']['image_path'],
                        'width' => $saveImageForPost['new_width'],
                        'height' => $saveImageForPost['new_height'],
                    ],
                    // отдаём во фронтенд, чтобы прислать обратно при подтверждении —
                    // файл уже во временной папке, повторно загружать не нужно
                    'staged_image' => $saveImageForPost['staged_image'],
                ]);
            }

            if (!empty($saveImageForPost['errors'])) {
                $errors = $saveImageForPost['errors'];
                throw new \Exception(is_array($errors) ? implode(' ', $errors) : $errors);
            }

            $this->attachSavedImageToPost($post, $saveImageForPost);

            return response()->json([
                'status' => true,
                'message' => 'Изображение добавлено',
                'image' => $saveImageForPost['original_image'],
            ]);
        } catch (\Exception $e) {
            return response()->json(['status' => false, 'message' => $e->getMessage()]);
        }
    }

    /**
     * Пользователь подтвердил: у нового (большего/лучшего) изображения есть похожий
     * дубликат меньшего размера в ДРУГОМ посте — этот дубликат нужно удалить оттуда,
     * а новое изображение привязать к текущему посту.
     *
     * @param Request $request
     * @param Post $post
     * @return \Illuminate\Http\JsonResponse
     */
    public function confirmReplaceDuplicate(Request $request, Post $post)
    {
        try {
            $request->validate([
                'staged_image' => 'required|array',
                'duplicate_post_id' => 'required|integer',
                'duplicate_image_name' => 'required|string',
            ]);

            $user = auth()->user();

            $duplicatePost = Post::where('id', $request->duplicate_post_id)
                ->where('user_id', $user->id)
                ->first();

            if ($duplicatePost) {
                $deletePostImages = (new UploadImageController)->deletePostImages($duplicatePost, $request->duplicate_image_name);
                if ($deletePostImages !== true) {
                    $errorMessage = 'Не удалось удалить старое изображение из поста #' . $duplicatePost->id;
                    if (!empty($deletePostImages['errors'])) {
                        throw new \Exception($errorMessage . '. ' . $deletePostImages['errors']);
                    }
                    throw new \Exception($errorMessage);
                }

                UploadImageController::deleteImageHash($duplicatePost->id, $request->duplicate_image_name);
            }

            $uploadController = new UploadImageController();
            // временный файл уже лежит на диске с прошлой попытки — сохраняем его,
            // на этот раз без проверки на дубликат (мы её уже прошли и подтвердили выбор)
            $saveImageForPost = $uploadController->saveImageForPost($request->staged_image, $user->id, $post->category_id);

            if (!empty($saveImageForPost['errors'])) {
                $errors = $saveImageForPost['errors'];
                throw new \Exception(is_array($errors) ? implode(' ', $errors) : $errors);
            }

            $this->attachSavedImageToPost($post, $saveImageForPost);

            return response()->json([
                'status' => true,
                'message' => 'Изображение добавлено, старая копия удалена',
                'image' => $saveImageForPost['original_image'],
            ]);
        } catch (\Exception $e) {
            return response()->json(['status' => false, 'message' => $e->getMessage()]);
        }
    }

    /**
     * Пользователь подтвердил: похожая картинка меньшего/такого же размера в другом посте —
     * это ложное совпадение (или просто не страшно), добавляем новую как есть, ничего
     * не удаляя и не трогая. См. UploadImageController::DUPLICATE_*_THRESHOLD — совпадение
     * визуального хеша не гарантирует, что это буквально одна и та же картинка.
     *
     * @param Request $request
     * @param Post $post
     * @return \Illuminate\Http\JsonResponse
     */
    public function confirmAddDuplicate(Request $request, Post $post)
    {
        try {
            $request->validate([
                'staged_image' => 'required|array',
            ]);

            $user = auth()->user();

            $uploadController = new UploadImageController();
            // forceAdd = true: пользователь уже подтвердил выбор — повторно не проверяем
            $saveImageForPost = $uploadController->saveImageForPost($request->staged_image, $user->id, $post->category_id, false, true);

            if (!empty($saveImageForPost['errors'])) {
                $errors = $saveImageForPost['errors'];
                throw new \Exception(is_array($errors) ? implode(' ', $errors) : $errors);
            }

            $this->attachSavedImageToPost($post, $saveImageForPost);

            return response()->json([
                'status' => true,
                'message' => 'Изображение добавлено',
                'image' => $saveImageForPost['original_image'],
            ]);
        } catch (\Exception $e) {
            return response()->json(['status' => false, 'message' => $e->getMessage()]);
        }
    }

    /**
     * При создании поста нашлась такая же картинка в посте $post, и пользователь в окне сравнения
     * выбрал "Объединить": новый пост не создаётся, в $post переносится выбранное —
     * картинка лучшего качества (image_choice), текст (text_choice: old/new/both), название
     * (title_choice), хештеги нового (merge_hashtags), а также видео и данные фильма, если у $post
     * их нет. Поля формы создания (text, title, hashtags, video, pin_type, film-*) приходят как есть.
     *
     * @param Request $request
     * @param Post $post
     * @return \Illuminate\Http\JsonResponse
     */
    public function mergeDuplicate(Request $request, Post $post)
    {
        try {
            $request->validate([
                'staged_image' => 'required|array',
                'staged_image.image_name' => 'required|string',
                'staged_image.image_extension' => 'required|string',
                'staged_image.image_path' => 'required|string',
                'duplicate_image_name' => 'required|string',
                'image_choice' => 'required|in:old,new',
                'text_choice' => 'nullable|in:old,new,both',
                'title_choice' => 'nullable|in:old,new',
            ]);

            $userId = (int)auth()->id();
            if ((int)$post->user_id !== $userId) {
                abort(403);
            }

            $staged = $request->input('staged_image');
            if (!$this->isOwnStagedImage($staged, $userId)) {
                throw new \Exception('Загруженная картинка не найдена — загрузите её заново');
            }

            $uploadController = new UploadImageController();
            if ($request->input('image_choice') === 'new') {
                // проверку на дубликат уже прошли — это он и есть
                $saved = $uploadController->saveImageForPost($staged, (string)$userId, (string)$post->category_id, false, true);
                if (!empty($saved['errors'])) {
                    $errors = $saved['errors'];
                    throw new \Exception(is_array($errors) ? implode(' ', $errors) : $errors);
                }
                $uploadController->swapPostImage($post, $request->input('duplicate_image_name'), $saved);
            } else {
                UploadImageController::deleteStagedImageFiles($staged['image_name'], $staged['image_extension'], $userId);
            }

            // текст: у нового нет или он такой же — оставляем как есть; у старого нет — берём новый
            $newText = (string)$request->input('text', '');
            if ($this->postPlainText($newText) !== '' && !$this->isSamePostText($post->content, $newText)) {
                $textChoice = $this->postPlainText($post->content) === '' ? 'new' : $request->input('text_choice', 'old');
                if ($textChoice === 'new') {
                    $post->content = $newText;
                } elseif ($textChoice === 'both') {
                    $post->content = $post->content . "\n" . $newText;
                }
            }

            $newTitle = trim((string)$request->input('title', ''));
            if ($newTitle !== '' && (trim((string)$post->title) === '' || $request->input('title_choice') === 'new')) {
                $post->title = $newTitle;
            }

            if (empty($post->video) && !empty($request->video)) {
                $videoData = json_decode($request->video, true);
                $savedVideo = is_array($videoData) ? $this->saveVideoForPost($videoData, (string)$userId) : null;
                if ($savedVideo) {
                    $post->video = json_encode($savedVideo);
                }
            }

            $post->save();

            if ($request->boolean('merge_hashtags') && !empty($request->hashtags)) {
                $hashtagIds = array_keys(json_decode($request->hashtags, true) ?: []);
                $post->hashtags()->syncWithoutDetaching(
                    Hashtag::where('user_id', $userId)->whereIn('id', $hashtagIds)->pluck('id')->all()
                );
            }

            if ($request->input('pin_type') === Post::TYPE_FILM && !$post->film) {
                $filmAttributes = PostFilm::attributesFromRequest($request);
                if ($filmAttributes !== null) {
                    PostFilm::saveForPost($post, $filmAttributes);
                    $post->type = Post::TYPE_FILM;
                    $post->save();
                }
            }

            // то же для "Одежды": в существующий пост переносим данные вещи, если у него их ещё нет
            if ($request->input('pin_type') === Post::TYPE_CLOTHES && !$post->clothes) {
                $clothesAttributes = PostClothes::attributesFromRequest($request, (int)$userId);
                if ($clothesAttributes !== null) {
                    PostClothes::saveForPost($post, $clothesAttributes);
                    $post->type = Post::TYPE_CLOTHES;
                    $post->save();
                }
            }

            return response()->json([
                'status' => true,
                'message' => 'Объединено с постом #' . $post->id,
                'post_id' => $post->id,
            ]);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json(['status' => false, 'message' => implode(' ', $e->validator->errors()->all())]);
        } catch (\Exception $e) {
            return response()->json(['status' => false, 'message' => $e->getMessage()]);
        }
    }

    /**
     * Дубликаты среди картинок формы создания поста — до того, как что-то сохранено
     * (см. store()). Картинки, для которых пользователь уже выбрал "создать отдельно"
     * (force_duplicate_keys), не проверяем.
     *
     * @param Request $request
     * @param array $images images формы создания: {image_name: {image_name, image_path, ...}}
     * @param int $userId
     * @return array для окна сравнения в admin.js, по элементу на найденный дубликат
     */
    private function findDuplicatesBeforeCreate(Request $request, array $images, int $userId): array
    {
        $forceKeys = json_decode($request->force_duplicate_keys ?? '[]', true) ?: [];
        $infos = [];

        foreach ($images as $image) {
            if (!is_array($image) || empty($image['image_name']) || in_array($image['image_name'], $forceKeys, true)) {
                continue;
            }

            $found = UploadImageController::findDuplicateForStagedImage($image, $userId);
            $existingPost = $found ? Post::with('hashtags')->find($found['duplicate']->post_id) : null;
            if (!$existingPost) {
                continue;
            }

            $duplicate = $found['duplicate'];
            $oldFileSize = Storage::exists($duplicate->path) ? Storage::size($duplicate->path) : 0;
            $newArea = $found['width'] * $found['height'];
            $oldArea = $duplicate->width * $duplicate->height;

            $infos[] = [
                'image_key' => $image['image_name'],
                'staged_image' => $image,
                'new_image' => [
                    'thumbnail_url' => '/storage/' . $this->stagedPreviewPath($image),
                    'width' => $found['width'],
                    'height' => $found['height'],
                    'file_size' => $found['file_size'],
                ],
                'duplicate' => $this->formatDuplicateForFrontend($duplicate) + ['file_size' => $oldFileSize],
                // лучше качество — больше пикселей, при равном разрешении — больший файл
                'new_is_better' => $newArea !== $oldArea ? $newArea > $oldArea : $found['file_size'] > $oldFileSize,
                'existing_post' => [
                    'id' => $existingPost->id,
                    'url' => route('posts.edit', $existingPost->id),
                    'merge_url' => route('posts.merge-duplicate', $existingPost->id),
                    'title' => (string)$existingPost->title,
                    'text' => $this->postPlainText($existingPost->content),
                    'hashtags' => $existingPost->hashtags->map(function ($hashtag) {
                        return ['id' => $hashtag->id, 'title' => $hashtag->title];
                    })->values(),
                    'images_count' => count(UploadImageController::normalizeImages($existingPost->original_image)),
                ],
                'texts_equal' => $this->isSamePostText($existingPost->content, (string)$request->input('text', '')),
            ];
        }

        return $infos;
    }

    /**
     * Текст поста (HTML из TinyMCE) без разметки — для показа в окне сравнения и сравнения текстов
     *
     * @param string|null $html
     * @return string
     */
    private function postPlainText(?string $html): string
    {
        $text = preg_replace('/<(br|\/p|\/div|\/li|\/h\d)[^>]*>/i', "\n", (string)$html);
        $text = html_entity_decode(strip_tags($text), ENT_QUOTES | ENT_HTML5, 'UTF-8');
        $text = preg_replace('/[ \t\x{00A0}]+/u', ' ', $text);

        return trim(preg_replace("/\n\s*\n+/u", "\n\n", $text));
    }

    /**
     * Тексты одинаковые, если совпадают без учёта разметки, регистра и пробелов
     *
     * @param string|null $a
     * @param string|null $b
     * @return bool
     */
    private function isSamePostText(?string $a, ?string $b): bool
    {
        $normalize = function (?string $html) {
            return mb_strtolower(preg_replace('/\s+/u', ' ', $this->postPlainText($html)));
        };

        return $normalize($a) === $normalize($b);
    }

    /**
     * Путь (от корня storage) к превью картинки с формы создания: её средняя копия, если есть, иначе оригинал.
     * m_image_path — не файл, а папка копии ("temp_directory/1/", у спарсенных — без слеша), сам файл —
     * "<папка>/<m_image_name>.<image_extension>"; средней копии нет у картинок меньше 800×800
     *
     * @param array $image элемент images формы создания
     * @return string
     */
    private function stagedPreviewPath(array $image): string
    {
        // имя приходит с фронтенда — без папок и "..", как и image_name в isOwnStagedImage()
        $safeName = fn ($name) => !preg_match('/[\/\\\\]|\.\./', (string)$name);
        if (!empty($image['m_image_path']) && !empty($image['m_image_name']) && !empty($image['image_extension'])
            && $safeName($image['m_image_name']) && $safeName($image['image_extension'])) {
            $mediumPath = rtrim((string)$image['m_image_path'], '/') . '/' . $image['m_image_name'] . '.' . $image['image_extension'];
            if (Storage::exists($mediumPath)) {
                return $mediumPath;
            }
        }

        return (string)$image['image_path'];
    }

    /**
     * Картинка из формы создания лежит во временной папке этого пользователя
     * (пути приходят с фронтенда — не даём подсунуть чужой или произвольный файл)
     *
     * @param array $image
     * @param int $userId
     * @return bool
     */
    private function isOwnStagedImage(array $image, int $userId): bool
    {
        if (preg_match('/[\/\\\\]|\.\./', $image['image_name'] . $image['image_extension'])) {
            return false;
        }

        $prefix = 'temp_directory/' . $userId . '/';
        foreach (['image_path', 's_image_path', 'm_image_path'] as $key) {
            if (!isset($image[$key])) {
                continue;
            }
            $path = (string)$image[$key];
            if (strpos($path, $prefix) !== 0 || strpos($path, '..') !== false || strpos($path, '\\') !== false) {
                return false;
            }
        }

        return true;
    }

    /**
     * Приводит "сырой" объект дубликата (из UploadImageController::findDuplicateByHash())
     * к виду, удобному для фронтенда (готовый thumbnail_url вместо внутреннего path).
     *
     * @param object|null $duplicate
     * @return array|null
     */
    private function formatDuplicateForFrontend($duplicate): ?array
    {
        if (empty($duplicate)) {
            return null;
        }

        return [
            'post_id' => $duplicate->post_id,
            'image_name' => $duplicate->image_name,
            'thumbnail_url' => '/storage' . $duplicate->path,
            'width' => $duplicate->width,
            'height' => $duplicate->height,
        ];
    }

    /**
     * Привязывает уже сохранённое (moveImage выполнен) изображение к посту:
     * дописывает его в original_image/medium_image/small_image и сохраняет хеш
     * для дальнейшего поиска дублей.
     *
     * @param Post $post
     * @param array $saveImageForPost результат UploadImageController::saveImageForPost()
     * @return void
     */
    private function attachSavedImageToPost(Post $post, array $saveImageForPost): void
    {
        $originalImages = UploadImageController::normalizeImages($post->original_image);
        $mediumImages = UploadImageController::normalizeImages($post->medium_image);
        $smallImages = UploadImageController::normalizeImages($post->small_image);

        $originalImages[$saveImageForPost['original_image']['name']] = $saveImageForPost['original_image'];
        if (!empty($saveImageForPost['medium_image'])) {
            $mediumImages[$saveImageForPost['medium_image']['name']] = $saveImageForPost['medium_image'];
        }
        if (!empty($saveImageForPost['small_image'])) {
            $smallImages[$saveImageForPost['small_image']['name']] = $saveImageForPost['small_image'];
        }

        $post->original_image = json_encode($originalImages);
        $post->medium_image = json_encode($mediumImages);
        $post->small_image = json_encode($smallImages);
        $post->save();

        if (!empty($saveImageForPost['phash'])) {
            UploadImageController::storeImageHash(
                $post->id,
                $saveImageForPost['original_image']['name'],
                $saveImageForPost['original_image']['path'],
                $saveImageForPost['width'],
                $saveImageForPost['height'],
                $saveImageForPost['phash'],
                $saveImageForPost['perceptual_hash'] ?? null
            );
        }
    }

    /**
     * Переименовать изображение поста (по текущему имени файла) — вместе со всеми его
     * размерами (original/medium/small).
     *
     * @param Request $request
     * @param Post $post
     * @return \Illuminate\Http\JsonResponse
     */
    public function renameImage(Request $request, Post $post)
    {
        try {
            $request->validate([
                'old_name' => 'required|string',
                'new_name' => 'required|string',
            ]);

            $user = auth()->user();
            $oldName = $request->old_name;
            $newTitle = $request->new_name;

            $originalImages = UploadImageController::normalizeImages($post->original_image);
            if (empty($originalImages[$oldName])) {
                throw new \Exception('Изображение не найдено');
            }

            $uploadController = new UploadImageController();

            // сначала оригинал — по нему берём базовое имя, чтобы найти его medium/small
            $renamedOriginal = $this->moveRenamedImage(
                $uploadController,
                $originalImages[$oldName],
                $newTitle,
                $user->id,
                $post->category_id
            );

            unset($originalImages[$oldName]);
            $originalImages[$renamedOriginal['name']] = $renamedOriginal;
            $post->original_image = json_encode($originalImages);

            // medium/small хранятся отдельными файлами, чьё имя содержит базовое имя
            // оригинала (см. saveImageForPost()/resizeImage()) — переименовываем и их тоже
            $oldBaseName = pathinfo($oldName, PATHINFO_FILENAME);

            $mediumImages = UploadImageController::normalizeImages($post->medium_image);
            $post->medium_image = json_encode($this->renameDerivedImages(
                $uploadController, $mediumImages, $oldBaseName, $newTitle, $user->id, $post->category_id
            ));

            $smallImages = UploadImageController::normalizeImages($post->small_image);
            $post->small_image = json_encode($this->renameDerivedImages(
                $uploadController, $smallImages, $oldBaseName, $newTitle, $user->id, $post->category_id
            ));

            $post->save();

            UploadImageController::updateImageHashReference(
                $post->id,
                $oldName,
                $renamedOriginal['name'],
                $renamedOriginal['path']
            );

            // копия оригинала до правок (restoreImage) — по имени картинки, переезжает вместе с ней
            if (UploadImageController::hasImageBackup($post, $oldName)) {
                Storage::disk('local')->move(
                    UploadImageController::imageBackupPath($post, $oldName),
                    UploadImageController::imageBackupPath($post, $renamedOriginal['name'])
                );
            }

            return response()->json([
                'status' => true,
                'message' => 'Файл переименован',
                'image' => $renamedOriginal,
            ]);
        } catch (\Exception $e) {
            return response()->json(['status' => false, 'message' => $e->getMessage()]);
        }
    }

    /**
     * Повернуть или отразить изображение поста (кнопки на карточке картинки, страница редактирования) —
     * вместе с его medium/small. Файлы перезаписываются на месте: имена и пути не меняются, поэтому в посте
     * (original_image/medium_image/small_image, палитра) ничего обновлять не нужно. Из-за этого размер в имени
     * medium/small ("_350_467") после поворота на 90° не совпадает с фактическим — на имя никто не опирается.
     *
     * @param Request $request name — имя файла оригинала, action — rotate_left | rotate_right | flip_h | flip_v
     * @param Post $post
     * @return \Illuminate\Http\JsonResponse
     */
    public function transformImage(Request $request, Post $post)
    {
        try {
            $request->validate([
                'name' => 'required|string',
                'action' => 'required|in:rotate_left,rotate_right,flip_h,flip_v',
            ]);

            $name = $request->name;
            $action = $request->action;
            $isRotation = in_array($action, ['rotate_left', 'rotate_right'], true);

            $original = UploadImageController::normalizeImages($post->original_image)[$name] ?? null;
            if (empty($original['path'])) {
                throw new \Exception('Изображение не найдено');
            }

            $disk = Storage::disk('local');
            $originalPath = $disk->path($original['path']);
            // у GIF при пересохранении пропала бы анимация, SVG — не растр
            $extension = strtolower(pathinfo($originalPath, PATHINFO_EXTENSION));
            if (!in_array($extension, ['jpg', 'jpeg', 'png', 'webp'], true) || !file_exists($originalPath)) {
                throw new \Exception('Изображение этого формата нельзя повернуть');
            }

            // первая правка — сначала копия оригинала, чтобы можно было "Вернуть оригинал" (restoreImage)
            UploadImageController::backupPostImage($post, $name, $original['path']);

            $image = \Intervention\Image\Facades\Image::make($originalPath);
            // фото с телефона: браузер показывает оригинал повёрнутым по EXIF, а при пересохранении EXIF
            // теряется — сначала разворачиваем пиксели так, как картинку видно на экране
            if (in_array($extension, ['jpg', 'jpeg'], true)) {
                $image->orientate();
            }
            switch ($action) {
                // Intervention крутит против часовой стрелки
                case 'rotate_left':
                    $image->rotate(90);
                    break;
                case 'rotate_right':
                    $image->rotate(-90);
                    break;
                case 'flip_h':
                    $image->flip('h');
                    break;
                default:
                    $image->flip('v');
            }
            $image->save($originalPath, 95);

            // medium/small пересобираем из уже повёрнутого оригинала — в том же размере (при повороте на 90°
            // ширина и высота меняются местами). Свои — по имени: "{имя оригинала}_{ширина}_{высота}"
            $derivedPattern = '/^' . preg_quote(pathinfo($name, PATHINFO_FILENAME), '/') . '_\d+_\d+(\.\w+)?$/iu';
            foreach ([$post->medium_image, $post->small_image] as $json) {
                foreach (UploadImageController::normalizeImages($json) as $key => $derived) {
                    if (!preg_match($derivedPattern, (string)$key) || empty($derived['path']) || !$disk->exists($derived['path'])) {
                        continue;
                    }

                    $derivedPath = $disk->path($derived['path']);
                    $size = getimagesize($derivedPath);
                    if (!$size) {
                        continue;
                    }

                    \Intervention\Image\Facades\Image::make($originalPath)
                        ->resize($isRotation ? $size[1] : $size[0], $isRotation ? $size[0] : $size[1])
                        ->save($derivedPath);
                }
            }

            // хеши для поиска дубликатов считаются по содержимому — у перевёрнутой картинки они другие
            UploadImageController::refreshImageHash($post->id, $name, $originalPath);

            return response()->json([
                'status' => true,
                'message' => $isRotation ? 'Изображение повёрнуто' : 'Изображение отражено',
                'image' => $original,
                'has_backup' => true,
                'cover_url' => $post->coverImageUrl(),
            ]);
        } catch (\Exception $e) {
            return response()->json(['status' => false, 'message' => $e->getMessage()]);
        }
    }

    /**
     * Обрезать картинку поста (окно редактора на странице редактирования, admin.js openImageEditor): браузер
     * присылает уже обрезанную/повёрнутую картинку, она заменяет оригинал на месте — имя и путь те же, как
     * у transformImage(). Перед первой правкой сохраняется копия оригинала (restoreImage() вернёт её)
     *
     * @param Request $request name — имя картинки в посте, file — новая картинка
     * @param Post $post
     * @return \Illuminate\Http\JsonResponse
     */
    public function cropImage(Request $request, Post $post)
    {
        try {
            $request->validate([
                'name' => 'required|string',
                'file' => 'required|file|mimes:jpeg,png,jpg,webp',
            ]);

            [$name, $original, $originalPath] = $this->editablePostImage($post, $request->name);

            UploadImageController::backupPostImage($post, $name, $original['path']);

            // в том же формате, что и был (браузер кодирует в него же) — расширение в имени остаётся верным
            if (!copy($request->file('file')->getRealPath(), $originalPath)) {
                throw new \Exception('Не удалось сохранить картинку');
            }

            $this->afterPostImageReplaced($post, $name, $originalPath);

            return response()->json([
                'status' => true,
                'message' => 'Изображение сохранено',
                'image' => $original,
                'has_backup' => true,
                'cover_url' => $post->coverImageUrl(),
            ]);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json(['status' => false, 'message' => 'Не удалось сохранить картинку']);
        } catch (\Exception $e) {
            return response()->json(['status' => false, 'message' => $e->getMessage()]);
        }
    }

    /**
     * Вернуть картинке поста оригинал — каким он был до первой обрезки/поворота/отражения (копия из
     * backupPostImage()). Копия после этого удаляется
     *
     * @param Request $request name — имя картинки в посте
     * @param Post $post
     * @return \Illuminate\Http\JsonResponse
     */
    public function restoreImage(Request $request, Post $post)
    {
        try {
            $request->validate(['name' => 'required|string']);

            [$name, $original, $originalPath] = $this->editablePostImage($post, $request->name);

            $disk = Storage::disk('local');
            $backupPath = UploadImageController::imageBackupPath($post, $name);
            if (!$disk->exists($backupPath)) {
                throw new \Exception('Копии оригинала нет — картинку не меняли или оригинал уже возвращён');
            }
            if (!copy($disk->path($backupPath), $originalPath)) {
                throw new \Exception('Не удалось вернуть оригинал');
            }
            UploadImageController::deleteImageBackups($post, $name);

            $this->afterPostImageReplaced($post, $name, $originalPath);

            return response()->json([
                'status' => true,
                'message' => 'Оригинал возвращён',
                'image' => $original,
                'has_backup' => false,
                'cover_url' => $post->coverImageUrl(),
            ]);
        } catch (\Exception $e) {
            return response()->json(['status' => false, 'message' => $e->getMessage()]);
        }
    }

    /**
     * Картинка поста, которую можно править на месте (обрезка, возврат оригинала)
     *
     * @param Post $post
     * @param string $name
     * @return array [имя, запись original_image, абсолютный путь оригинала]
     * @throws \Exception
     */
    private function editablePostImage(Post $post, string $name): array
    {
        $original = UploadImageController::normalizeImages($post->original_image)[$name] ?? null;
        if (empty($original['path'])) {
            throw new \Exception('Изображение не найдено');
        }

        $originalPath = Storage::disk('local')->path($original['path']);
        // у GIF при пересохранении пропала бы анимация, SVG — не растр
        $extension = strtolower(pathinfo($originalPath, PATHINFO_EXTENSION));
        if (!in_array($extension, ['jpg', 'jpeg', 'png', 'webp'], true) || !file_exists($originalPath)) {
            throw new \Exception('Изображение этого формата нельзя редактировать');
        }

        return [$name, $original, $originalPath];
    }

    /**
     * Оригинал картинки поста заменён на месте (обрезка, возврат оригинала): пересобрать medium/small,
     * пересчитать хеши для поиска дублей и — если это обложка и палитру не правили вручную — палитру.
     *
     * Пропорции после обрезки другие, поэтому medium/small не копируют прежний размер (как при повороте),
     * а уменьшаются так же, как при загрузке (UploadImageController::resizeImage): короткая сторона —
     * MAX_IMAGE_MEDIUM_* / MAX_IMAGE_SMALL_*, но не больше, чем у оригинала. Файлы остаются под прежними
     * именами (размер в имени может не совпасть с фактическим — на имя никто не опирается)
     *
     * @param Post $post
     * @param string $name
     * @param string $originalPath абсолютный путь оригинала
     * @return void
     */
    private function afterPostImageReplaced(Post $post, string $name, string $originalPath): void
    {
        $disk = Storage::disk('local');
        $originalSize = UploadImageController::imageSize($originalPath);
        $replacedPaths = [UploadImageController::normalizeImages($post->original_image)[$name]['path'] ?? null];

        $derivedPattern = '/^' . preg_quote(pathinfo($name, PATHINFO_FILENAME), '/') . '_\d+_\d+(\.\w+)?$/iu';
        $shortSides = [
            'medium_image' => min((int)UploadImageController::MAX_IMAGE_MEDIUM_WIDTH, (int)UploadImageController::MAX_IMAGE_MEDIUM_HEIGHT),
            'small_image' => min((int)UploadImageController::MAX_IMAGE_SMALL_WIDTH, (int)UploadImageController::MAX_IMAGE_SMALL_HEIGHT),
        ];
        foreach ($shortSides as $column => $targetShortSide) {
            foreach (UploadImageController::normalizeImages($post->$column) as $key => $derived) {
                if (!preg_match($derivedPattern, (string)$key) || empty($derived['path']) || !$disk->exists($derived['path'])) {
                    continue;
                }

                $derivedPath = $disk->path($derived['path']);
                if (!$originalSize) {
                    continue;
                }

                $originalShortSide = min($originalSize[0], $originalSize[1]);
                $scale = min($targetShortSide, $originalShortSide) / $originalShortSide;

                \Intervention\Image\Facades\Image::make($originalPath)
                    ->resize(max(1, (int)round($originalSize[0] * $scale)), max(1, (int)round($originalSize[1] * $scale)))
                    ->save($derivedPath);

                $replacedPaths[] = $derived['path'];
            }
        }

        UploadImageController::refreshImageHash($post->id, $name, $originalPath);

        // путь обложки не изменился, поэтому сама палитра при сохранении поста не пересчитается (Post::booted)
        if (!$post->palette_edited && in_array($post->coverImagePath(), $replacedPaths, true)) {
            $post->palette = $post->computePalette() ?: null;
            $post->save();
        }
    }

    /**
     * Переименовывает и перемещает один файл изображения (original/medium/small).
     * Для medium/small ($isOriginalImage = false) размер (_ширина_высота) в новое имя
     * подставляется автоматически, по фактическим размерам текущего файла —
     * см. UploadImageController::generateImageNameAndPath().
     *
     * @param UploadImageController $uploadController
     * @param array $image {name, extension, path}
     * @param string $newBaseName
     * @param $userId
     * @param $categoryId
     * @param bool $isOriginalImage
     * @return array {name, extension, path}
     */
    private function moveRenamedImage(
        UploadImageController $uploadController,
        array $image,
        string $newBaseName,
        $userId,
        $categoryId,
        bool $isOriginalImage = true
    ): array {
        $oldAbsolutePath = Storage::disk('local')->path($image['path']);

        $generateImagePath = UploadImageController::generateImageNameAndPath(
            $newBaseName,
            $image['extension'],
            $userId,
            $categoryId,
            $isOriginalImage,
            $oldAbsolutePath
        );

        $newPath = $generateImagePath['image_path'] . '/' . $generateImagePath['image_name'];

        $moveImage = $uploadController->moveImage($image['path'], $newPath);
        if ($moveImage !== true) {
            throw new \Exception(is_string($moveImage) ? $moveImage : 'Не удалось переименовать файл');
        }

        return [
            'name' => $generateImagePath['image_name'],
            'extension' => $image['extension'],
            'path' => $newPath,
        ];
    }

    /**
     * Переименовывает все medium/small записи, относящиеся к переименовываемому оригиналу
     * (их ключ содержит базовое имя оригинала — так их сохраняет saveImageForPost()).
     * Записи, не относящиеся к этому оригиналу, остаются как есть.
     *
     * @param UploadImageController $uploadController
     * @param array $images нормализованная коллекция medium_image/small_image
     * @param string $oldBaseName
     * @param string $newTitle
     * @param $userId
     * @param $categoryId
     * @return array
     */
    private function renameDerivedImages(
        UploadImageController $uploadController,
        array $images,
        string $oldBaseName,
        string $newTitle,
        $userId,
        $categoryId
    ): array {
        foreach ($images as $key => $image) {
            if (stripos($key, $oldBaseName) === false) {
                continue;
            }

            $renamed = $this->moveRenamedImage($uploadController, $image, $newTitle, $userId, $categoryId, false);
            unset($images[$key]);
            $images[$renamed['name']] = $renamed;
        }

        return $images;
    }

    /**
     * Свободный alias: сам $alias или "$alias-2", "$alias-3"... Колонка posts.alias уникальна по всей таблице
     * (не по пользователю), поэтому проверяем без user_id. Раньше занятый alias давал dd() (режим "один пост") или
     * "userId_categoryId_time()" — а он совпадает у постов, сохранённых в одну секунду (группа постов, две вкладки)
     *
     * @param string $alias
     * @return string
     */
    private function freeAlias(string $alias): string
    {
        $alias = $alias !== '' ? $alias : (string)auth()->id() . '_' . time();

        $candidate = $alias;
        // withTrashed: alias поста из корзины тоже занят — колонка уникальна, а пост могут вернуть
        for ($i = 2; Post::withTrashed()->where('alias', $candidate)->exists(); $i++) {
            $candidate = $alias . '-' . $i;
        }

        return $candidate;
    }

    /**
     * @param string|null $link
     * @return BaseParser
     */
    private function getParser(?string $link): BaseParser
    {
        //dd((strripos($link, 'rezka.ag')));
        //dd($link);
        switch ($link) {
            //Инстаграм блокирует мои запросы с локального ip
            case (strripos($link, 'instagram.com') !== false):
                return new InstagramComParser($link);
            case (strripos($link, 'pinterest.com') !== false):
                return new PinterestParser($link);
            // sweet.tv сменил вёрстку — селекторы SweetTvParser (itemprop, film-left__time...) больше не
            // находятся, все данные теперь в JSON-LD, который читает универсальный парсер
            case (strripos($link, 'sweet.tv') !== false):
                return new UniversalFilmParser($link);
            //'rezka.io', 'rezka.ag' - пока можно только вслепую делать, потому что с локального ip запрос не пропускает
            case (strripos($link, 'rezka.ag') !== false):
                return new UniversalFilmParser($link);
            case (strripos($link, 'uakino.club') !== false):
                return new UniversalFilmParser($link);
            case (strripos($link, 'hd.filmix.fun') !== false):
                return new HdFilmixFunParser($link);
            case (strripos($link, 'eda.ru') !== false):
                return new EdaRuParser($link);
//            case 2:
//                echo "i equals 2";
//                break;
            default:
                return new UniversalFilmParser($link);
        }
    }

}
