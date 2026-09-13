<?php

namespace App\Http\Controllers\Admin;

use App\Components\ImageReader;
use App\Http\Controllers\Admin\Packages\InstagramParser;
use App\Http\Controllers\Controller;
use App\Http\Controllers\Admin\Packages\UploadImageController;
use App\Models\Hashtag;
use App\Models\Post;
use App\Models\PostsCategory;
use App\Parsers\BaseParser;
use App\Parsers\DefaultParser;
use App\Parsers\EdaRuParser;
use App\Parsers\HdFilmixFunParser;
use App\Parsers\HdRezkaParser;
use App\Parsers\InstagramComParser;
use App\Parsers\PinterestParser;
use App\Parsers\SweetTvParser;
use App\Parsers\UaKinoClubParser;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Contracts\View\Factory;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use function PHPUnit\Framework\isJson;

class PostController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return Application|Factory|View
     */
    public function index(Request $request)
    {
        //$posts = Post::orderBy('title', 'asc')->get();

        $posts = Post::selectRaw('posts.id, posts.title, posts.alias, posts.user_id, posts.category_id,
        posts.status, posts.is_used, posts.content, posts.small_image, posts.medium_image, posts.original_image, posts.video')
            ->with([
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
            ])
            ->where('posts.user_id', 1)
            ->when($request->boolean('without_hashtags'), function($q) {
                $q->whereDoesntHave('hashtags');
            })
            ->latest()
            ->paginate(50);

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

        if ($request->ajax()) {
            return view('admin.posts.parts.post_items', [
                'posts' => $posts,
            ]);
        }

        return view('admin.posts.index', [
            'posts' => $posts,
            'withoutHashtags' => $request->boolean('without_hashtags'),
        ]);
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return Application|Factory|View
     */
    public function create()
    {
        $categories = PostsCategory::orderBy('title', 'DESC')->get();
        $hashtags = Hashtag::orderBy('title', 'DESC')->get();

//        DevHelpersContoller::writeLogToFile($categories);

        return view('admin.posts.create', ['categories' => $categories, 'hashtags' => $hashtags]);
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function store(Request $request)
    {
        $user = auth()->user();
        //dd($request);

        if (!empty($request->images)) {

            $images = json_decode($request->images, true);

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
                    foreach ($images as $key => $image) {
                        $count++;

                        $newPost = $this->createPost($request, $user->id, $image);

                        if ($newPost !== true) {
                            $errors[] = $newPost['errors'];
                            if (!empty($newPost['duplicate_infos'])) {
                                $duplicateInfos = array_merge($duplicateInfos, $newPost['duplicate_infos']);
                            }
                        }
                    }

                    if (!empty($errors)) {
                        return response()->json([
                            'errors' => $errors,
                            'duplicate_infos' => $duplicateInfos,
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
     * @param $id
     * @return Application|Factory|View
     */
    public function show($id)
    {
        $post = Post::where('id', $id)->with([
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
        ])->firstOrFail();

        $categories = PostsCategory::orderBy('title', 'DESC')->get();

        $originalImage = json_decode($post->original_image, true);
        $mediumImage = json_decode($post->medium_image, true);
//        dd($originalImage);
//        foreach ($originalImage as $key => $image) {
//            //dd($mediumImage[$key]['path'] ?? $image['path'] ?? '');
//            dd($image['path']);
//        }

        return view('admin.posts.show', ['categories' => $categories, 'post' => $post, 'originalImage' => $originalImage, 'mediumImage' => $mediumImage]);
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param $id
     * @return Application|Factory|View
     */
    public function edit($id)
    {
        $post = Post::where('id', $id)
            ->where('posts.user_id', 1)
            ->with([
                'hashtags' => function($q) {
                    $q->select(['id', 'title', 'parent_id', 'user_id']);
                },
            ])->first();

        $categories = PostsCategory::orderBy('title', 'DESC')->get();

        // приводим original_image к единому виду — коллекции {name: {name, extension, path}},
        // независимо от того, один это файл (старый формат) или несколько
        $returnArray = [
            'categories' => $categories,
            'post' => $post,
            'postImages' => UploadImageController::normalizeImages($post->original_image),
        ];

        return view('admin.posts.edit', $returnArray);
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

        if (!empty($request->content)) {
            $post->content = $request->content;
        }

        // Добавление/замена/переименование/удаление изображений поста больше не идёт через
        // этот общий save — теперь это отдельные немедленные AJAX-действия панели
        // "Изображения поста" (см. PostController::addImage(), ::renameImage(), ::deletePostFile()).

        $postSave = $post->save();

        if ($postSave === true) {
            if (!empty($addHashtags)) {
                $this->addHashtagsToPost($post->id, $addHashtags);
            }
            if (!empty($deleteHashtags)) {
                $this->removeHashtagsFromPost($post->id, $deleteHashtags);
            }

            return response()->json([
                'status' => true,
                'message' => 'Пост был успешно обновлен',
            ]);
        }

        return response()->json([
            'errors' => 'Не удалось обновить пост',
        ]);
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function destroy($id)
    {
        $user = auth()->user();
        $post = Post::where('id', $id)->where('posts.user_id', $user->id)->first();
        $categoryId = $post->category_id;

        try {
            $deletePostImages = (new UploadImageController)->deletePostImages($post);
            if ($deletePostImages !== true) {
                $errorMessage = 'Ошибка при удалении изображений поста.';
                if (!empty($deletePostImages['errors'])) {
                    throw new \Exception($errorMessage . '. ' . $deletePostImages['errors']);
                }

                throw new \Exception($errorMessage);
            }

            if ($post->delete() === true) {
                //dd('UDALENO');
                return response()->json([
                    'status' => true,
                    'message' => 'Пост был успешно удалён',
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
     * @return array|bool|bool[]|string|string[]
     */
    private function createPost(Request $request, string $userId, array $image = [], string $imageAlias = '')
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

        $categoryId = $request->category_id ?? 0;
        $categoryParentId = (new PostsCategory)->getCategoryParentId($categoryId);

        $new_post = new Post();
        $new_post->user_id = $userId;
        $new_post->category_id = $request->category_id ?: 0;

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

            if ($this->aliasExist($alias, $userId) === null) {
                $new_post->alias = $alias;
            } else {
                dd($this->aliasExist($alias, $userId));
            }

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

            if ($this->aliasExist($alias, $userId) === null) {
                $new_post->alias = $alias;
            } else {
                $new_post->alias = $userId . '_' . $categoryId . '_' . time();
            }
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
        $post = Post::where('id', $postId)->firstOrFail();
        $hashtags = Hashtag::find($hashtagsIds);
        $post->hashtags()->attach($hashtags);
    }

    /**
     * @param $postId
     * @param array $hashtagsIds
     * @return void
     */
    private function removeHashtagsFromPost($postId, array $hashtagsIds)
    {
        $post = Post::where('id', $postId)->firstOrFail();
        $hashtags = Hashtag::find($hashtagsIds);
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
        }

        return $result;
    }

    public function getTextFromImage(Request $request)
    {
        if ($request->ajax() && !empty($request->imagePath)) {
            $imageReader = new ImageReader();
            $imageReader->recognizeText($request->imagePath);
        }
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
                'image' => 'required|file|mimes:jpeg,png,jpg,gif,svg,webp',
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
     * @param $alias
     * @param $userId
     * @return mixed
     */
    private function aliasExist($alias, $userId)
    {
        $posts = Post::selectRaw('posts.id, posts.alias, posts.user_id')
            ->where('posts.user_id', $userId)
            ->where('posts.alias', $alias)
            ->first();

        return $posts;
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
            //почему-то парсит только на украинском языке, хотя вставляю ссылку с ru в урле
            case (strripos($link, 'sweet.tv') !== false):
                return new SweetTvParser($link);
            //'rezka.io', 'rezka.ag' - пока можно только вслепую делать, потому что с локального ip запрос не пропускает
            case (strripos($link, 'rezka.ag') !== false):
                return new HdRezkaParser($link);
            case (strripos($link, 'uakino.club') !== false):
                return new UaKinoClubParser($link);
            case (strripos($link, 'hd.filmix.fun') !== false):
                return new HdFilmixFunParser($link);
            case (strripos($link, 'eda.ru') !== false):
                return new EdaRuParser($link);
//            case 2:
//                echo "i equals 2";
//                break;
            default:
                return new DefaultParser($link);
        }
    }

}
