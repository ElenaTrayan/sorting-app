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
//Route::post('/search-hashtag',[App\Http\Controllers\Admin\Packages\SearchController::class, 'searchHashtag'])->name('search.hashtag');

Route::middleware(['role:admin'])->prefix('admin_panel')->group(function () {
    Route::get('/test', function () {
        return view('test');
    });
    Route::get('/', [App\Http\Controllers\Admin\HomeController::class, 'index'])->name('homeAdmin');
    Route::resource('posts-categories', \App\Http\Controllers\Admin\CategoryController::class);
    Route::resource('posts', \App\Http\Controllers\Admin\PostController::class);
    Route::resource('hashtags', \App\Http\Controllers\Admin\HashtagController::class);
    Route::post('upload', [App\Http\Controllers\Admin\Packages\UploadImageController::class, 'upload'])->name('image.upload');
    Route::post('delete-download-file', [App\Http\Controllers\Admin\Packages\UploadImageController::class, 'deleteDownloadFile']);
    Route::post('upload-image-to-temp-directory', [App\Http\Controllers\Admin\Packages\UploadImageController::class, 'uploadImageToTempDirectory'])->name('image.upload-to-temp-directory');
    Route::post('/search-hashtag',[App\Http\Controllers\Admin\Packages\SearchController::class, 'searchHashtag'])->name('search.hashtag');
    Route::post('/search-hashtag-by-title',[App\Http\Controllers\Admin\Packages\SearchController::class, 'searchSameHashtag'])->name('search.hashtag-by-title');
    Route::any('/search-posts-by-hashtags',[App\Http\Controllers\Admin\Packages\SearchController::class, 'searchPostsByHashtags'])->name('search.posts-by-hashtags');
//    Route::post('/posts-update-hashtags',[App\Http\Controllers\Admin\PostController::class, 'updatePostsHashtags'])->name('post-update-hashtags');
    Route::get('/media-parser', [App\Http\Controllers\Admin\ParserController::class, 'index'])->name('media-parser');
    Route::get('/instagram-parser', [App\Http\Controllers\Admin\ParserController::class, 'instagramParserSettings'])->name('instagram-parser-settings');
    Route::post('/instagram-parser-get-media', [App\Http\Controllers\Admin\ParserController::class, 'getMediaFilesFromInstagram'])->name('instagram-parser-get-media');
    Route::post('/get-parsed-post-info', [\App\Http\Controllers\Admin\PostController::class, 'getParsedPostInfo'])->name('parse.get-parsed-post-info');

    Route::post('/get-text-from-image', [\App\Http\Controllers\Admin\PostController::class, 'getTextFromImage'])->name('get-text-from-image');

    Route::get('/generate-docs', function(){

        $headers = array(
            "Content-type"=>"text/html",
            "Content-Disposition"=>"attachment;Filename=myfile.doc"
        );

        $content = '<html>

            <head><meta charset="utf-8"></head>

            <body>
                <h1>Title test</h1>

                <p style="color: #ff0050">My Content</p>

                <ul><li>Cat</li><li>Cat</li></ul>

            </body>

            </html>';

        return \Response::make($content,200, $headers);

    });

});

