<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Admin\Packages\SearchController;
use App\Http\Controllers\Controller;
use App\Models\Hashtag;
use Illuminate\Contracts\Foundation\Application as ApplicationAlias;
use Illuminate\Contracts\View\Factory as FactoryAlias;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class HashtagController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return ApplicationAlias|FactoryAlias|View
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
     * @return ApplicationAlias|FactoryAlias|View
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
     * @return JsonResponse|RedirectResponse
     */
    public function store(Request $request)
    {
        if (empty($request->title)) {
            return redirect()->route('hashtags.index')->withErrors('Не удалось создать хештег: пустой обязательный параметр');
        }

        $parent_id = 0;
        if (!empty($request->parent_id)) {
            $parent_id = $request->parent_id;
        }

        $search = new SearchController();
        $foundHashtags = $search->searchTagByTitle($request->title, $request->hashtags ?? '');
        if (!$foundHashtags->isEmpty()) {
            return redirect()->route('hashtags.index')->withErrors('Хештег уже сущетвует!');
        }

        $user = auth()->user();

        $newHashtag = new Hashtag();
        $newHashtag->title = $request->title;
        $newHashtag->parent_id = $parent_id;
        $newHashtag->user_id = $user->id;
        $newHashtagSave = $newHashtag->save();

        if ($newHashtagSave === true) {
            //return redirect()->route('hashtags.index')->withSuccess('Хештег был успешно создан');
            return response()->json(['status' => true,
                'message' => 'Хештег был успешно добавлен',
                'info' => [
                    'id' => $newHashtag->id,
                    'title' => $newHashtag->title,
                ]
            ]);
        }

        //return redirect()->route('hashtags.index')->withErrors('Не удалось создать хештег');
        return response()->json(['status' => false, 'message' => 'Хештег не был добавлен']);
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
     * @param Hashtag $hashtag
     * @return ApplicationAlias|FactoryAlias|View
     */
    public function edit(Hashtag $hashtag)
    {
        $user = auth()->user();

        $hashtag = Hashtag::where('user_id', $user->id)
            ->where('id', $hashtag->id)
            ->orderBy('title', 'asc')->first();

        $hashtags = Hashtag::where('parent_id', 0)
            ->where('user_id', $user->id)
            ->orderBy('title', 'asc')
            ->with([
                'children' => function ($q) {
                    $q->select(['id', 'title', 'parent_id', 'user_id']);
                },
                'children.children' => function ($q) {
                    $q->select(['id', 'title', 'parent_id', 'user_id']);
                },
                'children.children.children' => function ($q) {
                    $q->select(['id', 'title', 'parent_id', 'user_id']);
                },
            ])
            ->get();

        if (!empty($hashtag->associated_hashtags)) {
            $associatedHashtags = explode(',', $hashtag->associated_hashtags);

            $associated_hashtags = Hashtag::where('user_id', $user->id)
                ->whereIn('id', $associatedHashtags)
                ->orderBy('title', 'asc')
                ->get();
        }

        return view('admin.hashtags.edit', [
            'hashtag' => $hashtag,
            'hashtags' => $hashtags,
            'associated_hashtags' => $associated_hashtags ?? [],
        ]);
    }

    /**
     * Update the specified resource in storage.
     *
     * @param Request $request
     * @param Hashtag $hashtag
     * @return RedirectResponse
     */
    public function update(Request $request, Hashtag $hashtag)
    {
        $hashtag->title = $request->title;
        $hashtag->parent_id = $request->parent_id == '-' ? 0 : $request->parent_id;
        //$hashtag->associated_hashtags = $request->associated_hashtags;
        $hashtagSave = $hashtag->save();

        if ($hashtagSave === true) {
            return redirect()->route('hashtags.index')->withSuccess('Хештег был успешно обновлен');
        }

        return redirect()->route('hashtags.index')->withErrors('Не удалось обновить хештег');
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param $id
     * @return JsonResponse
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
