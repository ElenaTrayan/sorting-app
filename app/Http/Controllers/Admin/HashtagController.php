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
use Illuminate\Support\Facades\DB;

class HashtagController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @param Request $request
     * @return ApplicationAlias|FactoryAlias|View
     */
    public function index(Request $request)
    {
        $user = auth()->user();

        $hashtags = Hashtag::where('user_id', $user->id)->orderBy('title', 'asc')->get();

        // группируем по первой букве названия (регистронезависимо);
        // т.к. $hashtags уже отсортирован по title, порядок букв внутри группировки
        // остаётся алфавитным сам по себе
        $groupedHashtags = $hashtags->groupBy(function ($hashtag) {
            $firstChar = mb_substr(trim($hashtag->title), 0, 1);
            return mb_strtoupper($firstChar, 'UTF-8');
        });

        $viewData = [
            'hashtags' => $hashtags,
            'groupedHashtags' => $groupedHashtags,
        ];

        // при AJAX-запросе отдаём только тело списка — так после удаления
        // хештега можно перезапросить и обновить блок целиком (пропадут
        // опустевшие буквы, обновится счётчик), не перезагружая страницу
        if ($request->ajax()) {
            return view('admin.hashtags.parts.hashtags_list', $viewData);
        }

        return view('admin.hashtags.index', $viewData);
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
            // связанные хештеги, выбранные прямо на форме создания — привязываем сразу,
            // одним действием вместе с созданием самого хештега
            if (!empty($request->related_hashtag_ids) && is_array($request->related_hashtag_ids)) {
                foreach ($request->related_hashtag_ids as $relatedHashtagId) {
                    $newHashtag->attachRelated((int)$relatedHashtagId);
                }
            }

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

        $relatedHashtags = $hashtag->relatedHashtags()->orderBy('title', 'asc')->get();

        return view('admin.hashtags.edit', [
            'hashtag' => $hashtag,
            'hashtags' => $hashtags,
            'relatedHashtags' => $relatedHashtags,
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
        // чистим связи "связанные хештеги" в обе стороны — FK-каскада на этой таблице нет
        DB::table('hashtag_relations')
            ->where('hashtag_id', $id)
            ->orWhere('related_hashtag_id', $id)
            ->delete();

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

    /**
     * Добавить связанный хэштег (записывается симметрично, в обе стороны).
     *
     * @param Request $request
     * @param Hashtag $hashtag
     * @return JsonResponse
     */
    public function attachRelated(Request $request, Hashtag $hashtag)
    {
        $relatedHashtagId = (int)$request->input('related_hashtag_id');

        $relatedHashtag = Hashtag::where('user_id', $hashtag->user_id)
            ->where('id', $relatedHashtagId)
            ->first();

        if (empty($relatedHashtag) || $relatedHashtag->id === $hashtag->id) {
            return response()->json(['status' => false, 'message' => 'Хештег не найден']);
        }

        $hashtag->attachRelated($relatedHashtag->id);

        return response()->json([
            'status' => true,
            'hashtag' => [
                'id' => $relatedHashtag->id,
                'title' => $relatedHashtag->title,
            ],
        ]);
    }

    /**
     * Убрать связанный хэштег (удаляется симметрично, в обе стороны).
     *
     * @param Hashtag $hashtag
     * @param Hashtag $relatedHashtag
     * @return JsonResponse
     */
    public function detachRelated(Hashtag $hashtag, Hashtag $relatedHashtag)
    {
        $hashtag->detachRelated($relatedHashtag->id);

        return response()->json(['status' => true]);
    }

    /**
     * Связанные хэштеги для набора выбранных хэштегов (используется в блоке
     * "Связанные" при поиске постов по хэштегам — показывает объединение
     * связанных хэштегов всех хэштегов, выбранных в данный момент).
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function relatedFor(Request $request)
    {
        $user = auth()->user();

        $selectedIds = array_keys($request->hashtags ?? []);

        if (empty($selectedIds)) {
            return response()->json(['status' => true, 'hashtags' => []]);
        }

        // связи в hashtag_relations хранятся симметрично (см. Hashtag::attachRelated()) —
        // для каждой пары A↔B есть обе строки (A,B) и (B,A), поэтому достаточно
        // одного whereIn по hashtag_id, чтобы получить связанные в любом направлении
        $relatedIds = DB::table('hashtag_relations')
            ->whereIn('hashtag_id', $selectedIds)
            ->pluck('related_hashtag_id')
            ->unique();

        $related = Hashtag::where('user_id', $user->id)
            ->whereIn('id', $relatedIds)
            ->whereNotIn('id', $selectedIds)
            ->orderBy('title', 'asc')
            ->get(['id', 'title']);

        return response()->json(['status' => true, 'hashtags' => $related]);
    }
}
