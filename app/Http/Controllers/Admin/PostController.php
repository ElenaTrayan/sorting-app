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
            ->limit(12)
            ->get();

        foreach ($posts as $post) {
            if (!empty($post->medium_image) && isJson($post->medium_image)) {
                $mediumImages = json_decode($post->medium_image, true);
                foreach ($mediumImages as $key => $mediumImage) {
                    //dd($mediumImage);
                    $post['cover_image'] = $mediumImage['path'];
                }
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

        //return response()->json(['Request' => $request]);
//        var_dump($request->title);
//        var_dump($request->alias);
//        var_dump($request->category_id);
        //echo $request->images;
//        var_dump($request->content);

        $categoryId = $request->category_id;
        $categoryParentId = (new \App\Models\PostsCategory)->getCategoryParentId($categoryId);

//        var_dump('categoryParentId = ' . $categoryParentId);

        $large_image = [];
        $small_image = [];

        $images = json_decode($request->images, true);
        foreach ($images as $key => $image) {
//            var_dump('public_path = ' . public_path());

            //user_id / category parent_id - category_id - image_title - image_size - расширение файла

            $newPath = '/' . UploadImageController::IMAGE_PATH . $user->id . '/' . $categoryParentId . '/' . $categoryParentId . '_' . $categoryId . '_' . $image['name'] . '.' . $image['extension'];
//            var_dump('newPath = ' . $newPath);

            $oldPath = '/temp_directory/' . $image['name'] . '.' . $image['extension'];
//            var_dump('oldPath = ' . $oldPath);

            $exists = Storage::disk('local')->exists($oldPath);
//            var_dump('exists = ' . $exists); // exists = 1

            $moveImage = Storage::move($oldPath, $newPath);
//            var_dump('moveImage = ' . $moveImage); // moveImage = 1

            $large_image[$key]['name'] = $image['name'];
            $large_image[$key]['extension'] = $image['extension'];
            $large_image[$key]['path'] = $newPath;

            $smallNewPath = '/' . UploadImageController::IMAGE_PATH . $user->id . '/' . $categoryParentId . '/' . $categoryParentId . '_' . $categoryId . '_' . $image['small_name'];
//            var_dump('smallNewPath = ' . $smallNewPath);

            $smallOldPath = '/temp_directory/' . $image['small_name'];
//            var_dump('smallOldPath = ' . $smallOldPath);

            $exists = Storage::disk('local')->exists($oldPath);
//            var_dump('exists = ' . $exists); // exists = 1

            $moveImage = Storage::move($smallOldPath, $smallNewPath);
//            var_dump('moveImage = ' . $moveImage); // moveImage = 1

//            $delete = Storage::delete($image['small']);
//            var_dump($delete);

            $small_image[$key]['name'] = $image['small_name'];
            $small_image[$key]['extension'] = $image['extension'];
            $small_image[$key]['path'] = $smallNewPath;

            $mediumNewPath = '/' . UploadImageController::IMAGE_PATH . $user->id . '/' . $categoryParentId . '/' . $categoryParentId . '_' . $categoryId . '_' . $image['medium_name'];
//            var_dump('smallNewPath = ' . $smallNewPath);

            $mediumOldPath = '/temp_directory/' . $image['medium_name'];
//            var_dump('smallOldPath = ' . $smallOldPath);

            $exists = Storage::disk('local')->exists($mediumOldPath);
//            var_dump('exists = ' . $exists); // exists = 1

            $moveImage = Storage::move($mediumOldPath, $mediumNewPath);
//            var_dump('moveImage = ' . $moveImage); // moveImage = 1

            $medium_image[$key]['name'] = $image['medium_name'];
            $medium_image[$key]['extension'] = $image['extension'];
            $medium_image[$key]['path'] = $mediumNewPath;
        }

//        exit();

        $new_post = new Post();
        $new_post->title = $request->title;
        $new_post->alias = $request->alias;
        $new_post->category_id = $request->category_id ?? 0;
        $new_post->user_id = $user->id;
        $new_post->content = $request->content;
        $new_post->original_image = json_encode($large_image);
        $new_post->medium_image = json_encode($medium_image);
        $new_post->small_image = json_encode($small_image);
        $new_post->save();

        return redirect()->back()->withSuccess('Пост был успешно добавлен');
    }

    /**
     * Display the specified resource.
     *
     * @param  \App\Models\Post  $post
     * @return \Illuminate\Http\Response
     */
    public function show(Post $post)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  \App\Models\Post  $post
     * @return \Illuminate\Http\Response
     */
    public function edit(Post $post)
    {
        //
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
    public function destroy(Post $post)
    {
        //
    }
}
