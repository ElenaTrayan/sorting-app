<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Admin\Packages\UploadImageController;
use App\Models\Hashtag;
use App\Models\Post;
use App\Models\PostsCategory;
use App\Parsers\BaseParser;
use App\Parsers\DefaultParser;
use App\Parsers\HdFilmixFunParser;
use App\Parsers\HdRezkaParser;
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

        $posts = Post::selectRaw('posts.id, posts.title, posts.alias, posts.user_id, posts.category_id, posts.status, posts.is_used, posts.content, posts.small_image, posts.medium_image, posts.original_image')
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
                    $q->select(['id', 'title', 'parent_id', 'user_id', 'associated_hashtags']);
                },
            ])
            ->where('posts.user_id', 1)
            ->latest()
            ->paginate(50);

        //dd($posts);

        foreach ($posts as $post) {
            if (!empty($post->small_image) && isJson($post->small_image)) {
                $smallImage = json_decode($post->small_image, true);
                $post['cover_image'] = $smallImage['path'];
            } elseif (!empty($post->medium_image) && isJson($post->medium_image)) {
                $mediumImage = json_decode($post->medium_image, true);
                $post['cover_image'] = $mediumImage['path'];
            } elseif (!empty($post->original_image) && isJson($post->original_image)) {
                $originalImage = json_decode($post->original_image, true);
                $post['cover_image'] = $originalImage['path'];
            }
        }

        //dd($posts);

        if ($request->ajax()) {
            return view('admin.posts.parts.post_items', [
                'posts' => $posts,
            ]);
        }

        return view('admin.posts.index', [
            'posts' => $posts,
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

        if (!empty($request->images)) {

            $images = json_decode($request->images, true);

            //если картинок больше одной
            if (count($images) > 1) {
                $count = 0;
                $errors = [];
                foreach ($images as $key => $image) {
                    $count++;

                    $newPost = $this->createPost($request, $user->id, $image);

                    if ($newPost !== true) {
                        $errors[] = $newPost['errors'];
                    }
                }

                if (!empty($errors)) {
                    return response()->json([
                        'errors' => $errors,
                    ]);
                }

                return response()->json([
                    'status' => true,
                    'message' => 'Посты были успешно добавлены',
                ]);
            }

            $image = array_shift($images);

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
                $q->select(['id', 'title', 'parent_id', 'user_id', 'associated_hashtags']);
            },
        ])->firstOrFail();

        $categories = PostsCategory::orderBy('title', 'DESC')->get();

        $originalImage = json_decode($post->original_image, true);
        $mediumImage = json_decode($post->medium_image, true);
        //dd($mediumImage);

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
                    $q->select(['id', 'title', 'parent_id', 'user_id', 'associated_hashtags']);
                },
            ])->first();

        $categories = PostsCategory::orderBy('title', 'DESC')->get();

        $returnArray = [
            'categories' => $categories,
            'post' => $post,
        ];

        if (!empty($post->original_image)) {
            $returnArray['originalImage'] = json_decode($post->original_image, true);
        }

        if (!empty($post->medium_image)) {
            $returnArray['mediumImage'] = json_decode($post->medium_image, true);
        }

        if (!empty($post->small_image)) {
            $returnArray['smallImage'] = json_decode($post->small_image, true);
        }
        //dd($returnArray);

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

        if (!empty($request->category_id)) {
            $post->category_id = $request->category_id;
        }

        $categoryId = $post->category_id;

        if (!empty($request->alias)) {
            $post->alias = $request->alias;
        }

        if (!empty($request->content)) {
            $post->content = $request->content;
        }

        if (!empty($request->images)) {
            $images = json_decode($request->images, true);
            $image = array_shift($images);

            $saveImageForPost = (new UploadImageController)->saveImageForPost($image, $user->id, $post->category_id);
            if (!empty($saveImageForPost['errors'])) {
                //return $saveImageForPost['errors'];
            } else {
                if (!empty($post->original_image)) {
                    $deleteImageFromPost = (new UploadImageController)->deleteImageFromPost($post->original_image, $user->id, $categoryId);
                    if ($deleteImageFromPost !== true) {
                        return response()->json([
                            'status' => false,
                            'message' => 'Ошибка при удалении original_image',
                        ]);
                    }
                }
                $post->original_image = json_encode($saveImageForPost['original_image']);

                if (!empty($post->medium_image)) {
                    $deleteImageFromPost = (new UploadImageController)->deleteImageFromPost($post->medium_image, $user->id, $categoryId);
                    if ($deleteImageFromPost !== true) {
                        return response()->json([
                            'status' => false,
                            'message' => 'Ошибка при удалении medium_image',
                        ]);
                    }
                }

                if (!empty($saveImageForPost['medium_image'])) {
                    $post->medium_image = json_encode($saveImageForPost['medium_image']);
                } else {
                    $post->medium_image = '';
                }

                if (!empty($post->small_image)) {
                    $deleteImageFromPost = (new UploadImageController)->deleteImageFromPost($post->small_image, $user->id, $categoryId);
                    if ($deleteImageFromPost !== true) {
                        return response()->json([
                            'status' => false,
                            'message' => 'Ошибка при удалении small_image',
                        ]);
                    }
                }

                if (!empty($saveImageForPost['small_image'])) {
                    $post->small_image = json_encode($saveImageForPost['small_image']);
                } else {
                    $post->medium_image = '';
                }

            }
        }

        //TODO - Пока не придумала как сделать ((
        //изменить название изображения поста
        if (!empty($request['image-name'])) {
            //$categoryParentId . '_' . $categoryId . '_'
            if (!empty($post->original_image)) {
                $originalImage = json_decode($post->original_image, true);
                $oldName = $originalImage['name'];
                $oldPath = $originalImage['path'];

                $generateImagePath = UploadImageController::generateImageNameAndPath(
                    $request['image-name'],
                    $originalImage['extension'],
                    $user->id,
                    $categoryId,
                );

                $newName = $generateImagePath['image_name'];
                $newPath = $generateImagePath['image_path'];

                $moveImage = (new UploadImageController())->moveImage($oldPath, $newPath);

                if ($moveImage) {
                   //обновляем названия и пути в БД

                    $post->original_image = json_encode([
                        'name' => $newName,
                        'extension' => $originalImage['extension'],
                        'path' => $newPath,
                    ]);

//                    "mediumImage" => array:3
//                    [▼
//                        "name" => "a17ac3f0262325f5c3bc30cb34fb9350_800_1337.jpg"
//                        "extension" => "jpg"
//                        "path" => "/images/1/7/7_6_a17ac3f0262325f5c3bc30cb34fb9350_800_1337.jpg"
//                    ]

                    if (!empty($post->medium_image)) {

                        $mediumImage = json_decode($post->medium_image, true);

                        $generateImageMediumPath = UploadImageController::generateImageNameAndPath(
                            $request['image-name'],
                            $mediumImage['extension'],
                            $user->id,
                            $categoryId,
                            false,
                            $mediumImage['path'],
                        );

                        $post->medium_image = json_encode([
                            'name' => $generateImageMediumPath['image_name'],
                            'extension' => $mediumImage['extension'],
                            'path' => $generateImageMediumPath['image_path'],
                        ]);
                    }

                    if (!empty($post->small_image)) {

                    }

                } elseif (is_string($moveImage)) {
                    //'error' => $moveImage
                }


            }

            dd($post); //TODO
        }

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

        $errors = [];

        if (!empty($post->original_image)) {
            $deleteImageFromPost = (new UploadImageController)->deleteImageFromPost($post->original_image, $user->id, $categoryId);
            if ($deleteImageFromPost !== true) {
                $errors[] = 'Ошибка при удалении original_image: ' . $post->original_image;
            }
        }

        if (!empty($post->medium_image)) {
            $deleteImageFromPost = (new UploadImageController)->deleteImageFromPost($post->medium_image, $user->id, $categoryId);
            if ($deleteImageFromPost !== true) {
                $errors[] = 'Ошибка при удалении medium_image: ' . $post->medium_image;
            }
        }

        if (!empty($post->small_image)) {
            $deleteImageFromPost = (new UploadImageController)->deleteImageFromPost($post->small_image, $user->id, $categoryId);
            if ($deleteImageFromPost !== true) {
                $errors[] = 'Ошибка при удалении small_image: ' . $post->small_image;
            }
        }

        if (!empty($errors)) {
            //TODO - Добавить запиь ошибок в логи в БД

            return response()->json([
                'status' => false,
                'error' => $errors,
            ]);
        }

        if ($post->delete() === true) {
            return response()->json([
                'status' => true,
                'message' => 'Пост был успешно удалён',
            ]);
        }

        return response()->json([
            'status' => false,
            'error' => 'Ошибка при удалении поста',
        ]);
    }

    /**
     * @param Request $request
     * @param string $userId
     * @param array $image
     * @param string $imageAlias
     * @return bool
     */
    private function createPost(Request $request, string $userId, array $image = [], string $imageAlias = '')
    {
        //dd($request->hashtags);

        $title = '';

        $categoryId = $request->category_id ?? 0;
        $categoryParentId = (new PostsCategory)->getCategoryParentId($categoryId);

        $new_post = new Post();
        $new_post->user_id = $userId;
        $new_post->category_id = $request->category_id ?: 0;

        if (isset($request->text) && !empty(strip_tags($request->text))) {
            $new_post->content = $request->text;
        }

        if (!empty($image)) {
            $saveImageForPost = (new UploadImageController)->saveImageForPost($image, $userId, $categoryId);
            if (!empty($saveImageForPost['errors'])) {
                return $saveImageForPost['errors'];
            } else {
                $new_post->original_image = json_encode($saveImageForPost['original_image']);
                if (!empty($saveImageForPost['medium_image'])) {
                    $new_post->medium_image = json_encode($saveImageForPost['medium_image']);
                }
                if (!empty($saveImageForPost['small_image'])) {
                    $new_post->small_image = json_encode($saveImageForPost['small_image']);
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
            dd($this->aliasExist($alias, $userId));
        }

        $newPost = $new_post->save();

        if ($newPost === true) {
            if ($request->hashtags) {
                $requestHashtags = json_decode($request->hashtags, true);
                $this->addHashtagsToPost($new_post->getAttribute('id'), array_keys($requestHashtags));
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
//            case 2:
//                echo "i equals 2";
//                break;
            default:
                return new DefaultParser($link);
        }
    }

}
