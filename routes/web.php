<?php

use App\Http\Controllers\Admin;
use App\Http\Controllers\User;
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
//Route::post('/search-hashtag',[App\Http\Controllers\User\Packages\SearchController::class, 'searchHashtag'])->name('search.hashtag');

// Публичная (гостевая) страница вишлиста — без логина, доступна всем, у кого есть ссылка
Route::get('/wishlist/{uuid}', [App\Http\Controllers\WishlistPublicController::class, 'show'])->name('wishlist.public');
Route::post('/wishlist/{uuid}/items/{item}/reserve', [App\Http\Controllers\WishlistPublicController::class, 'toggleReserve'])->name('wishlist.public.toggle-reserve');

/*
|--------------------------------------------------------------------------
| Кабинет пользователя (/cabinet)
|--------------------------------------------------------------------------
|
| Рабочие разделы: посты, категории, хештеги, база контактов, My Gift List, "Мои запасы".
| Доступен любому вошедшему пользователю (в том числе админу — со своими данными), но
| каждый раздел открыт только если включён в подписке пользователя — админка →
| Пользователи → карточка (middleware "service:<ключ>", ключи — config/subscription.php).
| Заблокированных выкидывает глобальный EnsureUserIsNotBlocked (см. Http\Kernel).
|
*/
Route::middleware(['auth'])->prefix('cabinet')->group(function () {
    Route::get('/', [User\HomeController::class, 'index'])->name('cabinet.home');

    // Общие ajax-подсказки хештегов — нужны сразу нескольким разделам (посты, хештеги, контакты)
    // и поп-апу "Поиск" в меню на всех страницах кабинета; отдают только хештеги/посты самого пользователя
    Route::post('/search-hashtag', [User\Packages\SearchController::class, 'searchHashtag'])->name('search.hashtag');
    Route::post('/search-hashtag-by-title', [User\Packages\SearchController::class, 'searchSameHashtag'])->name('search.hashtag-by-title');
    Route::post('/search-related-hashtags', [User\HashtagController::class, 'relatedFor'])->name('search.related-hashtags');
    Route::any('/search-posts-by-hashtags', [User\Packages\SearchController::class, 'searchPostsByHashtags'])->name('search.posts-by-hashtags');

    // ------------------------------------------------------------------
    // Посты и категории (+ загрузка файлов, медиа-парсер)
    // ------------------------------------------------------------------
    Route::middleware('service:posts')->group(function () {
        Route::resource('posts-categories', User\CategoryController::class);
        Route::resource('posts', User\PostController::class);

        Route::post('upload', [User\Packages\UploadImageController::class, 'upload'])->name('image.upload');
        Route::post('delete-download-file', [User\Packages\UploadImageController::class, 'deleteDownloadFile']);
        Route::post('upload-image-to-temp-directory', [User\Packages\UploadImageController::class, 'uploadImagesToTempDirectory'])->name('image.upload-to-temp-directory');
        Route::post('upload-video-to-temp-directory', [User\PostController::class, 'uploadVideoToTempDirectory'])->name('video.upload-to-temp-directory');
        Route::post('/posts/{post}/video', [User\PostController::class, 'addVideo'])->name('posts.video.add');
        Route::delete('/posts/{post}/video', [User\PostController::class, 'deleteVideo'])->name('posts.video.delete');
        Route::post('delete-post-file', [User\PostController::class, 'deletePostFile'])->name('posts.images.delete');
        Route::post('/posts/{post}/images', [User\PostController::class, 'addImage'])->name('posts.images.add');
        Route::post('/posts/{post}/images/confirm-replace-duplicate', [User\PostController::class, 'confirmReplaceDuplicate'])->name('posts.images.confirm-replace-duplicate');
        Route::post('/posts/{post}/images/confirm-add-duplicate', [User\PostController::class, 'confirmAddDuplicate'])->name('posts.images.confirm-add-duplicate');
        Route::post('/posts/{post}/images/rename', [User\PostController::class, 'renameImage'])->name('posts.images.rename');
        // группа постов ("Группировать посты" при создании — см. PostController::store()/createPost());
        // группы отдельной таблицей не заводим, group_id = id первого поста группы
        Route::get('/posts/groups/{groupId}', [User\PostController::class, 'showGroup'])->name('posts.groups.show');
        Route::post('/posts/groups/{groupId}/reorder', [User\PostController::class, 'reorderGroup'])->name('posts.groups.reorder');
        Route::get('/export-images-by-hashtags', [User\Packages\SearchController::class, 'exportImagesByHashtags'])->name('search.export-images-by-hashtags');
//        Route::post('/posts-update-hashtags',[User\PostController::class, 'updatePostsHashtags'])->name('post-update-hashtags');
        Route::get('/media-parser', [User\ParserController::class, 'index'])->name('media-parser');
        Route::get('/instagram-parser', [User\ParserController::class, 'instagramParserSettings'])->name('instagram-parser-settings');
        Route::post('/instagram-parser-get-media', [User\ParserController::class, 'getMediaFilesFromInstagram'])->name('instagram-parser-get-media');
        Route::post('/get-parsed-post-info', [User\PostController::class, 'getParsedPostInfo'])->name('parse.get-parsed-post-info');

        Route::post('/get-text-from-image', [User\PostController::class, 'getTextFromImage'])->name('get-text-from-image');
    });

    // ------------------------------------------------------------------
    // Хештеги
    // ------------------------------------------------------------------
    Route::middleware('service:hashtags')->group(function () {
        // хештег по названию (существующий или новый) — чипы "Вайб" на форме поста, film-fields.js
        Route::post('/hashtags/find-or-create', [User\HashtagController::class, 'findOrCreate'])->name('hashtags.find-or-create');
        // хештеги из полей фильма (жанры, год, актёры...) — после парсинга и при правке полей, film-fields.js
        Route::post('/hashtags/for-film', [User\HashtagController::class, 'forFilm'])->name('hashtags.for-film');
        Route::resource('hashtags', User\HashtagController::class);
        Route::post('/hashtags/{hashtag}/related', [User\HashtagController::class, 'attachRelated'])->name('hashtags.related.attach');
        Route::delete('/hashtags/{hashtag}/related/{relatedHashtag}', [User\HashtagController::class, 'detachRelated'])->name('hashtags.related.detach');
    });

    // ------------------------------------------------------------------
    // Мой список подарков (My Gift List)
    // ------------------------------------------------------------------
    Route::middleware('service:wishlists')->group(function () {
        Route::get('/wishlists', [User\WishlistController::class, 'index'])->name('wishlists.index');
        Route::post('/wishlists', [User\WishlistController::class, 'store'])->name('wishlists.store');
        Route::get('/wishlists/{wishlist}', [User\WishlistController::class, 'show'])->name('wishlists.show');
        Route::put('/wishlists/{wishlist}', [User\WishlistController::class, 'update'])->name('wishlists.update');
        Route::delete('/wishlists/{wishlist}', [User\WishlistController::class, 'destroy'])->name('wishlists.destroy');
        Route::post('/wishlists/link-preview', [User\WishlistController::class, 'linkPreview'])->name('wishlists.link-preview');
        Route::post('/wishlists/{wishlist}/items', [User\WishlistController::class, 'storeItem'])->name('wishlists.items.store');
        Route::put('/wishlist-items/{item}', [User\WishlistController::class, 'updateItem'])->name('wishlists.items.update');
        Route::post('/wishlist-items/{item}/toggle-fulfilled', [User\WishlistController::class, 'toggleFulfilledByOwner'])->name('wishlists.items.toggle-fulfilled');
        Route::delete('/wishlist-items/{item}', [User\WishlistController::class, 'destroyItem'])->name('wishlists.items.destroy');
        Route::post('/wishlist-items/{item}/duplicate', [User\WishlistController::class, 'duplicateItem'])->name('wishlists.items.duplicate');
    });

    // ------------------------------------------------------------------
    // База контактов
    // ------------------------------------------------------------------
    Route::middleware('service:contacts')->group(function () {
        Route::get('/contacts', [User\ContactController::class, 'index'])->name('contacts.index');
        Route::post('/contacts', [User\ContactController::class, 'store'])->name('contacts.store');
        Route::post('/contacts/residence-search', [User\ContactController::class, 'searchResidence'])->name('contacts.residence.search');
        Route::get('/contacts/{contact}', [User\ContactController::class, 'show'])->name('contacts.show');
        Route::put('/contacts/{contact}', [User\ContactController::class, 'update'])->name('contacts.update');
        Route::delete('/contacts/{contact}', [User\ContactController::class, 'destroy'])->name('contacts.destroy');
        Route::post('/contacts/{contact}/photos', [User\ContactController::class, 'storePhoto'])->name('contacts.photos.store');
        Route::delete('/contact-photos/{photo}', [User\ContactController::class, 'destroyPhoto'])->name('contacts.photos.destroy');
        Route::post('/contacts/{contact}/conversations', [User\ContactController::class, 'storeConversation'])->name('contacts.conversations.store');
        Route::delete('/contact-conversations/{conversation}', [User\ContactController::class, 'destroyConversation'])->name('contacts.conversations.destroy');
        Route::post('/contacts/{contact}/socials', [User\ContactController::class, 'storeSocial'])->name('contacts.socials.store');
        Route::delete('/contact-socials/{social}', [User\ContactController::class, 'destroySocial'])->name('contacts.socials.destroy');
        Route::post('/contacts/{contact}/quotes', [User\ContactController::class, 'storeQuote'])->name('contacts.quotes.store');
        Route::delete('/contact-quotes/{quote}', [User\ContactController::class, 'destroyQuote'])->name('contacts.quotes.destroy');
        Route::post('/contacts/quotes/recognize-text', [User\ContactController::class, 'recognizeQuoteText'])->name('contacts.quotes.recognize');
        Route::post('/contacts/{contact}/facts', [User\ContactController::class, 'storeFact'])->name('contacts.facts.store');
        Route::delete('/contact-facts/{fact}', [User\ContactController::class, 'destroyFact'])->name('contacts.facts.destroy');
    });

    // ------------------------------------------------------------------
    // "Мои запасы" — домашние запасы, аптечка, покупки, магазины, акции,
    // заказы, резерв ЧС, история покупок (см. app/Http/Controllers/User/Stock)
    // ------------------------------------------------------------------
    Route::middleware('service:stock')->prefix('stock')->name('stock.')->group(function () {
        Route::get('/', [User\Stock\OverviewController::class, 'index'])->name('overview');

        Route::get('/inventory', [User\Stock\InventoryController::class, 'index'])->name('inventory.index');
        Route::post('/inventory', [User\Stock\InventoryController::class, 'store'])->name('inventory.store');
        Route::put('/inventory/{item}', [User\Stock\InventoryController::class, 'update'])->name('inventory.update');
        Route::delete('/inventory/{item}', [User\Stock\InventoryController::class, 'destroy'])->name('inventory.destroy');
        Route::post('/inventory/{item}/save-as-regular', [User\Stock\InventoryController::class, 'saveAsRegular'])->name('inventory.save-as-regular');
        Route::post('/inventory/{item}/add-to-shopping-list', [User\Stock\InventoryController::class, 'addToShoppingList'])->name('inventory.add-to-shopping-list');

        Route::get('/shopping-list', [User\Stock\ShoppingListController::class, 'index'])->name('shopping-list.index');
        Route::post('/shopping-list', [User\Stock\ShoppingListController::class, 'store'])->name('shopping-list.store');
        Route::put('/shopping-list/{item}', [User\Stock\ShoppingListController::class, 'update'])->name('shopping-list.update');
        Route::post('/shopping-list/{item}/toggle', [User\Stock\ShoppingListController::class, 'toggle'])->name('shopping-list.toggle');
        Route::delete('/shopping-list/{item}', [User\Stock\ShoppingListController::class, 'destroy'])->name('shopping-list.destroy');
        Route::post('/shopping-list/clear-purchased', [User\Stock\ShoppingListController::class, 'clearPurchased'])->name('shopping-list.clear-purchased');

        Route::get('/regular-purchases', [User\Stock\RegularPurchaseController::class, 'index'])->name('regular-purchases.index');
        Route::post('/regular-purchases', [User\Stock\RegularPurchaseController::class, 'store'])->name('regular-purchases.store');
        Route::put('/regular-purchases/{regularPurchase}', [User\Stock\RegularPurchaseController::class, 'update'])->name('regular-purchases.update');
        Route::delete('/regular-purchases/{regularPurchase}', [User\Stock\RegularPurchaseController::class, 'destroy'])->name('regular-purchases.destroy');
        Route::post('/regular-purchases/{regularPurchase}/search-offers', [User\Stock\RegularPurchaseController::class, 'searchOffers'])->name('regular-purchases.search-offers');

        Route::get('/stores', [User\Stock\StoreController::class, 'index'])->name('stores.index');
        Route::post('/stores', [User\Stock\StoreController::class, 'store'])->name('stores.store');
        Route::put('/stores/{store}', [User\Stock\StoreController::class, 'update'])->name('stores.update');
        Route::delete('/stores/{store}', [User\Stock\StoreController::class, 'destroy'])->name('stores.destroy');
        Route::post('/stores/{store}/toggle-monitoring', [User\Stock\StoreController::class, 'toggleMonitoring'])->name('stores.toggle-monitoring');

        Route::get('/promotions', [User\Stock\PromotionController::class, 'index'])->name('promotions.index');
        Route::post('/promotions/sync', [User\Stock\PromotionController::class, 'sync'])->name('promotions.sync');
        Route::post('/promotions', [User\Stock\PromotionController::class, 'store'])->name('promotions.store');
        Route::delete('/promotions/{promotion}', [User\Stock\PromotionController::class, 'destroy'])->name('promotions.destroy');
        Route::post('/promotions/{promotion}/add-to-shopping-list', [User\Stock\PromotionController::class, 'addToShoppingList'])->name('promotions.add-to-shopping-list');

        Route::get('/orders', [User\Stock\OrderController::class, 'index'])->name('orders.index');
        Route::get('/orders/{order}', [User\Stock\OrderController::class, 'show'])->name('orders.show');
        Route::post('/orders', [User\Stock\OrderController::class, 'store'])->name('orders.store');
        Route::put('/orders/{order}', [User\Stock\OrderController::class, 'update'])->name('orders.update');
        Route::delete('/orders/{order}', [User\Stock\OrderController::class, 'destroy'])->name('orders.destroy');
        Route::post('/orders/{order}/status', [User\Stock\OrderController::class, 'updateStatus'])->name('orders.update-status');
        Route::post('/orders/{order}/register-return', [User\Stock\OrderController::class, 'registerReturn'])->name('orders.register-return');
        Route::post('/order-items/{orderItem}/add-to-stock', [User\Stock\OrderController::class, 'addItemToStock'])->name('orders.items.add-to-stock');

        Route::get('/reserve', [User\Stock\ReserveController::class, 'index'])->name('reserve.index');
        Route::post('/reserve', [User\Stock\ReserveController::class, 'store'])->name('reserve.store');
        Route::put('/reserve/{reserveItem}', [User\Stock\ReserveController::class, 'update'])->name('reserve.update');
        Route::delete('/reserve/{reserveItem}', [User\Stock\ReserveController::class, 'destroy'])->name('reserve.destroy');
        Route::post('/reserve/{reserveItem}/add-to-shopping-list', [User\Stock\ReserveController::class, 'addToShoppingList'])->name('reserve.add-to-shopping-list');

        Route::get('/history', [User\Stock\HistoryController::class, 'index'])->name('history.index');
        Route::post('/history', [User\Stock\HistoryController::class, 'store'])->name('history.store');
        Route::get('/history/export', [User\Stock\HistoryController::class, 'export'])->name('history.export');

        Route::get('/settings', [User\Stock\SettingsController::class, 'index'])->name('settings.index');
        Route::put('/settings', [User\Stock\SettingsController::class, 'update'])->name('settings.update');
    });
});

/*
|--------------------------------------------------------------------------
| Админ-панель (/admin_panel) — только для роли admin
|--------------------------------------------------------------------------
|
| Управление приложением, а не контентом: пользователи (просмотр, блокировка,
| сервисы подписки), внешние API. Рабочие разделы (посты, контакты и т.д.) — в кабинете.
|
*/
Route::middleware(['auth', 'role:admin'])->prefix('admin_panel')->name('admin.')->group(function () {
    Route::get('/', [Admin\HomeController::class, 'index'])->name('dashboard');

    Route::get('/users', [Admin\UserController::class, 'index'])->name('users.index');
    Route::get('/users/{user}', [Admin\UserController::class, 'show'])->name('users.show');
    Route::post('/users/{user}/block', [Admin\UserController::class, 'block'])->name('users.block');
    Route::post('/users/{user}/unblock', [Admin\UserController::class, 'unblock'])->name('users.unblock');
    Route::put('/users/{user}/services', [Admin\UserController::class, 'updateServices'])->name('users.services.update');

    Route::get('/integrations', [Admin\IntegrationController::class, 'index'])->name('integrations.index');
    Route::get('/integrations/{key}', [Admin\IntegrationController::class, 'edit'])->name('integrations.edit');
    Route::put('/integrations/{key}', [Admin\IntegrationController::class, 'update'])->name('integrations.update');

    // dev-заготовки, остались как были
    Route::get('/test', function () {
        return view('test');
    })->name('test');

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

    })->name('generate-docs');

});
