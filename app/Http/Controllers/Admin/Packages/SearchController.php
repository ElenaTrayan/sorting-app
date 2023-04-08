<?php

namespace App\Http\Controllers\Admin\Packages;

use App\Http\Controllers\Admin\DevHelpersContoller;
use App\Http\Controllers\Controller;
use App\Models\Post;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Contracts\View\Factory;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use function PHPUnit\Framework\isJson;

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

            if (!empty($requestHashtags)) {
                $hashtags = DB::table('hashtags')
                    ->where('title', 'LIKE', '%' . $request->search . "%")
                    ->where('user_id', $user->id)
                    ->whereNotIn('id', array_keys($requestHashtags))
                    //->groupBy('parent_id')
                    ->get();
            } else {
                $hashtags = DB::table('hashtags')
                    ->where('title', 'LIKE', '%' . $request->search . "%")
                    ->where('user_id', $user->id)
                    //->groupBy('parent_id')
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
        if ($request->ajax() && !empty($request->hashtags)) {
            $hashtags = $request->hashtags;
            session(['hashtags' => $hashtags]);
        } elseif ($request->session()->has('hashtags')) {
            $hashtags = session('hashtags');
        }

        if (!empty($hashtags)) {

            $user = auth()->user();

            $hashtagsIds = array_keys($hashtags);

            $posts = Post::whereHas('hashtags', function($q) use ($hashtagsIds) {
                //$q->select(['id', 'title', 'parent_id', 'user_id', 'associated_hashtags']);
                $q->whereIn('hashtags.id', $hashtagsIds);
            },
                )->with([
                    'hashtags' => function($q) use ($hashtagsIds) {
                        //$q->select(['id', 'title', 'parent_id', 'user_id', 'associated_hashtags']);
                        //$q->whereIn('hashtags.id', $hashtags);
                    },
                ])
                ->where('user_id', $user->id)
                ->latest()
                ->paginate(15);

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

            return view('admin.posts.parts.post_items', [
                'posts' => $posts,
            ]);
        }

        return response()->json(['status' => false]);
    }



}
