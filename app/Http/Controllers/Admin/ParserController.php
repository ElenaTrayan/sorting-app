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

class ParserController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return Application|Factory|View
     */
    public function index()
    {
        return view('admin.parser.index', []);
    }


    public function instagramParserSettings()
    {
        return view('admin.parser.instagram', []);
    }

    public function getMediaFilesFromInstagram()
    {
//        return view('admin.parser.instagram', []);
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
