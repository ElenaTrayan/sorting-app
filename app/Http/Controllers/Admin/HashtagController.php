<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Admin\Packages\SearchController;
use App\Http\Controllers\Controller;
use App\Models\Hashtag;
use Illuminate\Http\Request;

class HashtagController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Contracts\Foundation\Application|\Illuminate\Contracts\View\Factory|\Illuminate\Contracts\View\View
     */
    public function index()
    {
        $user = auth()->user();

        $hashtags = Hashtag::where('user_id', $user->id)->orderBy('title', 'asc')->get();

        return view('admin.hashtags.index', ['hashtags' => $hashtags]);
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Contracts\Foundation\Application|\Illuminate\Contracts\View\Factory|\Illuminate\Contracts\View\View
     */
    public function create()
    {
        $user = auth()->user();

        $hashtags = Hashtag::where('user_id', $user->id)->orderBy('title', 'asc')->get();

        return view('admin.hashtags.create', ['hashtags' => $hashtags]);
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse|\Illuminate\Http\RedirectResponse
     */
    public function store(Request $request)
    {
        if (empty($request->title)) {
            return redirect()->route('hashtags.index')->withErrors('Не удалось создать хештег: пустой обязательный параметр');
            //return response()->json(['status' => false, 'error' => 'err']);
        }

        $parent_id = 0;
        if (!empty($request->parent_id)) {
            $parent_id = $request->parent_id;
        }

        $search = new SearchController();
        $foundHashtags = $search->searchTagByTitle($request->title, $request->hashtags ?? '');

        if (!$foundHashtags->isEmpty()) {
            return redirect()->route('hashtags.index')->withErrors('Хештег уже сущетвует!');
            //return response()->json(['status' => false, 'hashtags' => $foundHashtags]);
        }

        $user = auth()->user();

        $newHashtag = new Hashtag();
        $newHashtag->title = $request->title;
        $newHashtag->parent_id = $parent_id;
        $newHashtag->user_id = $user->id;
        $newHashtagSave = $newHashtag->save();

        if ($newHashtagSave === true) {
            return redirect()->route('hashtags.index')->withSuccess('Хештег был успешно создан');
        }

        return redirect()->route('hashtags.index')->withErrors('Не удалось создать хештег');

//        return response()->json(['status' => true, 'message' => 'Хештег был успешно добавлен', 'info' => [
//            'id' => $newHashtag->id,
//            'title' => $newHashtag->title,
//        ]]);
    }

    /**
     * Display the specified resource.
     *
     * @param  \App\Models\Hashtag  $hashtag
     * @return \Illuminate\Http\Response
     */
    public function show(Hashtag $hashtag)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  \App\Models\Hashtag  $hashtag
     * @return \Illuminate\Http\Response
     */
    public function edit(Hashtag $hashtag)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\Hashtag  $hashtag
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, Hashtag $hashtag)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function destroy($id)
    {
        $post = Hashtag::where('id', $id)->delete();
        if ($post === 1) {
            return response()->json([
                'status' => true,
                'message' => 'Хештег был успешно удалён',
            ]);
        }

        return response()->json([
            'status' => false,
            'error' => 'Ошибка при удалении хештега',
        ]);
    }
}
