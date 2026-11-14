<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use App\Http\Controllers\User\Packages\UploadImageController;
use App\Models\Post;
use App\Models\PostProduct;
use App\Models\Product;
use App\Models\ShoppingListItem;
use App\Models\StockItem;
use App\Models\StockLocation;
use App\Models\Store;
use App\Models\Wishlist;
use App\Models\WishlistItem;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

/**
 * Пин "Товар" (post_products): "Заполнить по ссылке" на форме поста и кнопки на странице поста —
 * "В вишлист", "В список покупок", "На склад". Создают обычные записи этих разделов (дальше они живут своей
 * жизнью), а id созданной записи запоминается в post_products, чтобы кнопка показывала "уже там".
 */
class PostProductController extends Controller
{
    // категория товара (свободный текст пина) -> категория "Запасов" (Product::CATEGORIES)
    private const STOCK_CATEGORY_WORDS = [
        'cosmetics' => ['космет', 'уход', 'парфюм', 'макияж', 'волос', 'кож'],
        'medicine' => ['здоров', 'лекарств', 'аптек', 'витамин', 'бад'],
        'household' => ['бытов', 'химия', 'уборк', 'стирк'],
        'pet' => ['зоо', 'животн', 'кошк', 'собак'],
        'product' => ['еда', 'напит', 'продукт', 'чай', 'кофе', 'снек'],
    ];

    // категория "Запасов" -> место хранения по умолчанию (StockLocation::defaults, slug)
    private const STOCK_LOCATION_SLUGS = [
        'cosmetics' => 'cosmetics',
        'medicine' => 'medicine-cabinet',
        'household' => 'cleaning',
        'pet' => 'pets',
        'product' => 'pantry',
        'other' => 'other',
    ];

    /**
     * "Заполнить по ссылке": тот же разбор страницы магазина, что у вишлистов (og-теги, JSON-LD, микроразметка,
     * при защите от ботов — headless Chrome), но без привязки к сервису "Вишлисты"
     */
    public function linkPreview(Request $request)
    {
        // флаг для WishlistController: дождаться в headless Chrome ещё и характеристик с описанием
        $request->attributes->set('product_specs', true);
        $response = app(WishlistController::class)->linkPreview($request);

        // магазин — по адресу ссылки (config/shops.php), даже если страницу разобрать не удалось
        $store = $this->storeFromUrl((string)$request->input('url'));
        if ($store !== null && $response instanceof JsonResponse) {
            $response->setData(['store' => $store] + (array)$response->getData(true));
        }

        return $response;
    }

    /**
     * Название магазина по адресу товара: сначала свои магазины пользователя ("Запасы") с таким сайтом, потом
     * таблица config/shops.php. Если у пользователя магазин уже записан (в "Запасах" или в своих товарах) одним
     * из вариантов названия (aliases) — его написание, иначе название из таблицы. Незнакомый сайт — null.
     */
    private function storeFromUrl(string $url): ?string
    {
        $host = $this->normalizedHost($url);
        if ($host === null) {
            return null;
        }

        $userId = Auth::id();
        $userStores = Store::where('user_id', $userId)->get(['name', 'website']);

        foreach ($userStores as $store) {
            $storeHost = $store->website ? $this->normalizedHost($store->website) : null;
            if ($storeHost !== null && ($host === $storeHost || Str::endsWith($host, '.' . $storeHost))) {
                return $store->name;
            }
        }

        foreach ((array)config('shops.by_domain', []) as $shop) {
            if (!$this->hostMatchesAny($host, (array)($shop['domains'] ?? []))) {
                continue;
            }

            $aliases = array_map(function ($alias) {
                return mb_strtolower(trim($alias));
            }, array_merge([(string)$shop['name']], (array)($shop['aliases'] ?? [])));

            $knownNames = $userStores->pluck('name')->merge(
                PostProduct::whereHas('post', function ($q) use ($userId) {
                    $q->where('user_id', $userId);
                })->whereNotNull('store')->distinct()->pluck('store')
            );
            foreach ($knownNames as $name) {
                if (in_array(mb_strtolower(trim((string)$name)), $aliases, true)) {
                    return $name;
                }
            }

            return $shop['name'];
        }

        return null;
    }

    // "https://www.Temu.com/ua/..." -> "temu.com"; без схемы тоже ("temu.com/...") — так пишут в поле "Сайт" магазина
    private function normalizedHost(string $url): ?string
    {
        $url = trim($url);
        if ($url === '') {
            return null;
        }
        if (!preg_match('~^[a-z][a-z0-9+.-]*://~i', $url)) {
            $url = 'https://' . $url;
        }
        $host = strtolower((string)parse_url($url, PHP_URL_HOST));

        return $host !== '' ? preg_replace('~^www\.~', '', $host) : null;
    }

    // домен из config/shops.php: "temu.com" — сам домен и его поддомены, "amazon.*" — с любой зоной (amazon.co.uk)
    private function hostMatchesAny(string $host, array $domains): bool
    {
        foreach ($domains as $domain) {
            $domain = strtolower(trim($domain));
            $pattern = Str::endsWith($domain, '.*')
                ? '~(^|\.)' . preg_quote(substr($domain, 0, -2), '~') . '\.[a-z]{2,}(\.[a-z]{2,})?$~'
                : '~(^|\.)' . preg_quote($domain, '~') . '$~';
            if (preg_match($pattern, $host)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Картинка товара со страницы магазина — через сервер: браузер сам её не скачает (CORS), а форма поста
     * принимает файлы (post-product.js отдаёт её в page-files-drop, как перетащенную). Только http(s),
     * только картинки до 15 МБ и только внешние адреса (не локальная сеть)
     */
    public function imageProxy(Request $request)
    {
        $url = trim((string)$request->query('url'));
        $host = parse_url($url, PHP_URL_HOST);
        abort_unless(preg_match('~^https?://~i', $url) && $host, 422);

        $ip = gethostbyname($host);
        abort_unless(filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE), 422);

        try {
            $response = \Illuminate\Support\Facades\Http::timeout(15)
                ->withHeaders(['User-Agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36'])
                ->get($url);
        } catch (\Throwable $e) {
            abort(502);
        }

        $type = strtolower(trim(explode(';', (string)$response->header('Content-Type'))[0]));
        abort_unless($response->successful() && strpos($type, 'image/') === 0 && strlen($response->body()) <= 15 * 1024 * 1024, 422);

        return response($response->body(), 200, ['Content-Type' => $type]);
    }

    /**
     * Вишлисты пользователя и места хранения "Запасов" — для окон выбора на странице поста
     */
    public function options(Post $post): JsonResponse
    {
        $product = $this->productOf($post);
        $userId = Auth::id();

        $wishlists = Auth::user()->hasService('wishlists')
            ? Wishlist::where('user_id', $userId)->where('is_archived', false)->orderBy('created_at')->get(['id', 'title'])
            : collect();

        $locations = collect();
        $suggestedLocationId = null;
        if (Auth::user()->hasService('stock')) {
            StockLocation::ensureDefaultsFor($userId);
            $locations = StockLocation::where('user_id', $userId)->orderBy('sort_order')->get(['id', 'name', 'slug']);
            $slug = self::STOCK_LOCATION_SLUGS[$this->stockCategory($product)] ?? 'other';
            $suggestedLocationId = optional($locations->firstWhere('slug', $slug))->id ?? optional($locations->first())->id;
        }

        return response()->json([
            'status' => true,
            'wishlists' => $wishlists,
            'locations' => $locations->map->only(['id', 'name'])->values(),
            'suggested_location_id' => $suggestedLocationId,
        ]);
    }

    public function toWishlist(Request $request, Post $post): JsonResponse
    {
        abort_unless(Auth::user()->hasService('wishlists'), 403);
        $product = $this->productOf($post);
        $wishlist = $this->wishlistFromRequest($request);

        $this->createWishlistItem($post, $product, $wishlist);

        return response()->json([
            'status' => true,
            'message' => 'Добавлено в вишлист «' . $wishlist->title . '»',
            'url' => route('wishlists.show', $wishlist->id),
        ]);
    }

    /**
     * Вишлисты пользователя (не архивные) — для выбора "в какой" там, где поста-товара под рукой нет:
     * режим выбора на "Все посты" и быстрый просмотр поста (posts-wishlist.js)
     */
    public function wishlists(): JsonResponse
    {
        abort_unless(Auth::user()->hasService('wishlists'), 403);

        return response()->json([
            'status' => true,
            'wishlists' => Wishlist::where('user_id', Auth::id())->where('is_archived', false)->orderBy('created_at')->get(['id', 'title']),
        ]);
    }

    /**
     * "В вишлист" в режиме выбора на "Все посты": каждый отмеченный пост — отдельное желание. У поста-товара —
     * его цена, ссылка, категория и мнение (как у кнопки на странице поста), у остальных — название, текст и
     * первая картинка. Отмеченная стопка группы — одно желание, по её первому посту. Товары, которые уже есть
     * в этом вишлисте, второй раз не добавляются
     */
    public function bulkToWishlist(Request $request): JsonResponse
    {
        abort_unless(Auth::user()->hasService('wishlists'), 403);
        $request->validate([
            'ids' => 'required|array|max:500',
            'ids.*' => 'integer',
        ]);
        $wishlist = $this->wishlistFromRequest($request);

        $posts = Post::where('user_id', Auth::id())->whereIn('id', $request->ids)->with('product.wishlistItem')->get();
        $added = 0;
        $skipped = 0;
        foreach ($posts as $post) {
            $product = $post->product;
            if ($product && $product->wishlistItem && (int)$product->wishlistItem->wishlist_id === (int)$wishlist->id) {
                $skipped++;
                continue;
            }
            $this->createWishlistItem($post, $product, $wishlist);
            $added++;
        }

        $message = 'В вишлист «' . $wishlist->title . '»: ' . $added . ' ' . PostProduct::plural($added, 'желание', 'желания', 'желаний');
        if ($skipped) {
            $message .= ', уже были там: ' . $skipped;
        }

        return response()->json([
            'status' => true,
            'added' => $added,
            'skipped' => $skipped,
            'message' => $message,
            'url' => route('wishlists.show', $wishlist->id),
        ]);
    }

    // выбран вишлист — он (только свой); вишлистов нет — заводим новый с введённым названием
    private function wishlistFromRequest(Request $request): Wishlist
    {
        $request->validate(['wishlist_id' => 'nullable|integer', 'wishlist_title' => 'nullable|string|max:255']);

        if ($request->filled('wishlist_id')) {
            return Wishlist::where('user_id', Auth::id())->findOrFail($request->wishlist_id);
        }

        return Wishlist::create([
            'user_id' => Auth::id(),
            'title' => trim((string)$request->wishlist_title) ?: 'Мои желания',
        ]);
    }

    // запись вишлиста из поста; у поста-товара запоминаем её в post_products — кнопка покажет "В вишлисте"
    private function createWishlistItem(Post $post, ?PostProduct $product, Wishlist $wishlist): WishlistItem
    {
        $imageVariants = $this->postImageForWishlist($post);

        if ($product) {
            $title = $this->productTitle($post, $product);
            $description = $product->my_opinion;
        } else {
            $title = trim((string)$post->title) ?: 'Пост #' . $post->id;
            $description = trim(html_entity_decode(strip_tags((string)$post->content), ENT_QUOTES | ENT_HTML5, 'UTF-8'));
        }

        $item = WishlistItem::create([
            'wishlist_id' => $wishlist->id,
            'title' => Str::limit($title, 255, ''),
            'description' => $description ? Str::limit($description, 2000, '') : null,
            'url' => $product ? $product->shop_url : null,
            'price' => $product ? $product->price : null,
            'category' => $product && $product->category ? Str::limit($product->category, 100, '') : null,
            'image' => $imageVariants ? json_encode($imageVariants['image']) : null,
            'image_large' => $imageVariants && $imageVariants['image_large'] ? json_encode($imageVariants['image_large']) : null,
        ]);

        if ($product) {
            $product->update(['wishlist_item_id' => $item->id]);
        }

        return $item;
    }

    public function toShoppingList(Post $post): JsonResponse
    {
        abort_unless(Auth::user()->hasService('stock'), 403);
        $product = $this->productOf($post);

        $item = ShoppingListItem::create([
            'user_id' => Auth::id(),
            'product_id' => $this->stockProduct($post, $product)->id,
            'name' => Str::limit($this->productTitle($post, $product), 255, ''),
            'quantity' => 1,
            'unit' => 'шт',
            'note' => $this->shoppingNote($product),
            'source' => 'post',
        ]);

        $product->update(['shopping_list_item_id' => $item->id]);

        return response()->json([
            'status' => true,
            'message' => 'Добавлено в список покупок',
            'url' => route('stock.shopping-list.index'),
        ]);
    }

    public function toStock(Request $request, Post $post): JsonResponse
    {
        abort_unless(Auth::user()->hasService('stock'), 403);
        $product = $this->productOf($post);
        $request->validate([
            'location_id' => 'required|integer',
            'quantity' => 'nullable|numeric|min:0',
        ]);

        $userId = Auth::id();
        $location = StockLocation::where('user_id', $userId)->findOrFail($request->location_id);
        $stockProduct = $this->stockProduct($post, $product);
        $storeId = $product->store ? Store::firstOrCreate(['user_id' => $userId, 'name' => $product->store])->id : null;

        $item = StockItem::create([
            'user_id' => $userId,
            'product_id' => $stockProduct->id,
            'location_id' => $location->id,
            'store_id' => $storeId,
            'name' => $stockProduct->name,
            'brand' => $stockProduct->brand,
            'category' => $stockProduct->category,
            'quantity' => $request->input('quantity', 1) ?: 1,
            'unit' => 'шт',
            'purchased_at' => $product->purchased_at,
            'expires_at' => $product->expires_at,
            'price' => $product->price,
            'note' => $product->variant,
        ]);

        // на складе — значит, куплено: статус "Хочу"/пустой меняем на "Куплено", остальные (пользуюсь...) не трогаем
        $product->update([
            'stock_item_id' => $item->id,
            'status' => in_array($product->status, [null, 'want'], true) ? 'bought' : $product->status,
        ]);

        return response()->json([
            'status' => true,
            'message' => 'Добавлено на склад: ' . $location->name,
            'url' => route('stock.inventory.index'),
        ]);
    }

    // товар пина текущего пользователя; у чужого поста или без данных товара — 404
    private function productOf(Post $post): PostProduct
    {
        abort_unless((int)$post->user_id === (int)Auth::id(), 404);

        return PostProduct::firstOrCreate(['post_id' => $post->id], ['currency' => PostProduct::defaultCurrency()]);
    }

    private function productTitle(Post $post, PostProduct $product): string
    {
        $title = trim((string)$post->title);
        if ($title === '') {
            $title = trim(implode(' ', array_filter([$product->brand, $product->category]))) ?: 'Товар из поста #' . $post->id;
        }

        return $title;
    }

    private function stockCategory(PostProduct $product): string
    {
        $category = mb_strtolower((string)$product->category);
        foreach (self::STOCK_CATEGORY_WORDS as $key => $words) {
            foreach ($words as $word) {
                if ($category !== '' && mb_strpos($category, $word) !== false) {
                    return $key;
                }
            }
        }

        return 'other';
    }

    // товар "Запасов" (products) — тот же, если такой уже есть (Product::findOrCreateMatching)
    private function stockProduct(Post $post, PostProduct $product): Product
    {
        return Product::findOrCreateMatching(Auth::id(), [
            'name' => Str::limit($this->productTitle($post, $product), 255, ''),
            'brand' => $product->brand ? Str::limit($product->brand, 150, '') : null,
            'variant' => $product->variant,
            'barcode' => $product->article,
            'category' => $this->stockCategory($product),
        ]);
    }

    private function shoppingNote(PostProduct $product): ?string
    {
        $parts = array_filter([
            $product->variant,
            $product->priceLabel(),
            $product->store,
            $product->shop_url,
        ]);

        return $parts ? Str::limit(implode(' · ', $parts), 1000, '') : null;
    }

    // первая картинка поста — копией в папку вишлиста (у желания свои файлы, как при загрузке руками)
    private function postImageForWishlist(Post $post): ?array
    {
        $images = UploadImageController::normalizeImages($post->original_image);
        $first = reset($images);
        if (!$first || empty($first['path']) || !Storage::disk('local')->exists($first['path'])) {
            return null;
        }

        // storeUploadedImage() кладёт файл к себе — отдаём ему копию, чтобы картинка поста осталась на месте
        $copy = tempnam(sys_get_temp_dir(), 'wish');
        try {
            $source = Storage::disk('local')->path($first['path']);
            copy($source, $copy);
            $file = new UploadedFile($copy, basename($source), null, null, true);

            return app(WishlistController::class)->storeUploadedImage($file);
        } catch (\Throwable $e) {
            return null;
        } finally {
            if (is_file($copy)) {
                @unlink($copy);
            }
        }
    }
}
