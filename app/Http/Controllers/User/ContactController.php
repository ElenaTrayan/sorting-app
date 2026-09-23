<?php

namespace App\Http\Controllers\User;

use App\Components\ImageReader;
use App\Http\Controllers\Controller;
use App\Models\Contact;
use App\Models\ContactConversation;
use App\Models\ContactFact;
use App\Models\ContactPhoto;
use App\Models\ContactQuote;
use App\Models\ContactQuoteImage;
use App\Models\ContactSocialLink;
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
            'photo' => 'nullable|file|mimes:jpeg,png,jpg,gif,webp',
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

        $contact->load(['hashtags', 'photos', 'conversations', 'socialLinks', 'quotes.hashtags', 'quotes.images', 'facts']);

        $hashtagIds = $contact->hashtags->pluck('id');
        $relatedPosts = collect();

        if ($hashtagIds->isNotEmpty()) {
            // одного общего хештега мало, чтобы считать пост "про этого человека" — у контакта
            // могут быть и широкие категориальные теги (например, "мужчины"), которые есть
            // и на совершенно посторонних постах. Требуем совпадения минимум по двум хештегам
            // (либо по одному, если у контакта их и всего один — иначе такие контакты вообще
            // никогда не находили бы посты)
            $minMatches = min(2, $hashtagIds->count());

            $candidatePosts = Post::whereHas('hashtags', function ($q) use ($hashtagIds) {
                $q->whereIn('hashtags.id', $hashtagIds);
            })
                ->where('user_id', Auth::id())
                ->with(['hashtags', 'film'])
                ->latest()
                ->get();

            $relatedPosts = $candidatePosts->filter(function ($post) use ($hashtagIds, $minMatches) {
                return $post->hashtags->pluck('id')->intersect($hashtagIds)->count() >= $minMatches;
            })->values();

            foreach ($relatedPosts as $post) {
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
        }

        return view('user.contacts.show', [
            'contact' => $contact,
            'relatedPosts' => $relatedPosts,
        ]);
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
            'photo' => 'nullable|file|mimes:jpeg,png,jpg,gif,webp',
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
            'image' => 'required|file|mimes:jpeg,png,jpg,gif,webp',
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
            'image' => 'required|file|mimes:jpeg,png,jpg,gif,webp',
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
            'images.*' => 'file|mimes:jpeg,png,jpg,gif,webp|max:10240',
        ]);

        $reader = new ImageReader();
        $texts = [];

        foreach ($request->file('images') as $image) {
            try {
                $texts[] = $reader->recognizeText(file_get_contents($image->getRealPath()));
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
            'images.*' => 'file|mimes:jpeg,png,jpg,gif,webp|max:10240',
        ]);

        $quote = ContactQuote::create([
            'contact_id' => $contact->id,
            'quote' => $request->quote,
            'platform' => $request->platform ?: null,
            'source_url' => $request->source_url ?: null,
            'occurred_at' => $request->occurred_at ?: null,
            'is_exact_date' => $request->boolean('is_exact_date', true),
            'comment' => $request->comment ?: null,
        ]);

        $this->syncHashtags($quote, $request->hashtag_ids);

        foreach ($request->file('images', []) as $image) {
            ContactQuoteImage::create([
                'contact_quote_id' => $quote->id,
                'image' => json_encode($this->storeContactImage($image)),
            ]);
        }

        $quote->load(['hashtags', 'images']);

        return response()->json(['status' => true, 'quote' => $quote]);
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
