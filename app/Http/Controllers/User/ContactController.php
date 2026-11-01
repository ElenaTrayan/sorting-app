<?php

namespace App\Http\Controllers\User;

use App\Components\AvifImage;
use App\Components\ImageReader;
use App\Http\Controllers\Controller;
use App\Http\Controllers\User\Packages\UploadImageController;
use App\Models\Contact;
use App\Models\ContactConversation;
use App\Models\ContactFact;
use App\Models\ContactPhoto;
use App\Models\ContactQuote;
use App\Models\ContactQuoteImage;
use App\Models\ContactSocialLink;
use App\Models\Hashtag;
use App\Models\Post;
use App\Services\Integrations\IntegrationManager;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Intervention\Image\Facades\Image;
use function PHPUnit\Framework\isJson;

/**
 * Раздел "База контактов" — карточки людей с хештегами, фото, соцсетями,
 * высказываниями и фактами. На странице контакта также показываются посты
 * (фото и видео), привязанные теми же хештегами, что и у контакта.
 */
class ContactController extends Controller
{
    /**
     * Хештеги, по которым пост контакта попадает на вкладку "Переписки" (сравниваются в нижнем регистре)
     */
    const CHAT_HASHTAGS = ['переписка', 'переписки'];

    /**
     * Хештеги, по которым пост контакта попадает на вкладку "Высказывания" (сравниваются в нижнем регистре).
     * Первый — тот, что ставится постам, созданным кнопкой "Добавить" (createQuotePost())
     */
    const QUOTE_HASHTAGS = ['высказывание', 'высказывания'];

    /**
     * Источники высказываний (список — как в поп-апе "Добавить высказывание"): какой хештег поста о каком
     * источнике говорит. 'tag' — каким хештегом отметить новый пост, если подходящего у пользователя ещё нет.
     * Пост без такого хештега — "Другое"
     */
    const QUOTE_PLATFORMS = [
        'Instagram' => ['tag' => 'Instagram', 'hashtags' => ['instagram', 'инстаграм', 'инстаграмм', 'инста']],
        'Facebook' => ['tag' => 'Facebook', 'hashtags' => ['facebook', 'фейсбук']],
        'Threads' => ['tag' => 'Threads', 'hashtags' => ['threads', 'тредс']],
        'X (Twitter)' => ['tag' => 'Twitter', 'hashtags' => ['twitter', 'твиттер', 'x']],
        'Telegram' => ['tag' => 'Telegram', 'hashtags' => ['telegram', 'телеграм', 'телеграмм', 'тг']],
        'TikTok' => ['tag' => 'TikTok', 'hashtags' => ['tiktok', 'тикток']],
        'YouTube' => ['tag' => 'YouTube', 'hashtags' => ['youtube', 'ютуб']],
        'WhatsApp' => ['tag' => 'WhatsApp', 'hashtags' => ['whatsapp', 'ватсап', 'вотсап', 'вацап', 'ватсапп', 'вотсапп']],
        'Viber' => ['tag' => 'Viber', 'hashtags' => ['viber', 'вайбер']],
        'VK' => ['tag' => 'VK', 'hashtags' => ['vk', 'вк', 'вконтакте', 'vkontakte']],
        'Интервью' => ['tag' => 'интервью', 'hashtags' => ['интервью']],
        'Личное общение' => ['tag' => 'личное общение', 'hashtags' => ['личное общение']],
    ];

    /**
     * Сколько постов контакта показывать сразу и подгружать за раз при прокрутке (вкладка "Посты")
     */
    const POSTS_PER_PAGE = 40;

    /**
     * Источники переписок: какой хештег поста о каком источнике говорит. Порядок — порядок кнопок
     * и сортировки "по источнику"; пост без такого хештега — "Другое"
     */
    const CHAT_SOURCES = [
        'telegram' => ['label' => 'Telegram', 'icon' => 'fab fa-telegram-plane', 'hashtags' => ['telegram', 'телеграм', 'телеграмм', 'тг']],
        'whatsapp' => ['label' => 'WhatsApp', 'icon' => 'fab fa-whatsapp', 'hashtags' => ['whatsapp', 'ватсап', 'вотсап', 'вацап', 'ватсапп', 'вотсапп']],
        'viber' => ['label' => 'Viber', 'icon' => 'fab fa-viber', 'hashtags' => ['viber', 'вайбер']],
        'vk' => ['label' => 'VK', 'icon' => 'fab fa-vk', 'hashtags' => ['vk', 'вк', 'вконтакте', 'vkontakte']],
        'instagram' => ['label' => 'Instagram', 'icon' => 'fab fa-instagram', 'hashtags' => ['instagram', 'инстаграм', 'инстаграмм', 'инста']],
        'tinder' => ['label' => 'Tinder', 'icon' => 'fas fa-fire', 'hashtags' => ['tinder', 'тиндер']],
        'mamba' => ['label' => 'Mamba', 'icon' => 'fas fa-heart', 'hashtags' => ['mamba', 'мамба']],
        'other' => ['label' => 'Другое', 'icon' => 'fas fa-comment-dots', 'hashtags' => []],
    ];

    public function index()
    {
        $contacts = Contact::where('user_id', Auth::id())
            ->with('hashtags')
            ->latest()
            ->get();

        return view('user.contacts.index', [
            'contacts' => $contacts,
        ]);
    }

    /**
     * Подсказки город/страна для поля "Место жительства" (форма контакта). Основной
     * источник — своя локальная база (residence_cities/residence_countries, импорт
     * из GeoNames см. App\Console\Commands\ImportResidenceGeoData): быстро, без сети
     * и всегда с названием на русском/английском (у внешних геосервисов с этим беда —
     * например, украинские города они знают только под местным названием). Внешние
     * Photon/Nominatim — просто подстраховка на случай, если города нет в локальной
     * базе (она не полная: только от ~5000 жителей). Кэш на сутки — на тот случай,
     * когда всё же пришлось идти во внешний сервис.
     */
    public function searchResidence(Request $request)
    {
        $search = trim((string) $request->input('search'));
        if (mb_strlen($search) < 2) {
            return response()->json(['status' => true, 'results' => []]);
        }

        $localItems = self::searchResidenceLocal($search);
        if ($localItems) {
            return response()->json(['status' => true, 'results' => $localItems]);
        }

        // включённость провайдеров (админка → "Внешние API") — часть ключа кэша, чтобы пустой
        // результат от выключенного провайдера не залипал на сутки после его включения
        $integrations = app(IntegrationManager::class);
        $usePhoton = $integrations->isEnabled('photon');
        $useNominatim = $integrations->isEnabled('nominatim');
        $cacheKey = 'contacts-residence-search:' . (int) $usePhoton . (int) $useNominatim . ':' . mb_strtolower($search);

        $results = Cache::remember($cacheKey, now()->addDay(), function () use ($search, $usePhoton, $useNominatim) {
            // Один источник не покрывает всё: у Photon индекс по префиксам слов (годится для
            // "печатаю и сразу вижу совпадения" — "Чугу"/"Чугуе"), но он ищет только по
            // основному "name" из OSM, без языковых вариантов — украинский город "Чугуїв"
            // так и остаётся "Чугуїв", даже если ввести русское "Чугуев" целиком. А вот у
            // Nominatim в индексе есть и альтернативные/языковые названия (name:ru и т.п.),
            // поэтому он находит "Чугуев" → "Чугуев, Украина", но только по ПОЛНОМУ слову —
            // на частичном вводе съезжает в нечёткий (typo-tolerant) поиск и подсовывает
            // случайные города. Поэтому оба источника вместе: Photon отвечает за подсказки
            // по ходу набора, Nominatim подхватывает точное русское название под конец.
            $items = $usePhoton ? self::searchResidencePhoton($search) : [];

            foreach ($useNominatim ? self::searchResidenceNominatim($search) : [] as $item) {
                if (count($items) >= 6) {
                    break;
                }
                if (!in_array($item['label'], array_column($items, 'label'), true)) {
                    $items[] = $item;
                }
            }

            return array_slice($items, 0, 6);
        });

        return response()->json(['status' => true, 'results' => $results]);
    }

    /**
     * Своя база (GeoNames, импортирована локально) — ищем по всем вариантам названия
     * города (ru/en/оригинал/ascii-транслитерация), чтобы находило и при вводе на
     * русском, и на английском. Сортировка по населению — крупные города вперёд.
     */
    private static function searchResidenceLocal(string $search): array
    {
        $like = str_replace(['%', '_'], ['\\%', '\\_'], $search) . '%';

        $rows = DB::table('residence_cities as city')
            ->leftJoin('residence_countries as country', 'country.iso_code', '=', 'city.country_code')
            ->where(function ($q) use ($like) {
                $q->where('city.name_ru', 'like', $like)
                    ->orWhere('city.name_en', 'like', $like)
                    ->orWhere('city.name', 'like', $like)
                    ->orWhere('city.name_ascii', 'like', $like);
            })
            ->orderByDesc('city.population')
            ->limit(6)
            ->get([
                'city.name as city_name', 'city.name_ru as city_ru', 'city.name_en as city_en',
                'country.name as country_name', 'country.name_ru as country_ru', 'country.name_en as country_en',
                'city.lat', 'city.lng',
            ]);

        $items = [];
        foreach ($rows as $row) {
            $cityLabel = $row->city_ru ?? $row->city_en ?? $row->city_name;
            $countryLabel = $row->country_ru ?? $row->country_en ?? $row->country_name;
            $label = $countryLabel ? "{$cityLabel}, {$countryLabel}" : $cityLabel;

            if (!in_array($label, array_column($items, 'label'), true)) {
                $items[] = [
                    'label' => $label,
                    'lat' => $row->lat !== null ? (float) $row->lat : null,
                    'lng' => $row->lng !== null ? (float) $row->lng : null,
                ];
            }
        }

        return $items;
    }

    /**
     * Photon (komoot) — геокодинг на данных OSM с индексом по префиксам слов, поэтому
     * годится для автокомплита прямо по ходу набора (см. searchResidence).
     */
    private static function searchResidencePhoton(string $search): array
    {
        try {
            $response = Http::withHeaders([
                    'User-Agent' => 'SortingAdminPanel/1.0 (' . (config('app.url') ?: 'contact@example.com') . ')',
                ])
                ->timeout(5)
                ->get('https://photon.komoot.io/api/', [
                    'q' => $search,
                    'limit' => 12,
                ]);

            if (!$response->successful()) {
                return [];
            }

            $items = [];
            foreach ($response->json('features') ?: [] as $feature) {
                $props = $feature['properties'] ?? [];

                // берём только настоящие населённые пункты — Photon вперемешку с ними
                // отдаёт вершины/вокзалы/КПП/районы и области целиком (osm_key/osm_value
                // у них другие), для поля "место жительства" это не подходит
                $isSettlement = ($props['osm_key'] ?? null) === 'place' && in_array($props['osm_value'] ?? null, [
                    'city', 'town', 'village', 'hamlet', 'municipality', 'suburb', 'borough',
                ], true);

                if (!$isSettlement) {
                    continue;
                }

                $name = $props['name'] ?? null;
                $country = $props['country'] ?? null;
                $label = ($name && $country) ? "{$name}, {$country}" : $name;

                if (!$label || in_array($label, array_column($items, 'label'), true)) {
                    continue;
                }

                // geometry.coordinates у GeoJSON — [долгота, широта], в этом порядке
                $coords = $feature['geometry']['coordinates'] ?? null;
                $items[] = [
                    'label' => $label,
                    'lat' => isset($coords[1]) ? (float) $coords[1] : null,
                    'lng' => isset($coords[0]) ? (float) $coords[0] : null,
                ];

                if (count($items) >= 6) {
                    break;
                }
            }

            return $items;
        } catch (\Throwable $e) {
            // Photon недоступен/таймаут — просто без этой части подсказок
            return [];
        }
    }

    /**
     * Nominatim — резервный источник специально ради русских названий (name:ru и т.п.),
     * которых нет в индексе Photon. У него нет автокомплита по неполному слову, поэтому
     * толку от него на "Чугу"/"Чугуе" немного, зато на "Чугуев" он найдёт "Чугуев, Украина".
     * Фильтр по вхождению строки — защита от его же нечёткого (typo-tolerant) поиска,
     * который на коротких/редких запросах подсовывает вообще не похожие города.
     */
    private static function searchResidenceNominatim(string $search): array
    {
        try {
            $response = Http::withHeaders([
                    // Nominatim блокирует запросы без внятного User-Agent/контакта — см.
                    // https://operations.osmfoundation.org/policies/nominatim/
                    'User-Agent' => 'SortingAdminPanel/1.0 (' . (config('app.url') ?: 'contact@example.com') . ')',
                ])
                ->timeout(5)
                ->get('https://nominatim.openstreetmap.org/search', [
                    'q' => $search,
                    'format' => 'jsonv2',
                    'addressdetails' => 1,
                    'limit' => 8,
                    'accept-language' => 'ru',
                ]);

            if (!$response->successful()) {
                return [];
            }

            $items = [];
            foreach ($response->json() ?: [] as $row) {
                $address = $row['address'] ?? [];
                $city = $address['city'] ?? $address['town'] ?? $address['village']
                    ?? $address['municipality'] ?? $address['county'] ?? null;
                $country = $address['country'] ?? null;
                $label = ($city && $country) ? "{$city}, {$country}" : ($row['display_name'] ?? null);

                if (!$label || mb_stripos($city ?? $label, $search) === false) {
                    continue;
                }

                if (in_array($label, array_column($items, 'label'), true)) {
                    continue;
                }

                $items[] = [
                    'label' => $label,
                    'lat' => isset($row['lat']) ? (float) $row['lat'] : null,
                    'lng' => isset($row['lon']) ? (float) $row['lon'] : null,
                ];

                if (count($items) >= 6) {
                    break;
                }
            }

            return $items;
        } catch (\Throwable $e) {
            // Nominatim недоступен/таймаут/заблокировал по лимиту — просто без этой части
            return [];
        }
    }

    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'birth_date' => 'nullable|date',
            'residence' => 'nullable|string|max:255',
            'residence_lat' => 'nullable|numeric|between:-90,90',
            'residence_lng' => 'nullable|numeric|between:-180,180',
            'phone' => 'nullable|string|max:50',
            'photo' => 'nullable|file|mimes:jpeg,png,jpg,gif,webp,avif',
            'hashtag_ids' => 'nullable|string',
        ]);

        $contact = Contact::create([
            'user_id' => Auth::id(),
            'name' => $request->name,
            'birth_date' => $request->birth_date ?: null,
            'residence' => $request->residence,
            'residence_lat' => $request->residence_lat ?: null,
            'residence_lng' => $request->residence_lng ?: null,
            'phone' => $request->phone,
        ]);

        if ($request->hasFile('photo')) {
            $contact->photo = json_encode($this->storeContactImage($request->file('photo')));
        }

        $this->syncHashtags($contact, $request->hashtag_ids);
        $contact->save();

        return response()->json([
            'status' => true,
            'message' => 'Контакт добавлен',
            'redirect' => route('contacts.show', $contact->id),
        ]);
    }

    public function show(Contact $contact)
    {
        $this->authorizeOwner($contact);

        $contact->load(['hashtags', 'photos', 'conversations', 'socialLinks', 'facts']);

        // на вкладке "Посты" — только первая порция, остальные подгружаются при прокрутке (posts())
        $relatedIds = $this->relatedPostIds($contact);

        // переписок немного — все сразу, из всех постов контакта, а не только первой порции
        $chatPosts = $relatedIds->isEmpty() ? collect() : $this->postsForCards(Post::whereIn('id', $relatedIds)
            ->whereHas('hashtags', function ($q) {
                $q->whereIn('title', self::CHAT_HASHTAGS);
            })
            ->pluck('id'));
        [$chatPosts, $chatSources] = $this->chatPosts($chatPosts);

        $quotePosts = $relatedIds->isEmpty() ? collect() : $this->postsForCards(Post::whereIn('id', $relatedIds)
            ->whereHas('hashtags', function ($q) {
                $q->whereIn('title', self::QUOTE_HASHTAGS);
            })
            ->pluck('id'));

        return view('user.contacts.show', [
            'quotesFeed' => $this->quotesFeed($quotePosts),
            'contact' => $contact,
            'relatedPosts' => $this->postsForCards($relatedIds->take(self::POSTS_PER_PAGE)),
            'relatedTotal' => $relatedIds->count(),
            'postsPerPage' => self::POSTS_PER_PAGE,
            'chatPosts' => $chatPosts,
            'chatSources' => $chatSources,
        ]);
    }

    /**
     * Карточки вкладки "Высказывания" (quotes_tab.blade.php) из постов контакта с хештегом "высказывание":
     * скрин(ы) — картинки поста, текст — текст поста. Абзацы "Источник: ..." и "Комментарий: ...", которые
     * пишет createQuotePost(), показываются отдельно. Новые сверху — по дате пина, без неё — по дате поста
     *
     * @param \Illuminate\Support\Collection $posts посты с хештегами (postsForCards())
     * @return \Illuminate\Support\Collection
     */
    private function quotesFeed($posts)
    {
        return $posts->map(function (Post $post) {
            $content = (string)$post->content;
            $partOf = function ($class) use ($content) {
                return preg_match('#<p class="' . $class . '">(.*?)</p>#su', $content, $m) ? $m[1] : null;
            };
            $sourceHtml = $partOf('b-quote-source');
            $commentHtml = $partOf('b-quote-comment');
            $quoteHtml = trim(preg_replace('#<p class="b-quote-(source|comment)">.*?</p>#su', '', $content));
            $text = trim(html_entity_decode(strip_tags(preg_replace('#<br\s*/?>|</p>#i', "\n", preg_replace('/\R/u', '', $quoteHtml))), ENT_QUOTES | ENT_HTML5, 'UTF-8'));

            $titles = $post->hashtags->map(function ($hashtag) {
                return mb_strtolower(trim($hashtag->title));
            })->all();
            $platform = 'Другое';
            foreach (self::QUOTE_PLATFORMS as $label => $source) {
                if (array_intersect($source['hashtags'], $titles)) {
                    $platform = $label;
                    break;
                }
            }
            [$icon, $color] = ContactSocialLink::iconFor($platform);

            $images = collect(UploadImageController::normalizeImages($post->original_image))->map(function ($image) {
                return '/storage' . $image['path'];
            })->values();
            $date = $post->event_date ?: $post->created_at;

            return [
                'post' => $post,
                'quote_html' => $quoteHtml,
                'text' => $text,
                'comment' => $commentHtml !== null
                    ? trim(preg_replace('/^Комментарий:\s*/u', '', html_entity_decode(strip_tags(str_ireplace(['<br>', '<br />', '<br/>'], "\n", $commentHtml)), ENT_QUOTES | ENT_HTML5, 'UTF-8')))
                    : null,
                'original_url' => $sourceHtml !== null && preg_match('#href="([^"]+)"#', $sourceHtml, $m) ? html_entity_decode($m[1]) : null,
                'images' => $images,
                'content_type' => $images->isNotEmpty() ? 'screenshot' : 'text',
                'platform' => $platform,
                'icon' => $icon,
                'color' => $color,
                'hashtags' => $post->hashtags->reject(function ($hashtag) {
                    return in_array(mb_strtolower(trim($hashtag->title)), self::QUOTE_HASHTAGS, true);
                })->pluck('title')->values(),
                'timestamp' => $date ? $date->getTimestamp() : 0,
                'date_label' => $post->event_date
                    ? $post->eventDateLabel()
                    : ($post->created_at ? $post->created_at->locale('ru')->translatedFormat('d F Y, H:i') : ''),
            ];
        })->sortByDesc('timestamp')->values();
    }

    /**
     * Высказывание контакта — новый пост: скрины — картинки поста, текст — его текст, плюс абзацы со ссылкой
     * на источник и комментарием (их quotesFeed() показывает отдельно). Хештеги: "высказывание", именные
     * хештеги контакта (по ним пост попадёт к нему — relatedPostIds()) вместе с тем, что они добавляют
     * автоматически, источник (QUOTE_PLATFORMS) и выбранные в поп-апе. Дата высказывания — дата пина.
     * Похожие картинки не отклоняются: скрин добавили осознанно
     *
     * Используется поп-апом "Добавить высказывание" (storeQuote()) и переносом старых высказываний
     * (команда contacts:quotes-to-posts)
     *
     * @param Contact $contact хештеги загружены
     * @param array $data quote, platform, source_url, occurred_at (Y-m-d), is_exact_date, comment, hashtag_ids (int[])
     * @param \Illuminate\Http\UploadedFile[] $files скрины
     * @return Post
     * @throws \Exception картинку не удалось сохранить
     */
    public function createQuotePost(Contact $contact, array $data, array $files = []): Post
    {
        $userId = (int)Auth::id();

        $post = new Post();
        $post->user_id = $userId;
        $post->category_id = 0;
        $post->content = $this->quotePostContent($data);
        $post->alias = $this->freePostAlias($userId . '_0_quote_' . time());

        if (!empty($data['occurred_at'])) {
            $date = \Carbon\Carbon::parse($data['occurred_at']);
            // "Точная дата" снята — знаем только месяц
            $post->setEventDateFromInput(($data['is_exact_date'] ?? true) ? $date->format('Y-m-d') : $date->format('Y-m'));
        }

        $uploader = new UploadImageController();
        $saved = [];
        foreach ($files as $file) {
            $staged = $uploader->stageFileToTempDirectory($file);
            $result = $uploader->saveImageForPost($staged['data'], (string)$userId, '0', false, true);
            if (!empty($result['errors']) || empty($result['original_image'])) {
                $errors = $result['errors'] ?? 'не удалось сохранить картинку';
                throw new \Exception(is_array($errors) ? implode(' ', $errors) : $errors);
            }
            $saved[] = $result;
        }

        $original = [];
        $medium = [];
        $small = [];
        foreach ($saved as $result) {
            $original[$result['original_image']['name']] = $result['original_image'];
            if (!empty($result['medium_image'])) {
                $medium[$result['medium_image']['name']] = $result['medium_image'];
            }
            if (!empty($result['small_image'])) {
                $small[$result['small_image']['name']] = $result['small_image'];
            }
        }
        if ($original) {
            $post->original_image = json_encode($original);
            $post->medium_image = json_encode($medium);
            $post->small_image = json_encode($small);
        }
        $post->save();

        // хеши картинок — для поиска дублей, как у постов с формы создания
        foreach ($saved as $result) {
            if (!empty($result['phash'])) {
                UploadImageController::storeImageHash($post->id, $result['original_image']['name'], $result['original_image']['path'],
                    $result['width'], $result['height'], $result['phash'], $result['perceptual_hash'] ?? null);
            }
        }

        $post->hashtags()->sync($this->quotePostHashtagIds($contact, $data));

        return $post;
    }

    /**
     * Текст поста-высказывания: абзацы текста, затем "Источник: ссылка" и "Комментарий: ..." — с классами,
     * чтобы quotesFeed() мог показать их отдельно от самого высказывания
     *
     * @param array $data
     * @return string
     */
    private function quotePostContent(array $data): string
    {
        $paragraph = function ($text) {
            return nl2br(e(trim($text)), false);
        };

        $html = collect(preg_split('/\R\s*\R/u', trim((string)($data['quote'] ?? ''))))
            ->filter(function ($part) {
                return trim($part) !== '';
            })
            ->map(function ($part) use ($paragraph) {
                return '<p>' . $paragraph($part) . '</p>';
            })
            ->implode('');

        $url = trim((string)($data['source_url'] ?? ''));
        if ($url !== '' && preg_match('#^https?://#i', $url)) {
            $html .= '<p class="b-quote-source">Источник: <a href="' . e($url) . '" target="_blank" rel="noopener">' . e($url) . '</a></p>';
        }
        $comment = trim((string)($data['comment'] ?? ''));
        if ($comment !== '') {
            $html .= '<p class="b-quote-comment">Комментарий: ' . $paragraph($comment) . '</p>';
        }

        return $html;
    }

    /**
     * Хештеги поста-высказывания (см. createQuotePost())
     *
     * @param Contact $contact
     * @param array $data platform, hashtag_ids
     * @return int[]
     */
    private function quotePostHashtagIds(Contact $contact, array $data): array
    {
        $userId = (int)Auth::id();
        $userHashtag = function (array $titles, string $createTitle) use ($userId) {
            $found = Hashtag::where('user_id', $userId)->whereIn('title', $titles)->get();
            $hashtag = $found->first(function ($hashtag) use ($createTitle) {
                return mb_strtolower($hashtag->title) === mb_strtolower($createTitle);
            }) ?: $found->first();
            if (!$hashtag) {
                $hashtag = new Hashtag();
                $hashtag->title = $createTitle;
                $hashtag->user_id = $userId;
                $hashtag->save();
            }
            return (int)$hashtag->id;
        };

        $ids = [$userHashtag(self::QUOTE_HASHTAGS, self::QUOTE_HASHTAGS[0])];

        // именные хештеги контакта (без них пост к нему не попадёт); нет именных — все его хештеги
        [$identityIds] = $this->contactPostHashtagIds($contact);
        $contactIds = $identityIds->isNotEmpty() ? $identityIds : $contact->hashtags->pluck('id');
        // ...и то, что они добавляют к посту автоматически — как на форме создания поста
        $autoIds = $contactIds->isEmpty() ? collect() : DB::table('hashtag_relations')
            ->whereIn('hashtag_id', $contactIds)
            ->where('auto_add', true)
            ->pluck('related_hashtag_id');
        $ids = array_merge($ids, $contactIds->all(), $autoIds->all());

        $platform = self::QUOTE_PLATFORMS[$data['platform'] ?? ''] ?? null;
        if ($platform) {
            $ids[] = $userHashtag($platform['hashtags'], $platform['tag']);
        }

        if (!empty($data['hashtag_ids'])) {
            $ids = array_merge($ids, Hashtag::where('user_id', $userId)->whereIn('id', (array)$data['hashtag_ids'])->pluck('id')->all());
        }

        return array_values(array_unique(array_map('intval', $ids)));
    }

    /**
     * Свободный alias поста (колонка уникальна; посты из корзины тоже занимают свой — их могут вернуть)
     *
     * @param string $alias
     * @return string
     */
    private function freePostAlias(string $alias): string
    {
        $candidate = $alias;
        for ($i = 2; Post::withTrashed()->where('alias', $candidate)->exists(); $i++) {
            $candidate = $alias . '-' . $i;
        }

        return $candidate;
    }

    /**
     * Следующая порция постов контакта (вкладка "Посты", подгрузка при прокрутке — admin.js loadContactPosts()).
     * include/exclude — поиск по хештегам среди постов контакта: с каждым из include и ни с одним из exclude
     *
     * @param Request $request page (с 1), include[], exclude[] — id хештегов
     * @param Contact $contact
     * @return \Illuminate\Http\JsonResponse html карточек, total — сколько всего по фильтру, has_more
     */
    public function posts(Request $request, Contact $contact)
    {
        $this->authorizeOwner($contact);
        $contact->load('hashtags');

        $ids = function ($key) use ($request) {
            return array_values(array_filter(array_map('intval', (array)$request->input($key, []))));
        };
        $relatedIds = $this->relatedPostIds($contact, $ids('include'), $ids('exclude'));
        $page = max(1, (int)$request->input('page', 1));
        $pageIds = $relatedIds->slice(($page - 1) * self::POSTS_PER_PAGE, self::POSTS_PER_PAGE)->values();

        return response()->json([
            'html' => $pageIds->isEmpty() ? '' : view('user.posts.parts.post_items', [
                'posts' => $this->postsForCards($pageIds),
                'linksOnly' => true,
            ])->render(),
            'total' => $relatedIds->count(),
            'has_more' => $page * self::POSTS_PER_PAGE < $relatedIds->count(),
        ]);
    }

    /**
     * Id постов контакта, новые сверху. Одного общего хештега мало, чтобы считать пост "про этого человека" —
     * у контакта могут быть и широкие категориальные теги (например, "мужчины"), которые есть и на совершенно
     * посторонних постах. Требуем совпадения минимум по двум хештегам (либо по одному, если у контакта их всего
     * один — иначе такие контакты вообще никогда не находили бы посты). Именного хештега
     * (см. contactPostHashtagIds()) хватает и одного
     *
     * @param Contact $contact хештеги уже загружены
     * @param int[] $includeIds пост должен быть с каждым из этих хештегов (поиск по хештегам)
     * @param int[] $excludeIds ...и ни с одним из этих
     * @return \Illuminate\Support\Collection
     */
    private function relatedPostIds(Contact $contact, array $includeIds = [], array $excludeIds = [])
    {
        [$identityIds, $byMatchIds] = $this->contactPostHashtagIds($contact);
        if ($identityIds->isEmpty() && $byMatchIds->isEmpty()) {
            return collect();
        }
        $minMatches = min(2, $byMatchIds->count());

        // для отбора хватает id поста и id его хештегов — сами посты грузятся только для показанной порции
        $tagsByPost = DB::table('hashtag_post')
            ->join('posts', 'posts.id', '=', 'hashtag_post.post_id')
            ->whereIn('hashtag_post.post_id', function ($q) use ($identityIds, $byMatchIds) {
                $q->select('post_id')->from('hashtag_post')->whereIn('hashtag_id', $identityIds->merge($byMatchIds)->unique());
            })
            ->where('posts.user_id', Auth::id())
            ->whereNull('posts.deleted_at')
            ->orderByDesc('posts.created_at')
            ->orderByDesc('posts.id')
            ->get(['hashtag_post.post_id', 'hashtag_post.hashtag_id'])
            ->groupBy('post_id');

        return $tagsByPost->filter(function ($rows) use ($identityIds, $byMatchIds, $minMatches, $includeIds, $excludeIds) {
            $postHashtagIds = $rows->pluck('hashtag_id');
            $isRelated = $postHashtagIds->intersect($identityIds)->isNotEmpty()
                || ($byMatchIds->isNotEmpty() && $postHashtagIds->intersect($byMatchIds)->count() >= $minMatches);

            return $isRelated
                && collect($includeIds)->diff($postHashtagIds)->isEmpty()
                && $postHashtagIds->intersect($excludeIds)->isEmpty();
        })->keys()->values();
    }

    /**
     * Посты для карточек (post_items.blade.php) в заданном порядке: с хештегами, фильмом и обложкой
     *
     * @param \Illuminate\Support\Collection $ids
     * @return \Illuminate\Support\Collection
     */
    private function postsForCards($ids)
    {
        if ($ids->isEmpty()) {
            return collect();
        }

        $posts = Post::whereIn('id', $ids)->with(['hashtags', 'film'])->get()->keyBy('id');
        $ordered = $ids->map(function ($id) use ($posts) {
            return $posts->get($id);
        })->filter()->values();

        foreach ($ordered as $post) {
            if (!empty($post->small_image) && isJson($post->small_image)) {
                $smallImage = json_decode($post->small_image, true);
                $post['cover_image'] = $smallImage['path'] ?? (array_values($smallImage)[0]['path'] ?? '');
            } elseif (!empty($post->medium_image) && isJson($post->medium_image)) {
                $mediumImage = json_decode($post->medium_image, true);
                $post['cover_image'] = $mediumImage['path'] ?? (array_values($mediumImage)[0]['path'] ?? '');
            } elseif (!empty($post->original_image) && isJson($post->original_image)) {
                $originalImage = json_decode($post->original_image, true);
                $post['cover_image'] = $originalImage['path'] ?? (array_values($originalImage)[0]['path'] ?? '');
            }
        }

        return $ordered;
    }

    /**
     * Переписки контакта (вкладка "Переписки"): его посты с хештегом "переписка". Источник — по хештегу поста
     * (см. CHAT_SOURCES), дата — дата пина (event_date, у скрина — когда сделан), а без неё — когда пост добавлен.
     * Сразу по дате, новые сверху; фильтр по источнику и другая сортировка — на месте (admin.js)
     *
     * @param \Illuminate\Support\Collection $relatedPosts посты контакта с загруженными хештегами
     * @return array [Collection $chatPosts (+ chat_source, chat_date), array $chatSources ключ => label/icon/count]
     */
    private function chatPosts($relatedPosts): array
    {
        $lower = function ($title) {
            return mb_strtolower(trim((string)$title));
        };

        $chatPosts = $relatedPosts->filter(function ($post) use ($lower) {
            return $post->hashtags->contains(function ($hashtag) use ($lower) {
                return in_array($lower($hashtag->title), self::CHAT_HASHTAGS, true);
            });
        })->values();

        $sources = [];
        foreach (self::CHAT_SOURCES as $key => $source) {
            $sources[$key] = ['label' => $source['label'], 'icon' => $source['icon'], 'count' => 0];
        }

        foreach ($chatPosts as $post) {
            $titles = $post->hashtags->map(function ($hashtag) use ($lower) {
                return $lower($hashtag->title);
            })->all();
            $post->chat_source = 'other';
            foreach (self::CHAT_SOURCES as $key => $source) {
                if (array_intersect($source['hashtags'], $titles)) {
                    $post->chat_source = $key;
                    break;
                }
            }
            $post->chat_date = $post->event_date ?: $post->created_at;
            $sources[$post->chat_source]['count']++;
        }

        $chatPosts = $chatPosts->sortByDesc(function ($post) {
            return $post->chat_date ? $post->chat_date->getTimestamp() : 0;
        })->values();

        return [$chatPosts, $sources];
    }

    /**
     * Хештеги, по которым к контакту подбираются посты.
     *
     * Именные — пост с любым из них точно про этого человека:
     *  - хештег с именем контакта (даже если его не добавили контакту; ударение в имени не мешает: "Ска́рсгард");
     *  - хештег контакта, который добавляет к посту автоматически другие его хештеги — общие ("Роми Стрейд" →
     *    "девушка", "модель"): общие есть на тысячах постов про других людей и сами по себе не считаются;
     *  - другие написания именных: связанные, которые добавляются автоматически в обе стороны
     *    ("Роми Стрейд" ↔ "Romee Strijd", "Сандра Буллок" ↔ "Sandra Bullock") — они не общие.
     * По совпадению — если общих не нашлось, все хештеги контакта: пост подходит, если совпали хотя бы два
     *
     * @param Contact $contact хештеги уже загружены
     * @return array [Collection $identityIds, Collection $byMatchIds]
     */
    private function contactPostHashtagIds(Contact $contact): array
    {
        $hashtagIds = $contact->hashtags->pluck('id');

        // автодобавление только в одну сторону: взаимное — это другое написание того же имени
        // ("Сандра Буллок" ↔ "Sandra Bullock"), а не общий хештег
        $generalRelations = $hashtagIds->isEmpty() ? collect() : DB::table('hashtag_relations as r')
            ->leftJoin('hashtag_relations as back', function ($join) {
                $join->on('back.hashtag_id', '=', 'r.related_hashtag_id')
                    ->on('back.related_hashtag_id', '=', 'r.hashtag_id')
                    ->where('back.auto_add', true);
            })
            ->whereIn('r.hashtag_id', $hashtagIds)
            ->whereIn('r.related_hashtag_id', $hashtagIds)
            ->where('r.auto_add', true)
            ->whereNull('back.hashtag_id')
            ->get(['r.hashtag_id', 'r.related_hashtag_id']);
        $generalIds = $generalRelations->pluck('related_hashtag_id')->unique();
        // именной хештег контакта — тот, что добавляет общие ("Новиков Руслан" → "знакомые"); прочие хештеги
        // контакта, ни с чем не связанные ("мужчины"), именными не считаются
        $keyIds = $generalRelations->pluck('hashtag_id')->unique()->diff($generalIds)->values();

        $name = trim((string)$contact->name);
        $names = array_values(array_unique(array_filter([$name, str_replace("\u{0301}", '', $name)])));
        $nameIds = $names ? Hashtag::where('user_id', Auth::id())->whereIn('title', $names)->pluck('id') : collect();

        $identityIds = $keyIds->merge($nameIds)->diff($generalIds)->unique()->values();
        if ($identityIds->isNotEmpty()) {
            $aliasIds = DB::table('hashtag_relations as r')
                ->join('hashtag_relations as back', function ($join) {
                    $join->on('back.hashtag_id', '=', 'r.related_hashtag_id')
                        ->on('back.related_hashtag_id', '=', 'r.hashtag_id');
                })
                ->whereIn('r.hashtag_id', $identityIds)
                ->where('r.auto_add', true)
                ->where('back.auto_add', true)
                ->pluck('r.related_hashtag_id');
            $identityIds = $identityIds->merge($aliasIds)->diff($generalIds)->unique()->values();
        }

        return [$identityIds, $generalIds->isEmpty() ? $hashtagIds : collect()];
    }

    public function update(Request $request, Contact $contact)
    {
        $this->authorizeOwner($contact);

        $request->validate([
            'name' => 'required|string|max:255',
            'birth_date' => 'nullable|date',
            'residence' => 'nullable|string|max:255',
            'residence_lat' => 'nullable|numeric|between:-90,90',
            'residence_lng' => 'nullable|numeric|between:-180,180',
            'phone' => 'nullable|string|max:50',
            'photo' => 'nullable|file|mimes:jpeg,png,jpg,gif,webp,avif',
            'hashtag_ids' => 'nullable|string',
        ]);

        $contact->name = $request->name;
        $contact->birth_date = $request->birth_date ?: null;
        $contact->residence = $request->residence;
        $contact->residence_lat = $request->residence_lat ?: null;
        $contact->residence_lng = $request->residence_lng ?: null;
        $contact->phone = $request->phone;

        if ($request->hasFile('photo')) {
            $this->deleteContactPhotoFile($contact->photo_data);
            $contact->photo = json_encode($this->storeContactImage($request->file('photo')));
        }

        $this->syncHashtags($contact, $request->hashtag_ids);
        $contact->save();

        return response()->json(['status' => true, 'message' => 'Контакт обновлён']);
    }

    public function destroy(Contact $contact)
    {
        $this->authorizeOwner($contact);

        $this->deleteContactPhotoFile($contact->photo_data);
        foreach ($contact->photos as $photo) {
            $this->deleteContactPhotoFile($photo->image_data);
        }
        foreach ($contact->conversations as $conversation) {
            $this->deleteContactPhotoFile($conversation->image_data);
        }

        // без внешних ключей в БД (как и везде в проекте) — чистим связанные записи вручную
        $contact->photos()->delete();
        $contact->conversations()->delete();
        $contact->socialLinks()->delete();
        foreach ($contact->quotes as $quote) {
            foreach ($quote->images as $image) {
                $this->deleteContactPhotoFile($image->image_data);
            }
            $quote->images()->delete();
            $quote->hashtags()->detach();
        }
        $contact->quotes()->delete();
        $contact->facts()->delete();
        $contact->hashtags()->detach();

        $contact->delete();

        return response()->json(['status' => true, 'message' => 'Контакт удалён']);
    }

    public function storePhoto(Request $request, Contact $contact)
    {
        $this->authorizeOwner($contact);

        $request->validate([
            'image' => 'required|file|mimes:jpeg,png,jpg,gif,webp,avif',
            'caption' => 'nullable|string|max:255',
        ]);

        $photo = ContactPhoto::create([
            'contact_id' => $contact->id,
            'image' => json_encode($this->storeContactImage($request->file('image'))),
            'caption' => $request->caption,
        ]);

        return response()->json([
            'status' => true,
            'message' => 'Фото добавлено',
            'photo' => [
                'id' => $photo->id,
                'image_url' => $photo->image_url,
                'caption' => $photo->caption,
            ],
        ]);
    }

    public function destroyPhoto(ContactPhoto $photo)
    {
        $this->authorizeOwner($photo->contact);

        $this->deleteContactPhotoFile($photo->image_data);
        $photo->delete();

        return response()->json(['status' => true, 'message' => 'Фото удалено']);
    }

    /**
     * Скрин переписки ("Переписки" на странице контакта) — та же механика, что и
     * у обычных фото (contact_photos), просто отдельная сущность/раздел на странице.
     */
    public function storeConversation(Request $request, Contact $contact)
    {
        $this->authorizeOwner($contact);

        $request->validate([
            'image' => 'required|file|mimes:jpeg,png,jpg,gif,webp,avif',
            'caption' => 'nullable|string|max:255',
        ]);

        $conversation = ContactConversation::create([
            'contact_id' => $contact->id,
            'image' => json_encode($this->storeContactImage($request->file('image'))),
            'caption' => $request->caption,
        ]);

        return response()->json([
            'status' => true,
            'message' => 'Скрин добавлен',
            'conversation' => [
                'id' => $conversation->id,
                'image_url' => $conversation->image_url,
                'caption' => $conversation->caption,
            ],
        ]);
    }

    public function destroyConversation(ContactConversation $conversation)
    {
        $this->authorizeOwner($conversation->contact);

        $this->deleteContactPhotoFile($conversation->image_data);
        $conversation->delete();

        return response()->json(['status' => true, 'message' => 'Скрин удалён']);
    }

    public function storeSocial(Request $request, Contact $contact)
    {
        $this->authorizeOwner($contact);

        $request->validate([
            'platform' => 'required|string|max:100',
            'handle' => 'required|string|max:255',
            'value' => 'nullable|string|max:500',
        ]);

        $social = ContactSocialLink::create([
            'contact_id' => $contact->id,
            'platform' => $request->platform,
            'handle' => $request->handle,
            'value' => $request->value ?: null,
        ]);

        return response()->json(['status' => true, 'social' => $social]);
    }

    public function destroySocial(ContactSocialLink $social)
    {
        $this->authorizeOwner($social->contact);

        $social->delete();

        return response()->json(['status' => true, 'message' => 'Удалено']);
    }

    /**
     * Распознавание текста со скриншотов ("Текст успешно распознан..." в поп-апе "Добавить
     * высказывание") — до сохранения самого высказывания, поэтому не привязано к контакту.
     * Изображения не сохраняются на диск — распознаём прямо из содержимого загруженного файла
     * и забываем. Использует OCR.space (см. App\Components\ImageReader) — при
     * нескольких изображениях распознанный текст каждого склеивается через пустую строку.
     */
    public function recognizeQuoteText(Request $request)
    {
        $request->validate([
            'images' => 'required|array|min:1',
            'images.*' => 'file|mimes:jpeg,png,jpg,gif,webp,avif|max:10240',
        ]);

        $reader = new ImageReader();
        $texts = [];

        foreach ($request->file('images') as $image) {
            try {
                // OCR.space AVIF не принимает — отдаём ему WebP (см. AvifImage)
                $texts[] = $reader->recognizeText(file_get_contents(AvifImage::normalize($image)->getRealPath()));
            } catch (\Throwable $e) {
                \Illuminate\Support\Facades\Log::error('ImageReader::recognizeText failed: ' . $e->getMessage());

                if (Str::contains($e->getMessage(), 'отключена администратором')) {
                    $message = 'Распознавание текста сейчас отключено администратором. Введите текст вручную.';
                } elseif (Str::contains($e->getMessage(), 'OCR_SPACE_API_KEY')) {
                    $message = 'Распознавание текста не настроено: нет ключа OCR.space. Введите текст вручную.';
                } else {
                    $message = 'Не удалось распознать текст. Введите его вручную.';
                }

                return response()->json(['status' => false, 'message' => $message]);
            }
        }

        return response()->json([
            'status' => true,
            'text' => implode("\n\n", array_filter($texts, function ($text) { return $text !== ''; })),
            'count' => count($texts),
        ]);
    }

    /**
     * Поп-ап "Добавить высказывание" (quote_add_modal.blade.php). Источник/ссылка/дата/теги/
     * комментарий/сами изображения сохраняются по-настоящему; текст можно распознать со
     * скриншотов автоматически (см. recognizeQuoteText() выше), но это отдельный запрос —
     * сюда просто приходит уже готовый (при необходимости отредактированный) текст.
     */
    public function storeQuote(Request $request, Contact $contact)
    {
        $this->authorizeOwner($contact);

        $request->validate([
            'quote' => 'required|string|max:5000',
            'platform' => 'nullable|string|max:100',
            'source_url' => 'nullable|string|max:500',
            'occurred_at' => 'nullable|date',
            'is_exact_date' => 'nullable|boolean',
            'comment' => 'nullable|string|max:1000',
            'hashtag_ids' => 'nullable|string',
            'images' => 'nullable|array',
            'images.*' => 'file|mimes:jpeg,png,jpg,gif,webp,avif|max:10240',
        ]);

        // высказывание — это пост с хештегом "высказывание" (скрины + текст), см. createQuotePost()
        $contact->load('hashtags');
        $hashtagIds = json_decode($request->hashtag_ids ?? '[]', true);

        try {
            $post = $this->createQuotePost($contact, [
                'quote' => $request->quote,
                'platform' => $request->platform,
                'source_url' => $request->source_url,
                'occurred_at' => $request->occurred_at,
                'is_exact_date' => $request->boolean('is_exact_date', true),
                'comment' => $request->comment,
                'hashtag_ids' => is_array($hashtagIds) ? $hashtagIds : [],
            ], $request->file('images', []));
        } catch (\Exception $e) {
            return response()->json(['status' => false, 'message' => 'Не удалось сохранить: ' . $e->getMessage()]);
        }

        return response()->json(['status' => true, 'post_id' => $post->id]);
    }

    public function destroyQuote(ContactQuote $quote)
    {
        $this->authorizeOwner($quote->contact);

        foreach ($quote->images as $image) {
            $this->deleteContactPhotoFile($image->image_data);
        }
        $quote->images()->delete();
        $quote->hashtags()->detach();
        $quote->delete();

        return response()->json(['status' => true, 'message' => 'Удалено']);
    }

    public function storeFact(Request $request, Contact $contact)
    {
        $this->authorizeOwner($contact);

        $request->validate([
            'fact' => 'required|string|max:2000',
        ]);

        $fact = ContactFact::create([
            'contact_id' => $contact->id,
            'fact' => $request->fact,
        ]);

        return response()->json(['status' => true, 'fact' => $fact]);
    }

    public function destroyFact(ContactFact $fact)
    {
        $this->authorizeOwner($fact->contact);

        $fact->delete();

        return response()->json(['status' => true, 'message' => 'Удалено']);
    }

    private function authorizeOwner(Contact $contact): void
    {
        abort_unless($contact->user_id == Auth::id(), 403);
    }

    /**
     * @param \App\Models\Contact|\App\Models\ContactQuote $model Любая модель со связью hashtags()
     * @param string|null $hashtagIdsJson JSON-массив id хештегов
     */
    private function syncHashtags($model, ?string $hashtagIdsJson): void
    {
        $ids = json_decode($hashtagIdsJson ?? '[]', true);
        $model->hashtags()->sync(is_array($ids) ? $ids : []);
    }

    /**
     * @param \Illuminate\Http\UploadedFile $file
     * @return array{name: string, extension: string, path: string}
     */
    private function storeContactImage($file): array
    {
        // AVIF сразу конвертируем в WebP (см. AvifImage)
        $file = AvifImage::normalize($file);

        $extension = strtolower($file->getClientOriginalExtension()) ?: 'jpg';
        $name = Str::random(20);
        $relativeDir = 'contacts/' . Auth::id();

        Storage::disk('local')->makeDirectory($relativeDir);
        $file->storeAs($relativeDir, $name . '.' . $extension, 'local');
        $relativePath = '/' . $relativeDir . '/' . $name . '.' . $extension;

        // приводим к разумному размеру, как и у остальных загрузок в проекте
        try {
            $absolutePath = Storage::disk('local')->path($relativePath);
            $image = Image::make($absolutePath);
            if ($image->width() > 1200) {
                $image->resize(1200, null, function ($constraint) {
                    $constraint->aspectRatio();
                    $constraint->upsize();
                });
                $image->save($absolutePath);
            }
        } catch (\Throwable $e) {
            // не смогли обработать — оставляем как есть
        }

        return [
            'name' => $name,
            'extension' => $extension,
            'path' => $relativePath,
        ];
    }

    private function deleteContactPhotoFile(?array $imageData): void
    {
        if (empty($imageData['path'])) {
            return;
        }

        Storage::disk('local')->delete($imageData['path']);
    }
}
