<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Admin\Packages\UploadImageController;
use App\Models\Hashtag;
use App\Models\Post;
use App\Models\PostsCategory;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use function PHPUnit\Framework\isJson;

class PostController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        //$posts = Post::orderBy('title', 'asc')->get();

        $posts = Post::selectRaw('posts.id, posts.title, posts.alias, posts.user_id, posts.category_id, posts.status, posts.is_used, posts.content, posts.medium_image, posts.original_image')
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
            ->limit(30)
            ->get();

        //dd($posts);

        foreach ($posts as $post) {
            if (!empty($post->medium_image) && isJson($post->medium_image)) {
                $mediumImage = json_decode($post->medium_image, true);
                $post['cover_image'] = $mediumImage['path'];
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
     * @return \Illuminate\Http\Response
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
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
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
     * @param  \App\Models\Post  $post
     * @return \Illuminate\Http\Response
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
     * @param  \App\Models\Post  $post
     * @return \Illuminate\Http\Response
     */
    public function edit($id)
    {
        $post = Post::where('id', $id)->first();
        $categories = PostsCategory::orderBy('title', 'DESC')->get();

        return view('admin.posts.edit', ['categories' => $categories, 'post' => $post]);
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\Post  $post
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, Post $post)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Models\Post  $post
     * @return \Illuminate\Http\Response
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

        $alias = '';
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
                $alias = $userId . '_' . $categoryId . '_' . $image['name'];

                $new_post->original_image = json_encode($saveImageForPost['original_image']);
                $new_post->medium_image = json_encode($saveImageForPost['medium_image']);
                $new_post->small_image = json_encode($saveImageForPost['small_image']);
            }
        }

        if (!empty($request->title)) {
            $title = $request->title;
            $alias = str_slug($request->title);
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
                $this->addHashtagsToPost($new_post->getAttribute('id'), json_decode($request->hashtags));
            }

            return true;
        }

        return false;
    }

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
