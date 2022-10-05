<?php

namespace App\Http\Controllers\Admin\Packages;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class SearchController extends Controller
{

    /**
     * @param Request $request
     * @return \Illuminate\Contracts\Routing\ResponseFactory|\Illuminate\Http\Response|string
     */
    public function searchHashtag(Request $request)
    {
        if ($request->ajax() && !empty($request->search)) {
            $output = "";

            $user = auth()->user();

            $hashtags = DB::table('hashtags')
                ->where('title', 'LIKE', '%' . $request->search . "%")
                ->where('user_id', $user->id)
                ->get();

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



}
