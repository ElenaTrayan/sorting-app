<?php

namespace App\Http\Controllers\User\Packages;

use App\Components\CollectionColors;
use App\Components\ColorMatch;
use App\Http\Controllers\User\DevHelpersContoller;
use App\Http\Controllers\User\Packages\UploadImageController;
use App\Http\Controllers\Controller;
use App\Models\Post;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Contracts\View\Factory;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use function PHPUnit\Framework\isJson;
use ZipArchive;

class SearchController extends Controller
{

    /**
     * @param Request $request
     * @return JsonResponse
     */
    public function searchHashtag(Request $request)
    {
        if ($request->ajax() && !empty($request->search)) {
            $output = "";

            $user = auth()->user();

            //var_dump($request->hashtags);

            $requestHashtags = $request->hashtags;

            // сортируем по релевантности: сначала хештеги, где искомая подстрока встречается
            // раньше в названии (в идеале — в самом начале), а не в порядке "как найдётся в БД"
            if (!empty($requestHashtags)) {
                $hashtags = DB::table('hashtags')
                    ->where('title', 'LIKE', '%' . $request->search . "%")
                    ->where('user_id', $user->id)
                    ->whereNotIn('id', array_keys($requestHashtags))
                    ->orderByRaw('LOCATE(?, title) ASC', [$request->search])
                    ->orderBy('title', 'asc')
                    ->get();
            } else {
                $hashtags = DB::table('hashtags')
                    ->where('title', 'LIKE', '%' . $request->search . "%")
                    ->where('user_id', $user->id)
                    ->orderByRaw('LOCATE(?, title) ASC', [$request->search])
                    ->orderBy('title', 'asc')
                    ->get();
            }

            if (count($hashtags)) {

                foreach ($hashtags as $key => $hashtag) {
                    $output .= '' .
                        '' . $hashtag->id . '' .
                        '' . $hashtag->title . '' .
                        '';
                }

                return response()->json(['status' => true, 'hashtags' => $hashtags]);
            }
        }

        return response()->json(['status' => false]);
    }

    public function searchSameHashtag(Request $request)
    {
        if ($request->ajax() && !empty($request->title)) {
            $foundHashtags = $this->searchTagByTitle($request->title);

            return response()->json(['status' => true, 'hashtags' => $foundHashtags]);
        }

        return response()->json(['status' => false]);
    }

    /**
     * @param $title
     * @param array $hashtags
     * @return \Illuminate\Support\Collection
     */
    public function searchTagByTitle($title, $hashtags = [])
    {
        $user = auth()->user();

        if (!empty($hashtags)) {
            $foundHashtags = DB::table('hashtags')
                ->where('title', $title)
                ->where('user_id', $user->id)
                //->whereNotIn('id', array_keys($hashtags))
                //->groupBy('parent_id')
                ->get();
        } else {
            $foundHashtags = DB::table('hashtags')
                ->where('title', $title)
                ->where('user_id', $user->id)
                //->groupBy('parent_id')
                ->get();
        }

//        dd($foundHashtags);
//        foreach ($foundHashtags as $key => $hashtag) {
//            dd($hashtag);
//        }

        return $foundHashtags;
    }


    /**
     * @param Request $request
     * @return Application|Factory|View|JsonResponse
     */
    public function searchPostsByHashtags(Request $request)
    {
        // период по дате пина (блок "Период" в поп-апе поиска) — искать можно и по нему одному, без хештегов
        $requestPeriod = $this->periodFromRequest($request);
        // новый поиск (хештеги или период пришли в запросе), а не следующая страница уже начатого (всё — из сессии)
        $isNewSearch = $request->ajax() && (!empty($request->hashtags) || $requestPeriod !== null);
        $period = null;

        //TODO
        if ($isNewSearch) {
            $hashtags = $request->hashtags ?? [];
            $excludeHashtags = $request->excludeHashtags ?? [];
            $period = $requestPeriod;
            session(['hashtags' => $hashtags]);
            session(['excludeHashtags' => $excludeHashtags]);
            session(['searchPeriod' => $period]);
        } elseif ($request->session()->has('hashtags')) {
            $hashtags = session('hashtags');
            $excludeHashtags = session('excludeHashtags', []);
            $period = session('searchPeriod');
        }

        if (!empty($hashtags) || $period !== null) {

            $user = auth()->user();

            $hashtagsIds = array_keys($hashtags ?: []);
            $excludeHashtagsIds = !empty($excludeHashtags) ? array_keys($excludeHashtags) : [];

            // группа — одна карточка-стопка, как в ленте "Все посты"; снята галочка "Свернуть группы" — как и
            // в ленте, посты групп отдельными карточками
            $groupsExpanded = Post::feedGroupsExpanded();
            // группы развёрнуты самим поиском — admin.js снимет галочку "Свернуть группы" (заголовок X-Groups-Expanded)
            $expandedBySearch = false;
            $query = $this->buildHashtagFilteredPostsQuery($hashtagsIds, $excludeHashtagsIds, $user->id);
            $this->applyPeriod($query, $period);
            if (!$groupsExpanded) {
                $matched = (clone $query)->get(['id', 'group_id']);

                if ($isNewSearch && $this->hasPartlyMatchedGroups($matched, (int)$user->id)) {
                    // в какой-то группе нашлись не все посты — значит, по их собственным хештегам: показываем
                    // только найденные посты, отдельными карточками, а не всю группу стопкой. Следующие страницы
                    // идут уже без стопок — по cookie, которую ставит admin.js вместе с галочкой
                    $groupsExpanded = $expandedBySearch = true;
                } else {
                    // все найденные группы подошли целиком (по общим хештегам) — каждая одной стопкой, своим
                    // первым постом. Сюда же попадают следующие страницы поиска, если галочку после него отметили снова
                    $headIds = Post::groupHeadIdsByGroup((int)$user->id);
                    $ids = $matched
                        ->map(fn (Post $post) => $post->group_id && isset($headIds[$post->group_id]) ? $headIds[$post->group_id] : $post->id)
                        ->unique()
                        ->values()
                        ->all();
                    $query = Post::where('user_id', $user->id)->whereIn('id', $ids);
                }
            }
            $posts = $query
                ->with(['hashtags', 'film', 'wardrobeItems:id,post_id', 'filmPost:id,title'])
                // с периодом — по дате пина (новые даты выше), без него — как раньше, по дате добавления
                ->when($period !== null, function ($q) {
                    $q->orderByDesc('posts.event_date')->orderByDesc('posts.id');
                }, function ($q) {
                    $q->latest();
                })
                ->paginate(20);

//            $posts = Post::whereHas('hashtags', function($q) use ($hashtagsIds) {
//                //$q->select(['id', 'title', 'parent_id', 'user_id', 'associated_hashtags']);
//                $q->whereIn('hashtags.id', $hashtagsIds);
//            },
//            )->with([
//                    'hashtags' => function($q) use ($hashtagsIds) {
//                        //$q->select(['id', 'title', 'parent_id', 'user_id', 'associated_hashtags']);
//                        //$q->whereIn('hashtags.id', $hashtags);
//                    },
//                ])
//                ->where('user_id', $user->id)
//                ->latest()
//                ->paginate(20);

            // coverImagePath() понимает и одну картинку {path}, и карусель {name: {path}}
            foreach ($posts as $post) {
                $post['cover_image'] = $post->coverImagePath();
            }
            if (!$groupsExpanded) {
                Post::fillGroupCounts($posts);
                // на стопке — только общие хештеги группы, без собственных хештегов её первого поста
                Post::fillGroupCommonHashtags($posts);
            } else {
                // отдельные карточки постов групп: собственные хештеги — другим цветом
                Post::fillGroupOwnHashtagIds($posts);
            }

            $response = response()->view('user.posts.parts.post_items', [
                'posts' => $posts,
            ]);

            return $expandedBySearch ? $response->header('X-Groups-Expanded', '1') : $response;
        }

        return response()->json(['status' => false]);
    }

    /**
     * Период поиска по дате пина из запроса (блок "Период" в поп-апе поиска): date_from / date_to ('Y-m-d',
     * любой можно не задавать) или on_this_day ('MM-DD' — "в этот день" в любом году)
     *
     * @param Request $request
     * @return array|null ['from' => ?string, 'to' => ?string, 'day' => ?string]; null — период не задан
     */
    private function periodFromRequest(Request $request): ?array
    {
        $date = function ($value): ?string {
            return is_string($value) && preg_match('/^(\d{4})-(\d{2})-(\d{2})$/', $value, $m) && checkdate((int)$m[2], (int)$m[3], (int)$m[1])
                ? $value
                : null;
        };

        $day = $request->input('on_this_day');
        if (is_string($day) && preg_match('/^(\d{2})-(\d{2})$/', $day, $m) && checkdate((int)$m[1], (int)$m[2], 2000)) {
            return ['from' => null, 'to' => null, 'day' => $day];
        }

        $from = $date($request->input('date_from'));
        $to = $date($request->input('date_to'));
        if ($from && $to && $from > $to) {
            [$from, $to] = [$to, $from];
        }

        return $from || $to ? ['from' => $from, 'to' => $to, 'day' => null] : null;
    }

    /**
     * Условие периода по дате пина (см. periodFromRequest()) — Post::scopeEventDateBetween / scopeEventDateOnDay
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param array|null $period
     * @return void
     */
    private function applyPeriod($query, ?array $period): void
    {
        if ($period === null) {
            return;
        }
        if (!empty($period['day'])) {
            [$month, $day] = array_map('intval', explode('-', $period['day']));
            $query->eventDateOnDay($month, $day);
            return;
        }
        $query->eventDateBetween($period['from'] ?? null, $period['to'] ?? null);
    }

    /**
     * Есть ли среди найденных постов группа, найденная не целиком — то есть по собственным хештегам её постов,
     * а не по общим (общие есть у всех постов группы)
     *
     * @param \Illuminate\Support\Collection $matched найденные посты (id, group_id)
     * @param int $userId
     * @return bool
     */
    private function hasPartlyMatchedGroups($matched, int $userId): bool
    {
        $matchedByGroup = $matched->whereNotNull('group_id')->countBy('group_id');
        if ($matchedByGroup->isEmpty()) {
            return false;
        }

        $sizes = Post::whereIn('group_id', $matchedByGroup->keys())
            ->where('user_id', $userId)
            ->selectRaw('group_id, count(*) as total')
            ->groupBy('group_id')
            ->pluck('total', 'group_id');

        return $matchedByGroup->contains(fn ($count, $groupId) => $count < (int)($sizes[$groupId] ?? 0));
    }

    /**
     * Цвета коллекции для вкладки "Поиск по цвету": оттенки из палитр постов пользователя
     * с числом постов по каждому (App\Components\CollectionColors, кешируется). Грузится,
     * когда вкладку открывают, а не вместе со страницей — поп-ап поиска есть на каждой странице.
     *
     * @return JsonResponse
     */
    public function collectionColors(): JsonResponse
    {
        return response()->json([
            'status' => true,
            'colors' => CollectionColors::forUser(auth()->id()),
        ]);
    }

    /**
     * Посты, в палитре обложки которых есть цвет, похожий на искомый (вкладка "Поиск по цвету").
     * Сначала те, где этот цвет занимает больше картинки (App\Components\ColorMatch::score).
     * Вместе с хэштегами из поп-апа: include[] — у поста должны быть все, exclude[] — ни одного
     * (то же, что в поиске по хэштегам, buildHashtagFilteredPostsQuery).
     * Постов у пользователя сотни-тысячи, палитра — 2-6 цветов, поэтому считаем в PHP по всем сразу.
     * Отдаёт карточки (post_items), следующие страницы — ?page=N (бесконечная прокрутка в admin.js),
     * общее число найденных — в заголовке X-Total-Count.
     *
     * @param Request $request
     * @return \Illuminate\Http\Response
     */
    public function searchPostsByColor(Request $request)
    {
        $request->validate([
            'hex' => ['required', 'regex:/^#?[0-9a-fA-F]{6}$/'],
            'include' => 'array',
            'include.*' => 'integer',
            'exclude' => 'array',
            'exclude.*' => 'integer',
        ]);

        $targetLab = ColorMatch::hexToLab($request->input('hex'));

        $scores = [];
        $candidates = $this->buildHashtagFilteredPostsQuery(
            array_map('intval', $request->input('include', [])),
            array_map('intval', $request->input('exclude', [])),
            auth()->id()
        )
            ->whereNotNull('palette')
            ->get(['id', 'palette']);
        foreach ($candidates as $candidate) {
            $score = ColorMatch::score($candidate->palette, $targetLab);
            if ($score >= ColorMatch::MIN_SCORE) {
                $scores[$candidate->id] = $score;
            }
        }
        // по оценке, при равной — новые выше
        uksort($scores, function ($a, $b) use ($scores) {
            return [$scores[$b], $b] <=> [$scores[$a], $a];
        });

        $perPage = 20;
        $page = max(1, (int)$request->input('page', 1));
        $pageIds = array_slice(array_keys($scores), ($page - 1) * $perPage, $perPage);

        $posts = Post::whereIn('id', $pageIds)
            ->with(['hashtags', 'film', 'wardrobeItems:id,post_id', 'filmPost:id,title'])
            ->get()
            ->sortBy(function ($post) use ($pageIds) {
                return array_search($post->id, $pageIds);
            })
            ->values()
            ->each(function ($post) {
                $post['cover_image'] = $post->coverImagePath();
            });

        return response()
            ->view('user.posts.parts.post_items', ['posts' => $posts])
            ->header('X-Total-Count', count($scores));
    }

    /**
     * Общее для searchPostsByHashtags()/exportImagesByHashtags() построение запроса
     * постов по выбранным/исключённым хештегам.
     *
     * @param array $hashtagsIds
     * @param array $excludeHashtagsIds
     * @param int $userId
     * @return \Illuminate\Database\Eloquent\Builder
     */
    private function buildHashtagFilteredPostsQuery(array $hashtagsIds, array $excludeHashtagsIds, int $userId)
    {
        return Post::where(function ($query) use ($hashtagsIds) {
            foreach ($hashtagsIds as $hashtagId) {
                $query->whereHas('hashtags', function ($q) use ($hashtagId) {
                    $q->where('hashtags.id', $hashtagId);
                });
            }
        })
            ->when(!empty($excludeHashtagsIds), function ($query) use ($excludeHashtagsIds) {
                $query->whereDoesntHave('hashtags', function ($q) use ($excludeHashtagsIds) {
                    $q->whereIn('hashtags.id', $excludeHashtagsIds);
                });
            })
            ->where('user_id', $userId);
    }

    /**
     * Скачать архивом (zip) оригинальные изображения всех постов, найденных
     * текущим активным поиском по хештегам (кнопка "Экспорт изображений" на
     * странице /cabinet/posts). Фильтр берётся из той же сессии, что и
     * searchPostsByHashtags() — то есть архив собирается ровно из того, что
     * сейчас показано на экране.
     *
     * @param Request $request
     * @return \Symfony\Component\HttpFoundation\BinaryFileResponse|\Illuminate\Http\RedirectResponse
     */
    public function exportImagesByHashtags(Request $request)
    {
        $hashtags = session('hashtags');
        $excludeHashtags = session('excludeHashtags', []);
        $period = session('searchPeriod');

        if (empty($hashtags) && $period === null) {
            return redirect()->route('posts.index')->with('errors', 'Сначала выполните поиск по хештегам — экспортируются картинки из результатов поиска.');
        }

        $user = auth()->user();
        $hashtagsIds = array_keys($hashtags ?: []);
        $excludeHashtagsIds = !empty($excludeHashtags) ? array_keys($excludeHashtags) : [];

        $query = $this->buildHashtagFilteredPostsQuery($hashtagsIds, $excludeHashtagsIds, $user->id);
        // тот же период, что у поиска на экране
        $this->applyPeriod($query, $period);
        $posts = $query->get(['id', 'original_image']);

        if ($posts->isEmpty()) {
            return redirect()->route('posts.index')->with('errors', 'По текущему поиску не найдено ни одного поста.');
        }

        // собираем оригинальные изображения всех найденных постов, без повторов внутри архива
        $filesToZip = []; // entryName => absolutePath
        foreach ($posts as $post) {
            $images = UploadImageController::normalizeImages($post->original_image);

            foreach ($images as $name => $image) {
                if (empty($image['path']) || empty($image['extension'])) {
                    continue;
                }

                $absolutePath = Storage::disk('local')->path($image['path']);
                if (!file_exists($absolutePath)) {
                    continue;
                }

                $entryName = $name . '.' . $image['extension'];
                $i = 1;
                while (isset($filesToZip[$entryName])) {
                    $entryName = $name . '-' . $i . '.' . $image['extension'];
                    $i++;
                }

                $filesToZip[$entryName] = $absolutePath;
            }
        }

        if (empty($filesToZip)) {
            return redirect()->route('posts.index')->with('errors', 'У найденных постов нет ни одного изображения на диске.');
        }

        $filename = $this->sanitizeExportFilename($request->query('filename'));

        $userId = $user->id;
        $tempDir = 'temp_directory/' . $userId;
        Storage::disk('local')->makeDirectory($tempDir);
        $tempZipPath = Storage::disk('local')->path($tempDir . '/' . $filename . '-' . time() . '.zip');

        $zip = new ZipArchive();
        if ($zip->open($tempZipPath, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
            return redirect()->route('posts.index')->with('errors', 'Не удалось создать архив.');
        }

        foreach ($filesToZip as $entryName => $absolutePath) {
            $zip->addFile($absolutePath, $entryName);
        }

        $zip->close();

        return response()->download($tempZipPath, $filename . '.zip')->deleteFileAfterSend(true);
    }

    /**
     * Приводит имя архива, введённое пользователем, к безопасному виду для
     * заголовка Content-Disposition и файловой системы.
     *
     * @param string|null $rawFilename
     * @return string
     */
    private function sanitizeExportFilename(?string $rawFilename): string
    {
        $filename = trim((string) $rawFilename);
        // оставляем буквы (в т.ч. кириллицу), цифры, пробел, дефис, подчёркивание
        $filename = preg_replace('/[^\p{L}\p{N} _\-]+/u', '', $filename);
        $filename = trim(preg_replace('/\s+/', ' ', $filename));
        $filename = mb_substr($filename, 0, 150);

        if ($filename === '') {
            $filename = 'изображения-' . now()->format('Y-m-d');
        }

        return $filename;
    }

}
