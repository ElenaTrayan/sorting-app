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
        $categories = PostsCategory::orderBy('title', 'asc')->get();

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
        return view('admin.categories.create');
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

        $new_category = new PostsCategory();
        $new_category->title = $request->title;
        $new_category->alias = $request->alias;
        $new_category->parent_id = $request->parent_id ?? 0;
        $new_category->user_id = $user->id;
        $new_category->short_description = $request->short_description;
        $new_category->save();

        return redirect()->back()->withSuccess('Категория была успешно добавлена');
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
        return view('admin.categories.edit', [
            'category' => $postsCategory
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
        $postsCategory->save();

        return redirect()->back()->withSuccess('Категория была успешно обновлена');
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Models\PostsCategory  $postsCategory
     * @return \Illuminate\Http\Response
     */
    public function destroy(PostsCategory $postsCategory)
    {
        $postsCategory->delete();

        return redirect()->back()->withSuccess('Категория была успешно удалена');
    }
}
