<?php

use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
*/

Route::get('/', function () {
    return view('welcome');
});

Auth::routes();

Route::get('/home', [App\Http\Controllers\HomeController::class, 'index'])->name('home');

Route::middleware(['role:admin'])->prefix('admin_panel')->group(function () {
    Route::get('/test', function () {
        return view('test');
    });
    Route::get('/', [App\Http\Controllers\Admin\HomeController::class, 'index'])->name('homeAdmin');
    Route::resource('posts-categories', \App\Http\Controllers\Admin\CategoryController::class);
    Route::resource('posts', \App\Http\Controllers\Admin\PostController::class);
    Route::resource('hashtags', \App\Http\Controllers\Admin\HashtagController::class);
    Route::post('upload', [App\Http\Controllers\Admin\Packages\UploadImageController::class, 'upload'])->name('image.upload');
    Route::post('delete_download_file', [App\Http\Controllers\Admin\Packages\UploadImageController::class, 'deleteDownloadFile']);
    Route::post('upload-image-to-temp-directory', [App\Http\Controllers\Admin\Packages\UploadImageController::class, 'uploadImageToTempDirectory'])->name('image.upload-to-temp-directory');
    Route::post('/search-hashtag',[App\Http\Controllers\Admin\Packages\SearchController::class, 'searchHashtag'])->name('search.hashtag');
});

