<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\PostsCategory;
use Illuminate\Http\Request;

class CategoryController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        $user = auth()->user();

        //
        $categories = PostsCategory::where('parent_id', 0)
            ->where('user_id', $user->id)
            ->orderBy('title', 'asc')
            ->with([
                'children' => function ($q) {
                    $q->select(['id', 'alias', 'title', 'parent_id', 'user_id', 'status']);
                },
                'children.children' => function ($q) {
                    $q->select(['id', 'alias', 'title', 'parent_id', 'user_id', 'status']);
                },
                'children.children.children' => function ($q) {
                    $q->select(['id', 'alias', 'title', 'parent_id', 'user_id', 'status']);
                },
            ])
            ->get();

        //выводит сначала категории с parent_id 0,
        //а потом выводит остальные категории, отартированные по parent_id и title
//        $categories = PostsCategory::where('user_id', $user->id)
//            ->orderBy('parent_id', 'asc')
//            ->orderBy('title', 'asc')
//            ->with([
//                'parent' => function ($q) {
//                    $q->select(['id', 'alias', 'title', 'parent_id', 'user_id', 'status']);
//                },
//                'parent.parent' => function ($q) {
//                    $q->select(['id', 'alias', 'title', 'parent_id', 'user_id', 'status']);
//                },
//                'parent.parent.parent' => function ($q) {
//                    $q->select(['id', 'alias', 'title', 'parent_id', 'user_id', 'status']);
//                },
//            ])
//            ->get();

//        foreach ($categories as $key => $category) {
//            if (!empty($category->parent)) {
//                dd($category->parent->title);
//            }
//
////            $categories[$key]['parent_title'] = $category->parent->title ?? '';
//        }

        return view('admin.categories.index', [
            'categories' => $categories
        ]);
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        $user = auth()->user();

        $categories = PostsCategory::where('parent_id', 0)
            ->where('user_id', $user->id)
            ->orderBy('title', 'asc')
            ->with([
                'children' => function ($q) {
                    $q->select(['id', 'alias', 'title', 'parent_id', 'user_id', 'status']);
                },
                'children.children' => function ($q) {
                    $q->select(['id', 'alias', 'title', 'parent_id', 'user_id', 'status']);
                },
                'children.children.children' => function ($q) {
                    $q->select(['id', 'alias', 'title', 'parent_id', 'user_id', 'status']);
                },
            ])
            ->get();

        //dd($categories);

//        foreach ($categories as $key => $category) {
//            if (!empty($category->children)) {
//                dd($category->children);
//            }
//
////            $categories[$key]['parent_title'] = $category->parent->title ?? '';
//        }

        return view('admin.categories.create', [
            'categories' => $categories
        ]);
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param Request $request
     * @return \Illuminate\Http\RedirectResponse
     */
    public function store(Request $request)
    {
        $user = auth()->user();

        $new_category = new PostsCategory();
        $new_category->title = $request->title;
        $new_category->alias = $request->alias;
        $new_category->parent_id = $request->parent_id ?? 0;
        $new_category->user_id = $user->id;
        $new_category->short_description = $request->short_description;
        $categorySave = $new_category->save();

       // return redirect()->back()->withSuccess('Категория была успешно добавлена');

        if ($categorySave === true) {
            return redirect()->route('posts-categories.index')->withSuccess('Категория была успешно создана');
        }

        return redirect()->route('posts-categories.index')->withErrors('Не удалось создать категорию');
    }

    /**
     * Display the specified resource.
     *
     * @param  \App\Models\PostsCategory  $postsCategory
     * @return \Illuminate\Http\Response
     */
    public function show(PostsCategory $postsCategory)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  \App\Models\PostsCategory  $postsCategory
     * @return \Illuminate\Http\Response
     */
    public function edit(PostsCategory $postsCategory)
    {
        $user = auth()->user();

        $categories = PostsCategory::where('parent_id', 0)
            ->where('user_id', $user->id)
            ->orderBy('title', 'asc')
            ->with([
                'children' => function ($q) {
                    $q->select(['id', 'alias', 'title', 'parent_id', 'user_id', 'status']);
                },
                'children.children' => function ($q) {
                    $q->select(['id', 'alias', 'title', 'parent_id', 'user_id', 'status']);
                },
                'children.children.children' => function ($q) {
                    $q->select(['id', 'alias', 'title', 'parent_id', 'user_id', 'status']);
                },
            ])
            ->get();

        return view('admin.categories.edit', [
            'category' => $postsCategory,
            'categories' => $categories,
        ]);
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\PostsCategory  $postsCategory
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, PostsCategory $postsCategory)
    {
        $postsCategory->title = $request->title;
        $postsCategory->alias = $request->alias;
        $postsCategory->parent_id = $request->parent_id == '-' ? 0 : $request->parent_id;
        $postsCategory->short_description = $request->short_description;
        $categorySave = $postsCategory->save();

        if ($categorySave === true) {
            return redirect()->route('posts-categories.index')->withSuccess('Категория была успешно обновлена');
        }

        return redirect()->route('posts-categories.index')->withErrors('Не удалось обновить категорию');

        //return redirect()->back()->withSuccess('Категория была успешно обновлена');
//        return response()->json([
//            'errors' => 'Не удалось обновить категорию',
//        ]);
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function destroy($id)
    {
        $category = PostsCategory::where('id', $id)->delete();
        if ($category === 1) {
            return response()->json([
                'status' => true,
                'message' => 'Категория была успешно удалена',
            ]);
        }

        return response()->json([
            'status' => false,
            'error' => 'Ошибка при удалении категории',
        ]);
    }

    /**
     * @param string $str
     * @return string
     */
//    public function transliterate(string $str): string
//    {
//        return str_slug($str);
//    }
}
