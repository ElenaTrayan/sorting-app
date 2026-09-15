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

// Публичная (гостевая) страница вишлиста — без логина, доступна всем, у кого есть ссылка
Route::get('/wishlist/{uuid}', [App\Http\Controllers\WishlistPublicController::class, 'show'])->name('wishlist.public');
Route::post('/wishlist/{uuid}/items/{item}/reserve', [App\Http\Controllers\WishlistPublicController::class, 'toggleReserve'])->name('wishlist.public.toggle-reserve');

Route::middleware(['role:admin'])->prefix('admin_panel')->group(function () {
    Route::get('/test', function () {
        return view('test');
    });
    Route::get('/', [App\Http\Controllers\Admin\HomeController::class, 'index'])->name('homeAdmin');
    Route::resource('posts-categories', \App\Http\Controllers\Admin\CategoryController::class);
    Route::resource('posts', \App\Http\Controllers\Admin\PostController::class);
    Route::resource('hashtags', \App\Http\Controllers\Admin\HashtagController::class);
    Route::post('/hashtags/{hashtag}/related', [App\Http\Controllers\Admin\HashtagController::class, 'attachRelated'])->name('hashtags.related.attach');
    Route::delete('/hashtags/{hashtag}/related/{relatedHashtag}', [App\Http\Controllers\Admin\HashtagController::class, 'detachRelated'])->name('hashtags.related.detach');
    Route::post('/search-related-hashtags', [App\Http\Controllers\Admin\HashtagController::class, 'relatedFor'])->name('search.related-hashtags');

    // Мой список подарков (My Gift List)
    Route::get('/wishlists', [App\Http\Controllers\Admin\WishlistController::class, 'index'])->name('wishlists.index');
    Route::post('/wishlists', [App\Http\Controllers\Admin\WishlistController::class, 'store'])->name('wishlists.store');
    Route::get('/wishlists/{wishlist}', [App\Http\Controllers\Admin\WishlistController::class, 'show'])->name('wishlists.show');
    Route::put('/wishlists/{wishlist}', [App\Http\Controllers\Admin\WishlistController::class, 'update'])->name('wishlists.update');
    Route::delete('/wishlists/{wishlist}', [App\Http\Controllers\Admin\WishlistController::class, 'destroy'])->name('wishlists.destroy');
    Route::post('/wishlists/link-preview', [App\Http\Controllers\Admin\WishlistController::class, 'linkPreview'])->name('wishlists.link-preview');
    Route::post('/wishlists/{wishlist}/items', [App\Http\Controllers\Admin\WishlistController::class, 'storeItem'])->name('wishlists.items.store');
    Route::put('/wishlist-items/{item}', [App\Http\Controllers\Admin\WishlistController::class, 'updateItem'])->name('wishlists.items.update');
    Route::post('/wishlist-items/{item}/toggle-fulfilled', [App\Http\Controllers\Admin\WishlistController::class, 'toggleFulfilledByOwner'])->name('wishlists.items.toggle-fulfilled');
    Route::delete('/wishlist-items/{item}', [App\Http\Controllers\Admin\WishlistController::class, 'destroyItem'])->name('wishlists.items.destroy');
    Route::post('/wishlist-items/{item}/duplicate', [App\Http\Controllers\Admin\WishlistController::class, 'duplicateItem'])->name('wishlists.items.duplicate');

    // База контактов
    Route::get('/contacts', [App\Http\Controllers\Admin\ContactController::class, 'index'])->name('contacts.index');
    Route::post('/contacts', [App\Http\Controllers\Admin\ContactController::class, 'store'])->name('contacts.store');
    Route::post('/contacts/residence-search', [App\Http\Controllers\Admin\ContactController::class, 'searchResidence'])->name('contacts.residence.search');
    Route::get('/contacts/{contact}', [App\Http\Controllers\Admin\ContactController::class, 'show'])->name('contacts.show');
    Route::put('/contacts/{contact}', [App\Http\Controllers\Admin\ContactController::class, 'update'])->name('contacts.update');
    Route::delete('/contacts/{contact}', [App\Http\Controllers\Admin\ContactController::class, 'destroy'])->name('contacts.destroy');
    Route::post('/contacts/{contact}/photos', [App\Http\Controllers\Admin\ContactController::class, 'storePhoto'])->name('contacts.photos.store');
    Route::delete('/contact-photos/{photo}', [App\Http\Controllers\Admin\ContactController::class, 'destroyPhoto'])->name('contacts.photos.destroy');
    Route::post('/contacts/{contact}/conversations', [App\Http\Controllers\Admin\ContactController::class, 'storeConversation'])->name('contacts.conversations.store');
    Route::delete('/contact-conversations/{conversation}', [App\Http\Controllers\Admin\ContactController::class, 'destroyConversation'])->name('contacts.conversations.destroy');
    Route::post('/contacts/{contact}/socials', [App\Http\Controllers\Admin\ContactController::class, 'storeSocial'])->name('contacts.socials.store');
    Route::delete('/contact-socials/{social}', [App\Http\Controllers\Admin\ContactController::class, 'destroySocial'])->name('contacts.socials.destroy');
    Route::post('/contacts/{contact}/quotes', [App\Http\Controllers\Admin\ContactController::class, 'storeQuote'])->name('contacts.quotes.store');
    Route::delete('/contact-quotes/{quote}', [App\Http\Controllers\Admin\ContactController::class, 'destroyQuote'])->name('contacts.quotes.destroy');
    Route::post('/contacts/quotes/recognize-text', [App\Http\Controllers\Admin\ContactController::class, 'recognizeQuoteText'])->name('contacts.quotes.recognize');
    Route::post('/contacts/{contact}/facts', [App\Http\Controllers\Admin\ContactController::class, 'storeFact'])->name('contacts.facts.store');
    Route::delete('/contact-facts/{fact}', [App\Http\Controllers\Admin\ContactController::class, 'destroyFact'])->name('contacts.facts.destroy');
    Route::post('upload', [App\Http\Controllers\Admin\Packages\UploadImageController::class, 'upload'])->name('image.upload');
    Route::post('delete-download-file', [App\Http\Controllers\Admin\Packages\UploadImageController::class, 'deleteDownloadFile']);
    Route::post('upload-image-to-temp-directory', [App\Http\Controllers\Admin\Packages\UploadImageController::class, 'uploadImagesToTempDirectory'])->name('image.upload-to-temp-directory');
    Route::post('upload-video-to-temp-directory', [App\Http\Controllers\Admin\PostController::class, 'uploadVideoToTempDirectory'])->name('video.upload-to-temp-directory');
    Route::post('/posts/{post}/video', [App\Http\Controllers\Admin\PostController::class, 'addVideo'])->name('posts.video.add');
    Route::delete('/posts/{post}/video', [App\Http\Controllers\Admin\PostController::class, 'deleteVideo'])->name('posts.video.delete');
    Route::post('delete-post-file', [\App\Http\Controllers\Admin\PostController::class, 'deletePostFile'])->name('posts.images.delete');
    Route::post('/posts/{post}/images', [\App\Http\Controllers\Admin\PostController::class, 'addImage'])->name('posts.images.add');
    Route::post('/posts/{post}/images/confirm-replace-duplicate', [\App\Http\Controllers\Admin\PostController::class, 'confirmReplaceDuplicate'])->name('posts.images.confirm-replace-duplicate');
    Route::post('/posts/{post}/images/confirm-add-duplicate', [\App\Http\Controllers\Admin\PostController::class, 'confirmAddDuplicate'])->name('posts.images.confirm-add-duplicate');
    Route::post('/posts/{post}/images/rename', [\App\Http\Controllers\Admin\PostController::class, 'renameImage'])->name('posts.images.rename');
    Route::post('/search-hashtag',[App\Http\Controllers\Admin\Packages\SearchController::class, 'searchHashtag'])->name('search.hashtag');
    Route::post('/search-hashtag-by-title',[App\Http\Controllers\Admin\Packages\SearchController::class, 'searchSameHashtag'])->name('search.hashtag-by-title');
    Route::any('/search-posts-by-hashtags',[App\Http\Controllers\Admin\Packages\SearchController::class, 'searchPostsByHashtags'])->name('search.posts-by-hashtags');
    Route::get('/export-images-by-hashtags', [App\Http\Controllers\Admin\Packages\SearchController::class, 'exportImagesByHashtags'])->name('search.export-images-by-hashtags');
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

