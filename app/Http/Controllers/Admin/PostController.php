<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Admin\Packages\UploadImageController;
use App\Models\Hashtag;
use App\Models\Post;
use App\Models\PostsCategory;
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
    public function index()
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
            ->paginate(15);

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

        return view('admin.posts.index', [
            'posts' => $posts
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
        //dd($postImage);

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

        $categoryId = $post->category_id;

        if (!empty($request->alias)) {
            $post->alias = $request->alias;
            $post->category_id = $request->category_id;
        }

        if (!empty($request->content)) {
            $post->content = $request->content;
        }

        if (!empty($request->images)) {
            $images = json_decode($request->images, true);
            $image = array_shift($images);

            $user = auth()->user();

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
        $post = Post::where('id', $id)->delete();
        if ($post === 1) {
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

        if (!empty($request->title)) {
            $title = $request->title;
            $alias = str_slug($request->title);
        } elseif (!empty($image)) {
            $alias = $userId . '_' . $categoryId . '_' . $image['name'];
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

}
