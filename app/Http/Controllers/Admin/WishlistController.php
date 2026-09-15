<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Wishlist;
use App\Models\WishlistItem;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Intervention\Image\Facades\Image;
use HeadlessChromium\BrowserFactory;
use HeadlessChromium\Page;
use GuzzleHttp\Cookie\CookieJar;

/**
 * Раздел "Мой список подарков" (сайдбар "My Gift List") — управление вишлистами
 * и желаниями в них. Публичная (гостевая) сторона — см. WishlistPublicController.
 */
class WishlistController extends Controller
{
    /**
     * Список вишлистов текущего пользователя — открывается по клику на "My Gift List".
     */
    public function index()
    {
        $wishlists = Wishlist::where('user_id', Auth::id())
            ->withCount([
                'items',
                'items as reserved_items_count' => function ($query) {
                    $query->where('is_reserved', true);
                },
            ])
            ->latest()
            ->get();

        return view('admin.wishlists.index', [
            'wishlists' => $wishlists,
            'occasions' => config('wishlist.occasions'),
        ]);
    }

    /**
     * Создать новый вишлист (кнопка "Создать вишлист" на главной раздела).
     */
    public function store(Request $request)
    {
        $request->validate([
            'title' => 'nullable|string|max:255',
            'occasion' => 'nullable|string|max:100',
        ]);

        $wishlist = Wishlist::create([
            'user_id' => Auth::id(),
            'title' => $request->title ?: 'Мой список подарков',
            'occasion' => $request->occasion,
        ]);

        return response()->json([
            'status' => true,
            'redirect' => route('wishlists.show', $wishlist->id),
        ]);
    }

    /**
     * Страница вишлиста (владелец) — управление желаниями, шаринг, фильтры.
     */
    public function show(Wishlist $wishlist)
    {
        $this->authorizeOwner($wishlist);

        $wishlist->load(['items' => function ($q) {
            $q->orderBy('created_at');
        }]);

        // магазины (по домену ссылки), встречающиеся среди желаний этого вишлиста —
        // для группы "По магазину" в панели фильтров
        $storeDomains = $wishlist->items->pluck('url_domain')->filter()->unique()->sort()->values();

        return view('admin.wishlists.show', [
            'wishlist' => $wishlist,
            'occasions' => config('wishlist.occasions'),
            'categories' => config('wishlist.categories'),
            'priorities' => config('wishlist.priorities'),
            'storeDomains' => $storeDomains,
            'publicUrl' => route('wishlist.public', $wishlist->uuid),
        ]);
    }

    /**
     * Обновить название/описание/повод вишлиста.
     */
    public function update(Request $request, Wishlist $wishlist)
    {
        $this->authorizeOwner($wishlist);

        $request->validate([
            'title' => 'nullable|string|max:255',
            'description' => 'nullable|string|max:2000',
            'occasion' => 'nullable|string|max:100',
        ]);

        $wishlist->update([
            'title' => $request->title ?: $wishlist->title,
            'description' => $request->description,
            'occasion' => $request->occasion,
        ]);

        return response()->json(['status' => true, 'message' => 'Вишлист обновлён']);
    }

    /**
     * Удалить вишлист целиком (вместе со всеми желаниями и их картинками).
     */
    public function destroy(Wishlist $wishlist)
    {
        $this->authorizeOwner($wishlist);

        foreach ($wishlist->items as $item) {
            $this->deleteItemImage($item);
        }

        $wishlist->delete();

        return response()->json(['status' => true, 'message' => 'Вишлист удалён']);
    }

    /**
     * Попытаться вытащить название/картинку/цену со страницы по ссылке (кнопка
     * "Найти в магазинах" в попапе "Добавить желание") — по тегам Open Graph, JSON-LD и
     * Microdata, которые отдаёт большинство интернет-магазинов (см. parseOpenGraphMeta).
     * Если обычный запрос ничего не нашёл — часть магазинов защищена от ботов (AWS WAF,
     * Cloudflare и т.п.) и вместо страницы отдают JS-челлендж, который проходит только
     * настоящий браузер — пробуем ещё раз через headless Chrome (см. fetchHtmlViaHeadlessBrowser).
     * Если и это не помогло — возвращаем то, что удалось найти (может быть частично пусто),
     * фронт даёт дозаполнить руками.
     */
    public function linkPreview(Request $request)
    {
        $request->validate([
            'url' => 'required|string|max:1000',
        ]);

        $url = trim($request->url);

        if (!preg_match('#^https?://#i', $url)) {
            return response()->json(['status' => false, 'message' => 'Это не похоже на ссылку']);
        }

        $html = null;
        $contentType = null;

        try {
            // общая cookie-«банка» на оба запроса ниже: некоторые магазины (замечено на
            // Bitrix-сайтах вроде miraton.ua) на самый первый запрос новой сессии отвечают
            // пустой страницей (просто выставляют cookie сессии, PHPSESSID) и отдают
            // реальный контент только со второго запроса, если в нём эту cookie вернуть —
            // обычный браузер получает такой же пустой ответ на первую навигацию, но у него
            // обычно уже есть cookie домена от предыдущего визита (с главной страницы,
            // из каталога и т.п.), поэтому проблема заметна только при прямом переходе
            // по ссылке на товар, как в вишлисте
            $cookieJar = new CookieJar();
            $client = Http::timeout(8)
                ->withHeaders([
                    'User-Agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36',
                ])
                ->withOptions(['cookies' => $cookieJar]);

            $response = $client->get($url);

            if ($response->successful() && strlen(trim($response->body())) < 200) {
                $response = $client->get($url);
            }

            if ($response->successful()) {
                $html = $response->body();
                $contentType = $response->header('Content-Type');
            }
        } catch (\Throwable $e) {
            // сайт не ответил обычным запросом — попробуем через headless-браузер ниже
        }

        $meta = $html
            ? $this->parseOpenGraphMeta($html, $contentType)
            : ['title' => null, 'description' => null, 'image' => null, 'image_candidates' => [], 'price' => null];

        if (empty($meta['title']) && empty($meta['image']) && empty($meta['price'])) {
            $renderedHtml = $this->fetchHtmlViaHeadlessBrowser($url);
            if ($renderedHtml) {
                $renderedMeta = $this->parseOpenGraphMeta($renderedHtml, 'text/html; charset=UTF-8');
                if (!empty($renderedMeta['title']) || !empty($renderedMeta['image']) || !empty($renderedMeta['price'])) {
                    $meta = $renderedMeta;
                }
            }
        }

        if (empty($meta['title']) && empty($meta['image']) && empty($meta['price'])) {
            return response()->json(['status' => false, 'message' => 'Не удалось найти данные о товаре']);
        }

        // среди всех найденных вариантов картинки (og-теги/JSON-LD/микроразметка) выбираем
        // реально самый качественный — приоритет источника (см. parseOpenGraphMeta) не
        // гарантирует лучшее разрешение, см. pickBestQualityImageUrl
        if (!empty($meta['image_candidates'])) {
            $meta['image'] = $this->pickBestQualityImageUrl($meta['image_candidates'], $meta['image']);
        }

        return response()->json([
            'status' => true,
            'title' => $meta['title'],
            'description' => $meta['description'],
            'image_url' => $meta['image'],
            'price' => $meta['price'],
        ]);
    }

    /**
     * Рендерит страницу настоящим headless-браузером (Chrome через DevTools Protocol,
     * пакет chrome-php/chrome) и возвращает итоговый HTML. Нужен как запасной вариант
     * для магазинов с защитой от ботов (AWS WAF, Cloudflare и т.п.) — обычный HTTP-запрос
     * (Http::get выше) получает от них не страницу, а JS-челлендж, который может пройти
     * только настоящий браузер: он выполняет JS, решает челлендж и сам перезагружает
     * страницу. На это уходит несколько секунд, поэтому используем только как fallback,
     * когда обычный запрос уже не дал результата — не на каждую ссылку.
     *
     * @return string|null null — если Chrome не найден на сервере или что-то пошло не так
     */
    private function fetchHtmlViaHeadlessBrowser(string $url): ?string
    {
        $chromePath = $this->findChromeExecutable();
        if (!$chromePath) {
            Log::warning('[wishlist link-preview] Chrome не найден на сервере — headless-fallback недоступен', ['url' => $url]);
            return null;
        }

        // свой профиль Chrome под storage/ на каждый запрос — гарантированно доступен на
        // запись веб-серверу (Laravel и так требует этого от storage/), в отличие от
        // временной директории по умолчанию, которая под сервисным пользователем (например,
        // Apache как LocalSystem в Windows-сервисе) может вести себя иначе; отдельная папка
        // на каждый запрос — чтобы параллельные запросы не конфликтовали за один профиль
        $this->cleanupStaleChromeProfiles();
        $userDataDir = storage_path('app/chrome-profile/' . Str::random(16));
        @mkdir($userDataDir, 0777, true);

        try {
            $factory = new BrowserFactory($chromePath);
            $browser = $factory->createBrowser([
                'headless' => true,
                'noSandbox' => true,
                'windowSize' => [1280, 1024],
                'enableImages' => false,
                'userDataDir' => $userDataDir,
                'userAgent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36',
                // без этого флага некоторые антибот-защиты видят navigator.webdriver === true
                // и сразу распознают автоматизированный (не настоящий) браузер
                'customFlags' => ['--disable-blink-features=AutomationControlled'],
            ]);
        } catch (\Throwable $e) {
            Log::warning('[wishlist link-preview] не удалось запустить headless Chrome', [
                'url' => $url,
                'error' => $e->getMessage(),
            ]);
            $this->removeDirRecursive($userDataDir);
            return null;
        }

        try {
            $page = $browser->createPage();
            $page->navigate($url)->waitForNavigation(Page::DOM_CONTENT_LOADED, 15000);

            // антибот-защита (AWS WAF/Cloudflare) решает JS-челлендж и сама перезагружает
            // страницу, а после этого часть магазинов ещё и подгружает цену отдельным JS-
            // запросом — вместо фиксированной паузы опрашиваем HTML тем же парсером, что и
            // в конце (parseOpenGraphMeta), и ждём, пока в нём не появятся реальные данные
            $html = $this->waitUntilProductDataAppears($page, $url);

            if ($this->looksLikeBotChallenge($html)) {
                Log::warning('[wishlist link-preview] антибот-челлендж не был пройден за отведённое время', ['url' => $url]);
            }

            return $html;
        } catch (\Throwable $e) {
            Log::warning('[wishlist link-preview] ошибка при рендере страницы headless-браузером', [
                'url' => $url,
                'error' => $e->getMessage(),
            ]);
            return null;
        } finally {
            try {
                $browser->close();
            } catch (\Throwable $e) {
                // ничего не делаем — процесс Chrome и так завершится сам
            }
            $this->removeDirRecursive($userDataDir);
        }
    }

    /**
     * Рекурсивно удаляет временную папку профиля Chrome после запроса. Windows может ещё
     * долю секунды держать файлы профиля залоченными сразу после закрытия процесса Chrome —
     * поэтому пробуем несколько раз с небольшой паузой, прежде чем смириться с тем, что
     * папка останется на диске (не критично, просто "мусор" в storage/).
     */
    private function removeDirRecursive(string $dir, int $attempts = 3): void
    {
        if (!is_dir($dir)) {
            return;
        }

        for ($i = 0; $i < $attempts; $i++) {
            if ($i > 0) {
                usleep(300000);
            }

            try {
                $items = new \RecursiveIteratorIterator(
                    new \RecursiveDirectoryIterator($dir, \FilesystemIterator::SKIP_DOTS),
                    \RecursiveIteratorIterator::CHILD_FIRST
                );
                foreach ($items as $item) {
                    $item->isDir() ? @rmdir($item->getPathname()) : @unlink($item->getPathname());
                }
                @rmdir($dir);
            } catch (\Throwable $e) {
                // не критично — это временная папка, просто останется на диске
            }

            if (!is_dir($dir)) {
                return;
            }
        }
    }

    /**
     * Иногда removeDirRecursive не может сразу удалить папку профиля (Windows ещё держит
     * файлы залоченными после закрытия Chrome) — вместо отдельной задачи по расписанию
     * подчищаем такой "мусор" от предыдущих запросов старше часа прямо здесь, перед
     * созданием нового профиля (к этому моменту файлы уже точно освободились).
     */
    private function cleanupStaleChromeProfiles(): void
    {
        $baseDir = storage_path('app/chrome-profile');
        if (!is_dir($baseDir)) {
            return;
        }

        foreach (glob($baseDir . '/*', GLOB_ONLYDIR) ?: [] as $dir) {
            if (filemtime($dir) < time() - 3600) {
                $this->removeDirRecursive($dir, 1);
            }
        }
    }

    /**
     * Признаки страницы-челленджа антибот-защиты (AWS WAF, Cloudflare) вместо реального
     * содержимого — используется, чтобы понять, стоит ли ещё подождать перезагрузки.
     */
    private function looksLikeBotChallenge(string $html): bool
    {
        return stripos($html, 'awswaf.com') !== false
            || stripos($html, 'challenge-container') !== false
            || stripos($html, 'cf-browser-verification') !== false
            || stripos($html, 'Checking your browser') !== false;
    }

    /**
     * Опрашивает HTML страницы, пока в нём не появятся реально извлекаемые данные о товаре
     * (тем же parseOpenGraphMeta, что и в конце linkPreview), либо пока не выйдет лимит
     * времени. Ждать именно "исчезновения челленджа" недостаточно: часть магазинов после
     * прохождения антибот-проверки ещё и подгружает цену отдельным JS-запросом чуть позже —
     * поэтому дожидаемся не просто чистой страницы, а собственно нужных данных на ней.
     *
     * Отдельно учитываем магазины (замечено на Bitrix-сайтах вроде miraton.ua), которые на
     * самый первый запрос новой сессии отвечают вообще пустой страницей (только выставляют
     * cookie сессии) и никогда сами её не перезагружают — обычная навигация headless-браузера
     * зависла бы на такой пустой странице до конца отведённого времени. Если страница
     * подозрительно пустая (и это не антибот-челлендж, для которого и так ждём) — один раз
     * перезаходим на тот же адрес: теперь браузер отправит уже полученную cookie сессии.
     */
    private function waitUntilProductDataAppears(Page $page, string $url, int $maxWaitSeconds = 20, int $extraWaitForPriceSeconds = 8): string
    {
        $html = $page->getHtml();
        $bestHtml = $html;
        $basicsFoundAt = null;
        $reloadedForEmptyResponse = false;

        $waited = 0;
        while ($waited < $maxWaitSeconds) {
            if (!$reloadedForEmptyResponse && $waited >= 2 && strlen(trim($html)) < 200 && !$this->looksLikeBotChallenge($html)) {
                $reloadedForEmptyResponse = true;
                try {
                    $page->navigate($url)->waitForNavigation(Page::DOM_CONTENT_LOADED, 15000);
                    $html = $page->getHtml();
                } catch (\Throwable $e) {
                    // не получилось перезайти — продолжаем обычный опрос ниже
                }
            }

            if (!$this->looksLikeBotChallenge($html)) {
                $meta = $this->parseOpenGraphMeta($html, 'text/html; charset=UTF-8');
                $hasBasics = !empty($meta['title']) && (!empty($meta['price']) || !empty($meta['image']));

                if ($hasBasics) {
                    $bestHtml = $html;

                    if (!empty($meta['price'])) {
                        return $html;
                    }

                    // title/картинка уже есть, а цены нет — даём странице ещё немного
                    // времени именно на неё (часто подгружается отдельным JS-запросом чуть
                    // позже), но не ждём вечно, если у товара цены в принципе нет
                    if ($basicsFoundAt === null) {
                        $basicsFoundAt = $waited;
                    } elseif ($waited - $basicsFoundAt >= $extraWaitForPriceSeconds) {
                        return $bestHtml;
                    }
                }
            }

            sleep(1);
            $waited++;
            try {
                $html = $page->getHtml();
            } catch (\Throwable $e) {
                // страница как раз перезагружается — пробуем на следующей итерации
            }
        }

        return $bestHtml;
    }

    /**
     * Путь к бинарнику Chrome/Chromium на сервере. Сначала смотрим переменную окружения
     * (для продакшена/Linux-серверов — путь может быть нестандартным), затем — типичные
     * пути установки на Windows (локальная разработка через XAMPP) и Linux.
     */
    private function findChromeExecutable(): ?string
    {
        $envPath = env('CHROME_EXECUTABLE_PATH');
        if ($envPath && file_exists($envPath)) {
            return $envPath;
        }

        $candidates = [
            'C:\Program Files\Google\Chrome\Application\chrome.exe',
            'C:\Program Files (x86)\Google\Chrome\Application\chrome.exe',
            '/usr/bin/google-chrome',
            '/usr/bin/google-chrome-stable',
            '/usr/bin/chromium-browser',
            '/usr/bin/chromium',
        ];

        foreach ($candidates as $candidate) {
            if (file_exists($candidate)) {
                return $candidate;
            }
        }

        return null;
    }

    /**
     * Добавить желание в вишлист. Принимает либо распарсенную по ссылке картинку
     * (image_url — скачиваем сами), либо загруженный вручную файл (image),
     * либо ни то ни другое (без картинки).
     */
    public function storeItem(Request $request, Wishlist $wishlist)
    {
        $this->authorizeOwner($wishlist);

        $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'nullable|string|max:2000',
            'url' => 'nullable|string|max:1000',
            'price' => 'nullable|numeric|min:0',
            'category' => 'nullable|string|max:100',
            'priority' => 'nullable|string|max:50',
            'image_url' => 'nullable|string|max:2000',
            'image' => 'nullable|file|mimes:jpeg,png,jpg,gif,webp',
        ]);

        $imageVariants = null;
        if ($request->hasFile('image')) {
            $imageVariants = $this->storeUploadedImage($request->file('image'));
        } elseif (!empty($request->image_url)) {
            $imageVariants = $this->downloadAndStoreImage($request->image_url);
        }

        $item = WishlistItem::create([
            'wishlist_id' => $wishlist->id,
            'title' => $request->title,
            'description' => $request->description,
            'url' => $request->url,
            'price' => $request->price,
            'category' => $request->category,
            'priority' => $request->priority,
            'image' => $imageVariants ? json_encode($imageVariants['image']) : null,
            'image_large' => $imageVariants && $imageVariants['image_large'] ? json_encode($imageVariants['image_large']) : null,
        ]);

        return response()->json([
            'status' => true,
            'message' => 'Желание добавлено',
            'item' => $this->formatItemForFrontend($item),
        ]);
    }

    /**
     * Отредактировать желание (владелец).
     */
    public function updateItem(Request $request, WishlistItem $item)
    {
        $this->authorizeOwner($item->wishlist);

        $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'nullable|string|max:2000',
            'url' => 'nullable|string|max:1000',
            'price' => 'nullable|numeric|min:0',
            'category' => 'nullable|string|max:100',
            'priority' => 'nullable|string|max:50',
        ]);

        // поля необязательны и обновляются, только если реально переданы в запросе —
        // иначе (например, при частичном PUT) существующее значение затиралось бы null'ом
        $item->update([
            'title' => $request->title,
            'description' => $request->has('description') ? $request->description : $item->description,
            'url' => $request->has('url') ? $request->url : $item->url,
            'price' => $request->has('price') ? $request->price : $item->price,
            'category' => $request->has('category') ? $request->category : $item->category,
            'priority' => $request->has('priority') ? $request->priority : $item->priority,
        ]);

        return response()->json([
            'status' => true,
            'message' => 'Желание обновлено',
            'item' => $this->formatItemForFrontend($item),
        ]);
    }

    /**
     * Владелец отмечает желание как исполненное ("куплено/получено") — независимо
     * от того, бронировал ли его кто-то из гостей. "Забронировано" (is_reserved,
     * ставит гость) и "исполнено" (is_fulfilled, ставит владелец) — разные статусы:
     * желание может быть забронировано, но ещё не получено, и наоборот.
     */
    public function toggleFulfilledByOwner(WishlistItem $item)
    {
        $this->authorizeOwner($item->wishlist);

        $item->update([
            'is_fulfilled' => !$item->is_fulfilled,
        ]);

        return response()->json(['status' => true, 'item' => $this->formatItemForFrontend($item)]);
    }

    public function destroyItem(WishlistItem $item)
    {
        $this->authorizeOwner($item->wishlist);

        $this->deleteItemImage($item);
        $item->delete();

        return response()->json(['status' => true, 'message' => 'Желание удалено']);
    }

    /**
     * Создать копию желания в том же вишлисте ("Создать копию" в меню карточки).
     * Резерв/отметка "исполнено" не копируются — это новое, отдельное желание.
     * Картинка (если есть) физически копируется в свой файл, а не просто ссылается
     * на тот же путь — иначе удаление оригинала стёрло бы файл и у копии тоже.
     */
    public function duplicateItem(WishlistItem $item)
    {
        $this->authorizeOwner($item->wishlist);

        $newImage = $this->copyItemImageFile($item->image_data);
        $newImageLarge = $this->copyItemImageFile($item->image_large_data);

        $duplicate = WishlistItem::create([
            'wishlist_id' => $item->wishlist_id,
            'title' => $item->title,
            'description' => $item->description,
            'url' => $item->url,
            'price' => $item->price,
            'category' => $item->category,
            'priority' => $item->priority,
            'image' => $newImage ? json_encode($newImage) : null,
            'image_large' => $newImageLarge ? json_encode($newImageLarge) : null,
        ]);

        return response()->json([
            'status' => true,
            'message' => 'Копия создана',
            'item' => $this->formatItemForFrontend($duplicate),
        ]);
    }

    /**
     * Физически копирует файл картинки желания в новый файл (используется при
     * дублировании желания — как для обычной, так и для увеличенной версии), чтобы
     * удаление оригинала не стёрло файл и у копии тоже.
     *
     * @return array{name: string, extension: string, path: string}|null
     */
    private function copyItemImageFile(?array $data): ?array
    {
        if (empty($data['path'])) {
            return null;
        }

        $absoluteOld = Storage::disk('local')->path($data['path']);
        if (!file_exists($absoluteOld)) {
            return null;
        }

        $newName = Str::random(20);
        $relativeDir = 'wishlist/' . Auth::id();
        $newRelativePath = '/' . $relativeDir . '/' . $newName . '.' . $data['extension'];

        Storage::disk('local')->makeDirectory($relativeDir);
        copy($absoluteOld, Storage::disk('local')->path($newRelativePath));

        return ['name' => $newName, 'extension' => $data['extension'], 'path' => $newRelativePath];
    }

    /**
     * @param Wishlist $wishlist
     * @return void
     */
    private function authorizeOwner(Wishlist $wishlist): void
    {
        abort_unless($wishlist->user_id == Auth::id(), 403);
    }

    /**
     * Достаёт title/description/image/price со страницы товара из трёх источников —
     * og:.../twitter:... мета-теги, JSON-LD (schema.org Product) и Microdata (itemprop) —
     * и берёт то, что нашлось, с разным приоритетом для разных полей:
     *   - title/description — сначала og-теги (специально подобраны магазином под превью
     *     в соцсетях), затем JSON-LD, затем Microdata;
     *   - image — сначала JSON-LD/Microdata, затем og-теги: og:image иногда указывает не на
     *     настоящее фото товара, а на общий баннер для соцсетей (например, на estro.ua), тогда
     *     как JSON-LD/microdata почти всегда содержат реальную картинку товара;
     *   - price — сначала JSON-LD/Microdata (устойчивого og:price практически никто не
     *     отдаёт), затем og-теги.
     * Если title так и не нашёлся нигде — последовательно пробуем <title> страницы, а
     * совсем последним резервом (см. ниже) — первый <h1> на странице.
     *
     * @param string $html
     * @return array{title: ?string, description: ?string, image: ?string, price: ?string}
     */
    private function parseOpenGraphMeta(string $html, ?string $contentTypeHeader = null): array
    {
        $html = $this->convertHtmlToUtf8($html, $contentTypeHeader);
        // после перекодировки в самом HTML может остаться старая декларация кодировки
        // (<meta charset="windows-1251"> и т.п.) — если её не убрать, DOMDocument увидит
        // конфликт с XML-декларацией ниже и попытается перекодировать уже перекодированное
        $html = preg_replace('/<meta[^>]+charset=[^>]*>/i', '', $html);

        $doc = new \DOMDocument();
        libxml_use_internal_errors(true);
        // без XML-декларации DOMDocument сам пытается угадать кодировку и часто ошибается
        // на кириллице (получается "кракозябра") — раз строка уже гарантированно UTF-8,
        // явно говорим об этом libxml, а не полагаемся на его автоопределение
        $doc->loadHTML('<?xml encoding="UTF-8">' . $html, LIBXML_NOERROR | LIBXML_NOWARNING);
        libxml_use_internal_errors(false);

        $xpath = new \DOMXPath($doc);

        $metaData = $this->parseMetaTagsProduct($xpath);
        $jsonLd = $this->parseJsonLdProduct($xpath);
        $microdata = $this->parseMicrodataProduct($xpath);

        $title = $metaData['title'] ?: $jsonLd['title'] ?: $microdata['title'];
        $description = $metaData['description'] ?: $jsonLd['description'] ?: $microdata['description'];
        // og:image — вопреки названию, не всегда настоящее фото товара: некоторые магазины
        // (напр. estro.ua) отдают в og:image общий баннер для соцсетей, а не карточку товара,
        // при этом в JSON-LD/микроразметке (image/itemprop="image") лежит верное фото —
        // поэтому для картинки им доверяем больше, чем og-тегам
        $image = $jsonLd['image'] ?: $microdata['image'] ?: $metaData['image'];
        // но этот приоритет не гарантирует лучшее КАЧЕСТВО картинки — бывает и наоборот
        // (напр. на rozetka.com.ua JSON-LD отдаёт под всеми фото уменьшенную версию для
        // главной страницы, а нормальный размер лежит только в og:image) — поэтому отдельно
        // собираем вообще все найденные варианты, чтобы потом (см. linkPreview) выбрать
        // среди них реально самый качественный, а не просто "приоритетный по источнику"
        $imageCandidates = $this->collectAllImageCandidates($xpath, $image);
        // цену og-теги почти никогда не отдают — сначала смотрим более надёжные JSON-LD/микроразметку
        $price = $jsonLd['price'] ?: $microdata['price'] ?: $metaData['price'];

        if (empty($title)) {
            $titleNodes = $xpath->query('//title');
            if ($titleNodes->length > 0) {
                $title = trim($titleNodes->item(0)->textContent);
            }
        }

        // совсем последний фолбэк, если вообще ничего не нашлось (ни og-тегов, ни JSON-LD,
        // ни microdata, ни даже <title>) — берём первый <h1> на странице. Ненадёжно (это
        // может быть что угодно — слоган, название категории и т.п.), поэтому используем
        // только когда все более точные источники уже исчерпаны
        if (empty($title)) {
            $h1Nodes = $xpath->query('//h1');
            if ($h1Nodes->length > 0) {
                $h1Text = trim($h1Nodes->item(0)->textContent);
                if ($h1Text !== '') {
                    $title = $h1Text;
                }
            }
        }

        // цена может прийти как "1 234.56" или с пробелами/валютой — оставляем только число
        if (!empty($price)) {
            $price = preg_replace('/[^\d.,]/', '', $price);
            $price = str_replace(',', '.', $price);
        }

        return [
            'title' => $title ? mb_substr($title, 0, 255) : null,
            'description' => $description ? mb_substr($description, 0, 2000) : null,
            'image' => $image,
            'image_candidates' => $imageCandidates,
            'price' => $price ?: null,
        ];
    }

    /**
     * Собирает вообще все ссылки на фото товара, найденные на странице (og:image,
     * og:image:secure_url, twitter:image, все картинки из JSON-LD Product, microdata) —
     * без приоритета источника, просто список кандидатов для последующего сравнения по
     * реальному качеству (см. pickBestQualityImageUrl). $bestGuess (то, что выбрал бы
     * обычный приоритет источника) идёт в списке первым — если сравнение качества не
     * даст явного победителя, останется он.
     *
     * @return string[]
     */
    private function collectAllImageCandidates(\DOMXPath $xpath, ?string $bestGuess): array
    {
        $candidates = $bestGuess ? [$bestGuess] : [];

        foreach ($xpath->query('//meta') as $metaNode) {
            /** @var \DOMElement $metaNode */
            $key = strtolower($metaNode->getAttribute('property') ?: $metaNode->getAttribute('name'));
            if (!in_array($key, ['og:image', 'og:image:secure_url', 'twitter:image'], true)) {
                continue;
            }
            $value = trim($metaNode->getAttribute('content'));
            if ($value !== '') {
                $candidates[] = $value;
            }
        }

        foreach ($xpath->query('//script[@type="application/ld+json"]') as $node) {
            $json = json_decode(trim($node->textContent), true);
            if (!is_array($json)) {
                continue;
            }

            $nodes = isset($json['@graph']) && is_array($json['@graph']) ? $json['@graph'] : [$json];
            foreach ($nodes as $candidateNode) {
                if (!is_array($candidateNode) || empty($candidateNode['image'])) {
                    continue;
                }
                $type = $candidateNode['@type'] ?? null;
                $isProduct = $type === 'Product' || (is_array($type) && in_array('Product', $type, true));
                if (!$isProduct) {
                    continue;
                }
                foreach ($this->flattenJsonLdImageUrls($candidateNode['image']) as $url) {
                    $candidates[] = $url;
                }
            }
        }

        foreach ($xpath->query('//*[@itemscope]') as $scopeNode) {
            /** @var \DOMElement $scopeNode */
            if (stripos($scopeNode->getAttribute('itemtype'), 'schema.org/Product') === false) {
                continue;
            }
            $microImage = $this->microdataPropValue($xpath, $scopeNode, 'image');
            if ($microImage) {
                $candidates[] = $microImage;
            }

            // многие магазины показывают в itemprop="image" уменьшенную версию для быстрой
            // загрузки страницы, а настоящее большое фото прячут отдельно — для зум-плагина
            // (в нестандартном атрибуте вроде data-zoom-image/xoriginal на той же картинке)
            // или для лайтбокса (сама картинка обёрнута в <a href="..."> с полноразмерным
            // файлом). Проверяем оба места на каждой itemprop="image" картинке этого товара
            foreach ($xpath->query('.//*[@itemprop="image"]', $scopeNode) as $imageNode) {
                if (!($imageNode instanceof \DOMElement)) {
                    continue;
                }
                foreach ($this->collectZoomCandidatesForNode($imageNode) as $url) {
                    $candidates[] = $url;
                }
            }
            break;
        }

        return array_values(array_unique(array_filter($candidates)));
    }

    /** Атрибуты, в которых разные зум-плагины хранят ссылку на полноразмерную версию
     *  картинки, пока в src/content лежит уменьшенная превьюшка для быстрой загрузки. */
    private const ZOOM_IMAGE_ATTRIBUTES = [
        'data-zoom-image', 'data-zoom', 'data-large_image', 'data-large-image',
        'data-original', 'data-full', 'data-fullsize', 'data-hires', 'data-big',
        'xoriginal', 'data-src',
    ];

    /**
     * У картинки товара (itemprop="image", но подходит для любого <img>) смотрит:
     *   - нестандартные атрибуты зум-плагинов (см. ZOOM_IMAGE_ATTRIBUTES) — там может лежать
     *     ссылка на полноразмерное фото, пока src/content — уменьшенная версия для загрузки;
     *   - обёртку <a href="..."> — обычная разметка лайтбокс-галереи: превью-картинка внутри
     *     ссылки на полноразмерный файл.
     * Сам факт наличия такой ссылки не значит, что она больше src — сравнение по реальному
     * размеру всё равно делает pickBestQualityImageUrl, здесь только собираем кандидатов.
     *
     * @return string[]
     */
    private function collectZoomCandidatesForNode(\DOMElement $imageNode): array
    {
        $urls = [];

        foreach (self::ZOOM_IMAGE_ATTRIBUTES as $attr) {
            $value = trim($imageNode->getAttribute($attr));
            if ($value !== '') {
                $urls[] = $value;
            }
        }

        $parent = $imageNode->parentNode;
        if ($parent instanceof \DOMElement && strtolower($parent->tagName) === 'a') {
            $href = trim($parent->getAttribute('href'));
            if ($href !== '' && preg_match('/\.(jpe?g|png|webp|gif|avif)(\?|$)/i', $href)) {
                $urls[] = $href;
            }
        }

        return $urls;
    }

    /**
     * Как extractImageUrlFromJsonLdValue, но возвращает ВСЕ найденные ссылки, а не только
     * первую — нужно для сравнения качества между вариантами (см. collectAllImageCandidates).
     *
     * @param mixed $image
     * @return string[]
     */
    private function flattenJsonLdImageUrls($image): array
    {
        if (is_string($image)) {
            return $image !== '' ? [$image] : [];
        }

        if (!is_array($image)) {
            return [];
        }

        if (!empty($image['contentUrl']) && is_string($image['contentUrl'])) {
            return [$image['contentUrl']];
        }
        if (!empty($image['url']) && is_string($image['url'])) {
            return [$image['url']];
        }

        $urls = [];
        foreach ($image as $item) {
            $urls = array_merge($urls, $this->flattenJsonLdImageUrls($item));
        }

        return $urls;
    }

    /**
     * Среди нескольких найденных на странице ссылок на фото товара выбирает физически
     * самую качественную (по количеству пикселей) — реально скачивая и сравнивая, а не
     * угадывая по тому, из какого источника (og-теги/JSON-LD/микроразметка) взялась ссылка.
     * Это нужно, потому что "приоритетный" по общему правилу источник (см. parseOpenGraphMeta)
     * не всегда физически самый качественный — например, на rozetka.com.ua JSON-LD отдаёт
     * под всеми фото уменьшенную версию для главной страницы (238×333), а нормальный размер
     * (450×630) лежит только в og:image.
     */
    private function pickBestQualityImageUrl(array $candidateUrls, ?string $fallback): ?string
    {
        if (count($candidateUrls) <= 1) {
            return $fallback;
        }

        $bestUrl = $fallback;
        $bestArea = 0;

        // не больше 5 кандидатов — один клик "Найти в магазинах" не должен превращаться
        // в десяток лишних запросов из-за большой галереи фото на странице
        foreach (array_slice($candidateUrls, 0, 5) as $url) {
            try {
                $response = Http::timeout(5)->get($url);
                if (!$response->successful()) {
                    continue;
                }
                $info = @getimagesizefromstring($response->body());
                if (!$info) {
                    continue;
                }
                $area = $info[0] * $info[1];
                if ($area > $bestArea) {
                    $bestArea = $area;
                    $bestUrl = $url;
                }
            } catch (\Throwable $e) {
                // не удалось скачать/прочитать этот вариант — пробуем следующий
            }
        }

        return $bestUrl;
    }

    /**
     * og:.../twitter:.../description мета-теги.
     *
     * @return array{title: ?string, description: ?string, image: ?string, price: ?string}
     */
    private function parseMetaTagsProduct(\DOMXPath $xpath): array
    {
        $title = null;
        $description = null;
        $image = null;
        $price = null;

        $metaMap = [
            'og:title' => 'title',
            'twitter:title' => 'title',
            'og:description' => 'description',
            'twitter:description' => 'description',
            'description' => 'description',
            'og:image' => 'image',
            'og:image:secure_url' => 'image',
            'twitter:image' => 'image',
            'product:price:amount' => 'price',
            'og:price:amount' => 'price',
        ];

        foreach ($xpath->query('//meta') as $metaNode) {
            /** @var \DOMElement $metaNode */
            $key = $metaNode->getAttribute('property') ?: $metaNode->getAttribute('name');
            $key = strtolower($key);

            if (!isset($metaMap[$key])) {
                continue;
            }

            $value = trim($metaNode->getAttribute('content'));
            if ($value === '') {
                continue;
            }

            $field = $metaMap[$key];
            if ($field === 'title' && empty($title)) {
                $title = $value;
            } elseif ($field === 'description' && empty($description)) {
                $description = $value;
            } elseif ($field === 'image' && empty($image)) {
                $image = $value;
            } elseif ($field === 'price' && empty($price)) {
                $price = $value;
            }
        }

        return compact('title', 'description', 'image', 'price');
    }

    /**
     * JSON-LD (schema.org Product) — обходит все <script type="application/ld+json">
     * на странице (в т.ч. обёрнутые в "@graph") и ищет первый узел с "@type": "Product".
     *
     * @return array{title: ?string, description: ?string, image: ?string, price: ?string}
     */
    private function parseJsonLdProduct(\DOMXPath $xpath): array
    {
        $result = ['title' => null, 'description' => null, 'image' => null, 'price' => null];

        foreach ($xpath->query('//script[@type="application/ld+json"]') as $node) {
            $json = json_decode(trim($node->textContent), true);
            if (!is_array($json)) {
                continue;
            }

            $candidates = isset($json['@graph']) && is_array($json['@graph']) ? $json['@graph'] : [$json];

            foreach ($candidates as $candidate) {
                if (!is_array($candidate)) {
                    continue;
                }

                $type = $candidate['@type'] ?? null;
                $isProduct = $type === 'Product' || (is_array($type) && in_array('Product', $type, true));
                if (!$isProduct) {
                    continue;
                }

                if (!empty($candidate['name']) && is_string($candidate['name'])) {
                    $result['title'] = $candidate['name'];
                }
                if (!empty($candidate['description']) && is_string($candidate['description'])) {
                    $result['description'] = $candidate['description'];
                }
                if (!empty($candidate['image'])) {
                    $result['image'] = $this->extractImageUrlFromJsonLdValue($candidate['image']);
                }

                $offers = $candidate['offers'] ?? null;
                if ($offers) {
                    // offers может быть одним Offer/AggregateOffer, либо массивом Offer'ов —
                    // берём цену из первого, где она есть
                    $offerList = is_array($offers) && array_is_list($offers) ? $offers : [$offers];
                    foreach ($offerList as $offer) {
                        if (!is_array($offer)) {
                            continue;
                        }
                        $offerPrice = $offer['price'] ?? $offer['lowPrice'] ?? null;
                        if ($offerPrice !== null && $offerPrice !== '') {
                            $result['price'] = (string) $offerPrice;
                            break;
                        }
                    }
                }

                // нашли Product с данными — этого достаточно, не проверяем остальные <script>
                if ($result['title'] || $result['price']) {
                    return $result;
                }
            }
        }

        return $result;
    }

    /**
     * Поле "image" в JSON-LD может быть в разных формах: просто ссылка-строка, массив
     * ссылок, объект ImageObject ({"@type":"ImageObject","contentUrl":"..."}) или массив
     * таких объектов — разворачиваем любую из них до первой найденной ссылки-строки.
     *
     * @param mixed $image
     */
    private function extractImageUrlFromJsonLdValue($image): ?string
    {
        if (is_string($image)) {
            return $image ?: null;
        }

        if (!is_array($image)) {
            return null;
        }

        // ImageObject напрямую — ссылка лежит в contentUrl (стандартное поле) либо url
        if (!empty($image['contentUrl']) && is_string($image['contentUrl'])) {
            return $image['contentUrl'];
        }
        if (!empty($image['url']) && is_string($image['url'])) {
            return $image['url'];
        }

        // массив (строк или ImageObject'ов) — берём первую ссылку, которую удастся найти
        foreach ($image as $item) {
            $url = $this->extractImageUrlFromJsonLdValue($item);
            if ($url) {
                return $url;
            }
        }

        return null;
    }

    /**
     * Microdata (itemprop="..." прямо в HTML) — ищет itemscope с itemtype, содержащим
     * "schema.org/Product", и читает name/description/image/price внутри него.
     *
     * @return array{title: ?string, description: ?string, image: ?string, price: ?string}
     */
    private function parseMicrodataProduct(\DOMXPath $xpath): array
    {
        $result = ['title' => null, 'description' => null, 'image' => null, 'price' => null];

        $scope = null;
        foreach ($xpath->query('//*[@itemscope]') as $node) {
            /** @var \DOMElement $node */
            if (stripos($node->getAttribute('itemtype'), 'schema.org/Product') !== false) {
                $scope = $node;
                break;
            }
        }

        if (!$scope) {
            return $result;
        }

        $result['title'] = $this->microdataPropValue($xpath, $scope, 'name');
        $result['description'] = $this->microdataPropValue($xpath, $scope, 'description');
        $result['image'] = $this->microdataPropValue($xpath, $scope, 'image');
        // цена обычно лежит во вложенном itemscope="Offer" (itemprop="offers") — для неё
        // вложенность в другой itemscope разрешаем, в отличие от name/description/image
        $result['price'] = $this->microdataPropValue($xpath, $scope, 'price', true);

        return $result;
    }

    /**
     * Значение itemprop="$prop" внутри $scope. По умолчанию пропускает узлы, вложенные в
     * ДРУГОЙ itemscope внутри $scope (например, хлебные крошки внутри карточки товара) —
     * чтобы не перепутать название категории с названием товара.
     */
    private function microdataPropValue(\DOMXPath $xpath, \DOMElement $scope, string $prop, bool $allowNestedScopes = false): ?string
    {
        foreach ($xpath->query('.//*[@itemprop="' . $prop . '"]', $scope) as $node) {
            /** @var \DOMElement $node */
            if (!$allowNestedScopes) {
                $nestedInOtherScope = false;
                $ancestor = $node->parentNode;
                while ($ancestor && $ancestor !== $scope) {
                    if ($ancestor instanceof \DOMElement && $ancestor->hasAttribute('itemscope')) {
                        $nestedInOtherScope = true;
                        break;
                    }
                    $ancestor = $ancestor->parentNode;
                }
                if ($nestedInOtherScope) {
                    continue;
                }
            }

            // значение может лежать в content (<meta>), src (<img>), href (<link>) или в тексте узла
            $value = $node->getAttribute('content') ?: ($node->getAttribute('src') ?: ($node->getAttribute('href') ?: trim($node->textContent)));
            $value = trim($value);
            if ($value !== '') {
                return $value;
            }
        }

        return null;
    }

    /**
     * Многие интернет-магазины (особенно старые/украинские/русские) отдают HTML не в UTF-8,
     * а в windows-1251 — без учёта этого DOMDocument угадывает кодировку неправильно и
     * кириллица превращается в "кракозябры". Определяем реальную кодировку (заголовок
     * Content-Type, затем <meta charset> / <meta http-equiv="Content-Type"> в самом HTML)
     * и приводим строку к UTF-8 до передачи в DOMDocument.
     *
     * @param string $html
     * @param string|null $contentTypeHeader
     * @return string
     */
    private function convertHtmlToUtf8(string $html, ?string $contentTypeHeader): string
    {
        $charset = null;

        if ($contentTypeHeader && preg_match('/charset=([a-z0-9\-_]+)/i', $contentTypeHeader, $m)) {
            $charset = $m[1];
        }

        if (!$charset) {
            // ищем только в начале документа (там, где обычно лежит <head>) — незачем
            // прогонять регулярку по всей странице
            $head = substr($html, 0, 4096);
            if (preg_match('/<meta[^>]+charset=["\']?\s*([a-z0-9\-_]+)/i', $head, $m)) {
                $charset = $m[1];
            }
        }

        $charset = $charset ? strtolower(trim($charset, "\"' \t")) : null;

        if (!$charset || $charset === 'utf-8' || $charset === 'utf8') {
            // если явной кодировки не нашли — проверяем, действительно ли это валидный UTF-8;
            // если нет, наиболее вероятный кандидат для рунета — windows-1251
            if (!$charset && !mb_check_encoding($html, 'UTF-8')) {
                $charset = 'windows-1251';
            } else {
                return $html;
            }
        }

        $converted = @iconv($charset, 'UTF-8//IGNORE', $html);

        return $converted !== false ? $converted : $html;
    }

    /**
     * Скачивает картинку по URL (обычно og:image со страницы товара) и сохраняет
     * локально — чтобы вишлист не зависел от доступности стороннего сайта. Возвращает
     * сразу обе версии — компактную (для карточки в списке) и увеличенную (для просмотра
     * "поближе" по клику в попапе "Подробнее") — см. createImageSizeVariants.
     *
     * @param string $imageUrl
     * @return array{image: array{name: string, extension: string, path: string}, image_large: array{name: string, extension: string, path: string}|null}|null
     */
    private function downloadAndStoreImage(string $imageUrl): ?array
    {
        if (!preg_match('#^https?://#i', $imageUrl)) {
            return null;
        }

        try {
            $response = Http::timeout(10)->withHeaders([
                'User-Agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36',
            ])->get($imageUrl);
        } catch (\Throwable $e) {
            return null;
        }

        if (!$response->successful()) {
            return null;
        }

        $bytes = $response->body();
        $imageInfo = @getimagesizefromstring($bytes);
        if ($imageInfo === false) {
            return null; // не картинка (например, страница с ошибкой вместо og:image)
        }

        $extension = $this->extensionFromMime($imageInfo['mime'] ?? 'image/jpeg');
        $name = Str::random(20);
        $relativeDir = 'wishlist/' . Auth::id();
        $relativePath = '/' . $relativeDir . '/' . $name . '.' . $extension;

        Storage::disk('local')->makeDirectory($relativeDir);
        Storage::disk('local')->put($relativePath, $bytes);

        $absolutePath = Storage::disk('local')->path($relativePath);
        $imageLarge = $this->createImageSizeVariants($absolutePath, $relativeDir, $name, $extension);

        return [
            'image' => ['name' => $name, 'extension' => $extension, 'path' => $relativePath],
            'image_large' => $imageLarge,
        ];
    }

    /**
     * Сохраняет вручную загруженный файл картинки желания — тоже в двух версиях,
     * см. createImageSizeVariants.
     *
     * @param \Illuminate\Http\UploadedFile $file
     * @return array{image: array{name: string, extension: string, path: string}, image_large: array{name: string, extension: string, path: string}|null}
     */
    private function storeUploadedImage($file): array
    {
        $extension = $file->getClientOriginalExtension() ?: 'jpg';
        $name = Str::random(20);
        $relativeDir = 'wishlist/' . Auth::id();

        Storage::disk('local')->makeDirectory($relativeDir);
        $file->storeAs($relativeDir, $name . '.' . $extension, 'local');

        $relativePath = '/' . $relativeDir . '/' . $name . '.' . $extension;
        $absolutePath = Storage::disk('local')->path($relativePath);
        $imageLarge = $this->createImageSizeVariants($absolutePath, $relativeDir, $name, $extension);

        return [
            'image' => ['name' => $name, 'extension' => $extension, 'path' => $relativePath],
            'image_large' => $imageLarge,
        ];
    }

    /**
     * Из только что сохранённого файла (изначально — оригинального размера) делает две
     * версии: увеличенную (почти оригинал, максимум 1600px по широкой стороне — для
     * просмотра "поближе") сохраняет рядом под отдельным именем, а сам файл по
     * $absolutePath уменьшает до 500px — компактная версия для карточки в списке
     * и превью при добавлении, чтобы там не гонять лишний вес.
     *
     * @return array{name: string, extension: string, path: string}|null null, если не удалось обработать (тогда используется только обычная версия)
     */
    private function createImageSizeVariants(string $absolutePath, string $relativeDir, string $name, string $extension): ?array
    {
        try {
            $image = Image::make($absolutePath);

            if ($image->width() > 1600) {
                $image->resize(1600, null, function ($constraint) {
                    $constraint->aspectRatio();
                    $constraint->upsize();
                });
            }
            $largeName = $name . '_large';
            $largeRelativePath = '/' . $relativeDir . '/' . $largeName . '.' . $extension;
            $image->save(Storage::disk('local')->path($largeRelativePath), 85);

            // дальше ужимаем то же (уже, возможно, уменьшенное до 1600) изображение —
            // не перечитываем файл заново
            if ($image->width() > 500) {
                $image->resize(500, null, function ($constraint) {
                    $constraint->aspectRatio();
                    $constraint->upsize();
                });
            }
            $image->save($absolutePath, 85);

            return ['name' => $largeName, 'extension' => $extension, 'path' => $largeRelativePath];
        } catch (\Throwable $e) {
            // не смогли обработать — оставляем оригинал как единственную версию, это не критично
            return null;
        }
    }

    private function deleteItemImage(WishlistItem $item): void
    {
        $data = $item->image_data;
        if (!empty($data['path'])) {
            Storage::disk('local')->delete($data['path']);
        }

        $largeData = $item->image_large_data;
        if (!empty($largeData['path'])) {
            Storage::disk('local')->delete($largeData['path']);
        }
    }

    private function extensionFromMime(string $mime): string
    {
        $map = [
            'image/jpeg' => 'jpg',
            'image/png' => 'png',
            'image/webp' => 'webp',
            'image/gif' => 'gif',
            'image/avif' => 'avif',
            'image/svg+xml' => 'svg',
        ];

        return $map[$mime] ?? 'jpg';
    }

    /**
     * @param WishlistItem $item
     * @return array
     */
    public static function formatItemForFrontend(WishlistItem $item): array
    {
        return [
            'id' => $item->id,
            'title' => $item->title,
            'description' => $item->description,
            'url' => $item->url,
            'url_domain' => $item->url_domain,
            'price' => $item->price !== null ? (string) $item->price : null,
            'category' => $item->category,
            'priority' => $item->priority,
            'image_url' => $item->image_url,
            'image_large_url' => $item->image_large_url ?: $item->image_url,
            'is_reserved' => (bool) $item->is_reserved,
            'reserved_by' => $item->reserved_by,
            'is_fulfilled' => (bool) $item->is_fulfilled,
            'created_at' => $item->created_at ? $item->created_at->toIso8601String() : null,
        ];
    }
}
