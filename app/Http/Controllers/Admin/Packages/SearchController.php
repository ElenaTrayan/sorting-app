<?php

namespace App\Http\Controllers\Admin\Packages;

use App\Http\Controllers\Admin\DevHelpersContoller;
use App\Http\Controllers\Controller;
use App\Models\Post;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class SearchController extends Controller
{

    /**
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function searchHashtag(Request $request)
    {
        if ($request->ajax() && !empty($request->search)) {
            $output = "";

            $user = auth()->user();

            if (!empty($request->hashtags)) {
                $hashtags = DB::table('hashtags')
                    ->where('title', 'LIKE', '%' . $request->search . "%")
                    ->where('user_id', $user->id)
                    ->whereNotIn('id', json_decode($request->hashtags))
                    //->groupBy('parent_id')
                    ->get();
            } else {
                $hashtags = DB::table('hashtags')
                    ->where('title', 'LIKE', '%' . $request->search . "%")
                    ->where('user_id', $user->id)
                    //->groupBy('parent_id')
                    ->get();
            }

            if ($hashtags) {

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
     * @param string $title
     * @param string $hashtags
     * @return \Illuminate\Support\Collection|string
     */
    public function searchTagByTitle(string $title, string $hashtags = '')
    {
        $user = auth()->user();

        if (!empty($hashtags)) {
            $foundHashtags = DB::table('hashtags')
                ->where('title', $title)
                ->where('user_id', $user->id)
                //->whereNotIn('id', json_decode($hashtags))
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
     * @return \Illuminate\Http\JsonResponse
     */
    public function searchPostsByHashtags(Request $request)
    {
        if ($request->ajax() && !empty($request->hashtags)) {

            $user = auth()->user();
            $hashtags = $request->hashtags;

            $posts = Post::whereHas('hashtags', function($q) use ($hashtags) {
                //$q->select(['id', 'title', 'parent_id', 'user_id', 'associated_hashtags']);
                $q->whereIn('hashtags.id', $hashtags);
            },
                )->with([
                    'hashtags' => function($q) use ($hashtags) {
                        //$q->select(['id', 'title', 'parent_id', 'user_id', 'associated_hashtags']);
                        $q->whereIn('hashtags.id', $hashtags);
                    },
                ])
                //->where('user_id', $user->id)
                //->limit(12)
                ->get();

            if ($posts) {
                return response()->json(['status' => true, 'posts' => $posts]);
            }
        }

        return response()->json(['status' => false]);
    }



}
