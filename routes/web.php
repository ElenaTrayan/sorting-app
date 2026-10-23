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
| Рабочие разделы: посты, категории, хештеги, база контактов, My Gift List, "Мои запасы", "Мой гардероб".
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
    // вкладка "Поиск по цвету" поп-апа "Поиск" — посты, в палитре которых есть похожий цвет (?hex=D8C3A5&page=2)
    Route::get('/search-posts-by-color', [User\Packages\SearchController::class, 'searchPostsByColor'])->name('search.posts-by-color');
    // кружки той же вкладки — оттенки из палитр постов пользователя с числом постов
    Route::get('/search-collection-colors', [User\Packages\SearchController::class, 'collectionColors'])->name('search.collection-colors');

    // "Настройки ИИ" — свой ключ OpenAI для подбора хештегов по картинке (HashtagSuggester)
    Route::get('/ai-settings', [User\AiSettingsController::class, 'index'])->name('ai-settings.index');
    Route::put('/ai-settings', [User\AiSettingsController::class, 'update'])->name('ai-settings.update');
    Route::delete('/ai-settings/key', [User\AiSettingsController::class, 'destroyKey'])->name('ai-settings.destroy-key');

    // ------------------------------------------------------------------
    // Посты и категории (+ загрузка файлов, медиа-парсер)
    // ------------------------------------------------------------------
    Route::middleware('service:posts')->group(function () {
        Route::resource('posts-categories', User\CategoryController::class);
        // корзина постов (App\Components\PostTrash, user/posts/trash.blade.php). До Route::resource:
        // иначе /posts/trash попал бы в posts.show / posts.destroy как пост с id "trash"
        Route::get('/posts/trash', [User\PostController::class, 'trash'])->name('posts.trash');
        Route::delete('/posts/trash', [User\PostController::class, 'emptyTrash'])->name('posts.trash.empty');
        Route::post('/posts/trash/restore-all', [User\PostController::class, 'restoreAllTrashed'])->name('posts.trash.restore-all');
        Route::post('/posts/trash/groups/{groupId}/restore', [User\PostController::class, 'restoreTrashedGroup'])->name('posts.trash.restore-group');
        Route::post('/posts/trash/{postId}/restore', [User\PostController::class, 'restoreTrashed'])->name('posts.trash.restore');
        Route::delete('/posts/trash/groups/{groupId}', [User\PostController::class, 'purgeTrashedGroup'])->name('posts.trash.purge-group');
        Route::delete('/posts/trash/{postId}', [User\PostController::class, 'purgeTrashed'])->name('posts.trash.purge');
        Route::resource('posts', User\PostController::class);

        Route::post('upload', [User\Packages\UploadImageController::class, 'upload'])->name('image.upload');
        Route::post('delete-download-file', [User\Packages\UploadImageController::class, 'deleteDownloadFile']);
        Route::post('upload-image-to-temp-directory', [User\Packages\UploadImageController::class, 'uploadImagesToTempDirectory'])->name('image.upload-to-temp-directory');
        Route::post('replace-temp-image', [User\Packages\UploadImageController::class, 'replaceTempImage'])->name('image.replace-temp');
        Route::post('upload-video-to-temp-directory', [User\PostController::class, 'uploadVideoToTempDirectory'])->name('video.upload-to-temp-directory');
        Route::post('/posts/{post}/video', [User\PostController::class, 'addVideo'])->name('posts.video.add');
        Route::delete('/posts/{post}/video', [User\PostController::class, 'deleteVideo'])->name('posts.video.delete');
        Route::post('delete-post-file', [User\PostController::class, 'deletePostFile'])->name('posts.images.delete');
        Route::post('/posts/{post}/images', [User\PostController::class, 'addImage'])->name('posts.images.add');
        // палитра обложки, поправленная вручную (блок "Палитра" на странице редактирования, palette-editor.js)
        Route::get('/posts/{post}/palette', [User\PostController::class, 'showPalette'])->name('posts.palette.show');
        Route::post('/posts/{post}/palette', [User\PostController::class, 'updatePalette'])->name('posts.palette.update');
        Route::post('/posts/{post}/palette/reset', [User\PostController::class, 'resetPalette'])->name('posts.palette.reset');
        Route::post('/posts/{post}/images/confirm-replace-duplicate', [User\PostController::class, 'confirmReplaceDuplicate'])->name('posts.images.confirm-replace-duplicate');
        Route::post('/posts/{post}/images/confirm-add-duplicate', [User\PostController::class, 'confirmAddDuplicate'])->name('posts.images.confirm-add-duplicate');
        Route::post('/posts/{post}/merge-duplicate', [User\PostController::class, 'mergeDuplicate'])->name('posts.merge-duplicate');
        Route::post('/posts/{post}/suggest-hashtags', [User\PostController::class, 'suggestHashtagsForPost'])->name('posts.suggest-hashtags-for-post');
        Route::post('/posts/{post}/images/rename', [User\PostController::class, 'renameImage'])->name('posts.images.rename');
        // повернуть/отразить картинку поста вместе с её medium/small (кнопки на карточке картинки, страница редактирования)
        Route::post('/posts/{post}/images/transform', [User\PostController::class, 'transformImage'])->name('posts.images.transform');
        Route::post('/posts/{post}/images/crop', [User\PostController::class, 'cropImage'])->name('posts.images.crop');
        Route::post('/posts/{post}/images/restore', [User\PostController::class, 'restoreImage'])->name('posts.images.restore');
        // материалы фильма — посты, прикреплённые к пину "Фильм / Сериал" (блок "Материалы" на странице
        // фильма: parts/film_materials.blade.php, film-materials.js)
        Route::get('/posts/{post}/film-materials/candidates', [User\PostController::class, 'filmMaterialCandidates'])->name('posts.film-materials.candidates');
        Route::post('/posts/{post}/film-materials', [User\PostController::class, 'attachFilmMaterials'])->name('posts.film-materials.attach');
        Route::delete('/posts/{post}/film-materials/{materialId}', [User\PostController::class, 'detachFilmMaterial'])->name('posts.film-materials.detach');
        // группа постов ("Группировать посты" при создании — см. PostController::store()/createPost());
        // действия сразу с несколькими постами — режим "Выбрать" на "Все посты" (/admin/posts-bulk.js)
        Route::post('/posts/bulk/hashtags', [User\PostController::class, 'bulkAddHashtags'])->name('posts.bulk.hashtags');
        Route::post('/posts/bulk/group', [User\PostController::class, 'bulkGroup'])->name('posts.bulk.group');
        Route::post('/posts/bulk/category', [User\PostController::class, 'bulkSetCategory'])->name('posts.bulk.category');
        Route::post('/posts/bulk/delete', [User\PostController::class, 'bulkDelete'])->name('posts.bulk.delete');
        // отметка "Использовано": панель выбора, пункт меню карточки, кнопка на странице группы
        Route::post('/posts/bulk/used', [User\PostController::class, 'bulkSetUsed'])->name('posts.bulk.used');
        // группы отдельной таблицей не заводим, group_id = id одного из постов группы. Страница группы —
        // и просмотр, и редактирование её постов (user/posts/group.blade.php, /admin/post-group.js)
        Route::get('/posts/groups/{groupId}', [User\PostController::class, 'showGroup'])->name('posts.groups.show');
        Route::post('/posts/groups/{groupId}', [User\PostController::class, 'updateGroup'])->name('posts.groups.update');
        Route::delete('/posts/groups/{groupId}', [User\PostController::class, 'dissolveGroup'])->name('posts.groups.dissolve');
        Route::post('/posts/groups/{groupId}/reorder', [User\PostController::class, 'reorderGroup'])->name('posts.groups.reorder');
        // скачать картинки и видео: поста (один файл как есть, несколько — архивом) и группы (архив по порядку)
        Route::get('/posts/{post}/download', [User\PostController::class, 'download'])->name('posts.download');
        Route::get('/posts/groups/{groupId}/download', [User\PostController::class, 'downloadGroup'])->name('posts.groups.download');
        // экспорт группы текстов в файл: каждый пост — абзац
        Route::get('/posts/groups/{groupId}/export/{format}', [User\PostController::class, 'exportGroup'])->where('format', 'docx|pdf')->name('posts.groups.export');
        Route::get('/posts/groups/{groupId}/candidates', [User\PostController::class, 'groupCandidates'])->name('posts.groups.candidates');
        Route::post('/posts/groups/{groupId}/posts', [User\PostController::class, 'attachToGroup'])->name('posts.groups.attach');
        Route::post('/posts/groups/{groupId}/images', [User\PostController::class, 'uploadToGroup'])->name('posts.groups.upload');
        Route::post('/posts/groups/{groupId}/texts', [User\PostController::class, 'addTextToGroup'])->name('posts.groups.add-text');
        // убрать пост из группы; ?delete=1 — удалить его совсем
        Route::delete('/posts/groups/{groupId}/posts/{postId}', [User\PostController::class, 'detachFromGroup'])->name('posts.groups.detach');
        Route::post('/posts/{post}/group', [User\PostController::class, 'startGroup'])->name('posts.groups.start');
        Route::get('/export-images-by-hashtags', [User\Packages\SearchController::class, 'exportImagesByHashtags'])->name('search.export-images-by-hashtags');
//        Route::post('/posts-update-hashtags',[User\PostController::class, 'updatePostsHashtags'])->name('post-update-hashtags');
        Route::get('/media-parser', [User\ParserController::class, 'index'])->name('media-parser');
        Route::get('/instagram-parser', [User\ParserController::class, 'instagramParserSettings'])->name('instagram-parser-settings');
        Route::post('/instagram-parser-get-media', [User\ParserController::class, 'getMediaFilesFromInstagram'])->name('instagram-parser-get-media');
        Route::post('/get-parsed-post-info', [User\PostController::class, 'getParsedPostInfo'])->name('parse.get-parsed-post-info');

        Route::post('/get-text-from-image', [User\PostController::class, 'getTextFromImage'])->name('get-text-from-image');
        Route::post('/suggest-hashtags', [User\PostController::class, 'suggestHashtags'])->name('posts.suggest-hashtags');
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
        Route::patch('/hashtags/{hashtag}/related/{relatedHashtag}', [User\HashtagController::class, 'updateRelated'])->name('hashtags.related.update');
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

    // ------------------------------------------------------------------
    // "Мой гардероб" — одежда (что есть и что нужно купить) и образы из неё
    // (см. app/Http/Controllers/User/Wardrobe)
    // ------------------------------------------------------------------
    Route::middleware('service:wardrobe')->prefix('wardrobe')->name('wardrobe.')->group(function () {
        Route::get('/', [User\Wardrobe\ItemController::class, 'index'])->name('items.index');
        Route::post('/items', [User\Wardrobe\ItemController::class, 'store'])->name('items.store');
        Route::put('/items/{wardrobeItem}', [User\Wardrobe\ItemController::class, 'update'])->name('items.update');
        Route::delete('/items/{wardrobeItem}', [User\Wardrobe\ItemController::class, 'destroy'])->name('items.destroy');
        Route::post('/items/{wardrobeItem}/bought', [User\Wardrobe\ItemController::class, 'markBought'])->name('items.bought');
        // превью вещи для галереи (клик по фото карточки — Fancybox, как "Все посты")
        Route::get('/items/{wardrobeItem}/preview', [User\Wardrobe\ItemController::class, 'preview'])->name('items.preview');
        // картинки постов пользователя для окна "Выбрать из постов" (?search=...&page=2)
        Route::get('/post-images', [User\Wardrobe\ItemController::class, 'postImages'])->name('post-images');
        // "Импорт из пина" в форме новой вещи: картинки и поля пина "Одежда" (?post=ID)
        Route::get('/post-import', [User\Wardrobe\ItemController::class, 'postImport'])->name('post-import');

        // места хранения — свой список пользователя (тремпель, ящик, коробка, органайзер...)
        Route::get('/storages', [User\Wardrobe\StorageController::class, 'index'])->name('storages.index');
        Route::post('/storages', [User\Wardrobe\StorageController::class, 'store'])->name('storages.store');
        Route::put('/storages/{wardrobeStorage}', [User\Wardrobe\StorageController::class, 'update'])->name('storages.update');
        Route::delete('/storages/{wardrobeStorage}', [User\Wardrobe\StorageController::class, 'destroy'])->name('storages.destroy');

        Route::get('/outfits', [User\Wardrobe\OutfitController::class, 'index'])->name('outfits.index');
        Route::get('/outfits/create', [User\Wardrobe\OutfitController::class, 'create'])->name('outfits.create');
        Route::post('/outfits', [User\Wardrobe\OutfitController::class, 'store'])->name('outfits.store');
        Route::get('/outfits/{wardrobeOutfit}', [User\Wardrobe\OutfitController::class, 'show'])->name('outfits.show');
        // превью образа для галереи (клик по обложке карточки — Fancybox, как у вещей и "Все посты")
        Route::get('/outfits/{wardrobeOutfit}/preview', [User\Wardrobe\OutfitController::class, 'preview'])->name('outfits.preview');
        Route::get('/outfits/{wardrobeOutfit}/edit', [User\Wardrobe\OutfitController::class, 'edit'])->name('outfits.edit');
        Route::put('/outfits/{wardrobeOutfit}', [User\Wardrobe\OutfitController::class, 'update'])->name('outfits.update');
        Route::delete('/outfits/{wardrobeOutfit}', [User\Wardrobe\OutfitController::class, 'destroy'])->name('outfits.destroy');
        Route::post('/outfits/{wardrobeOutfit}/wears', [User\Wardrobe\OutfitController::class, 'storeWear'])->name('outfits.wears.store');
        Route::delete('/outfit-wears/{wardrobeWear}', [User\Wardrobe\OutfitController::class, 'destroyWear'])->name('outfits.wears.destroy');
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

    // общий список брендов "Моего гардероба" и бренды, добавленные пользователями
    Route::get('/wardrobe-categories', [Admin\WardrobeCategoryController::class, 'index'])->name('wardrobe-categories.index');
    Route::post('/wardrobe-categories', [Admin\WardrobeCategoryController::class, 'store'])->name('wardrobe-categories.store');
    Route::put('/wardrobe-categories/{category}', [Admin\WardrobeCategoryController::class, 'update'])->name('wardrobe-categories.update');
    Route::post('/wardrobe-categories/{category}/move', [Admin\WardrobeCategoryController::class, 'move'])->name('wardrobe-categories.move');
    Route::delete('/wardrobe-categories/{category}', [Admin\WardrobeCategoryController::class, 'destroy'])->name('wardrobe-categories.destroy');

    Route::get('/wardrobe-brands',[Admin\WardrobeBrandController::class, 'index'])->name('wardrobe-brands.index');
    Route::post('/wardrobe-brands', [Admin\WardrobeBrandController::class, 'store'])->name('wardrobe-brands.store');
    Route::put('/wardrobe-brands/{brand}', [Admin\WardrobeBrandController::class, 'update'])->name('wardrobe-brands.update');
    Route::post('/wardrobe-brands/{brand}/share', [Admin\WardrobeBrandController::class, 'share'])->name('wardrobe-brands.share');
    Route::delete('/wardrobe-brands/{brand}', [Admin\WardrobeBrandController::class, 'destroy'])->name('wardrobe-brands.destroy');

    // общий справочник принтов одежды (группа, название, образец) и принты, добавленные пользователями
    Route::get('/wardrobe-colors', [Admin\WardrobeColorController::class, 'index'])->name('wardrobe-colors.index');
    Route::post('/wardrobe-colors', [Admin\WardrobeColorController::class, 'store'])->name('wardrobe-colors.store');
    Route::put('/wardrobe-colors/{entry}', [Admin\WardrobeColorController::class, 'update'])->name('wardrobe-colors.update');
    Route::post('/wardrobe-colors/{entry}/share', [Admin\WardrobeColorController::class, 'share'])->name('wardrobe-colors.share');
    Route::delete('/wardrobe-colors/{entry}', [Admin\WardrobeColorController::class, 'destroy'])->name('wardrobe-colors.destroy');

    Route::get('/wardrobe-prints',[Admin\WardrobePrintController::class, 'index'])->name('wardrobe-prints.index');
    Route::post('/wardrobe-prints', [Admin\WardrobePrintController::class, 'store'])->name('wardrobe-prints.store');
    Route::put('/wardrobe-prints/{entry}', [Admin\WardrobePrintController::class, 'update'])->name('wardrobe-prints.update');
    Route::post('/wardrobe-prints/{entry}/share', [Admin\WardrobePrintController::class, 'share'])->name('wardrobe-prints.share');
    Route::delete('/wardrobe-prints/{entry}', [Admin\WardrobePrintController::class, 'destroy'])->name('wardrobe-prints.destroy');

    // то же для материалов одежды (оба контроллера — Admin\WardrobeDirectoryController)
    Route::get('/wardrobe-materials', [Admin\WardrobeMaterialController::class, 'index'])->name('wardrobe-materials.index');
    Route::post('/wardrobe-materials', [Admin\WardrobeMaterialController::class, 'store'])->name('wardrobe-materials.store');
    Route::put('/wardrobe-materials/{entry}', [Admin\WardrobeMaterialController::class, 'update'])->name('wardrobe-materials.update');
    Route::post('/wardrobe-materials/{entry}/share', [Admin\WardrobeMaterialController::class, 'share'])->name('wardrobe-materials.share');
    Route::delete('/wardrobe-materials/{entry}', [Admin\WardrobeMaterialController::class, 'destroy'])->name('wardrobe-materials.destroy');

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
