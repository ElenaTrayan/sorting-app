<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Вещь в разделе "Мой гардероб": то, что уже есть (owned), или то, что нужно купить (wishlist) —
 * у последних картинка обычно взята из поста (post_id), чтобы собирать образы из будущих покупок.
 */
class WardrobeItem extends Model
{
    protected $table = 'wardrobe_items';

    public const STATUS_OWNED = 'owned';
    public const STATUS_WISHLIST = 'wishlist';

    public const STATUSES = [
        self::STATUS_OWNED => 'В гардеробе',
        self::STATUS_WISHLIST => 'Нужно купить',
    ];

    // категории и типы вещей — WardrobeCategory (список в БД, ведёт админ)

    // разделы "сочетается с" (compatGroup): категории обуви и аксессуаров, остальное — одежда
    private const SHOE_CATEGORIES = ['sneakers', 'shoes', 'boots', 'summer_shoes'];
    private const ACCESSORY_CATEGORIES = ['bag', 'hats', 'scarves', 'accessory', 'jewelry'];

    // размерные сетки для списка "Размер" в форме вещи (parts/size_select): раздел => размеры. В БД размер —
    // текст, поэтому значения разных сеток не должны совпадать (RU 42 / EU 42 / обувь 42); свой размер можно вписать
    public const SIZES = [
        'Буквенные' => ['XXS', 'XS', 'S', 'M', 'L', 'XL', 'XXL', '3XL', '4XL', '5XL'],
        'Одежда, российские' => ['RU 38', 'RU 40', 'RU 42', 'RU 44', 'RU 46', 'RU 48', 'RU 50', 'RU 52', 'RU 54', 'RU 56',
            'RU 58', 'RU 60', 'RU 62', 'RU 64'],
        'Одежда, европейские' => ['EU 32', 'EU 34', 'EU 36', 'EU 38', 'EU 40', 'EU 42', 'EU 44', 'EU 46', 'EU 48', 'EU 50',
            'EU 52', 'EU 54'],
        'Джинсы и брюки' => ['W24', 'W25', 'W26', 'W27', 'W28', 'W29', 'W30', 'W31', 'W32', 'W33', 'W34', 'W36', 'W38', 'W40'],
        'Обувь' => ['35', '35,5', '36', '36,5', '37', '37,5', '38', '38,5', '39', '39,5', '40', '40,5', '41', '41,5',
            '42', '42,5', '43', '44', '45', '46', '47'],
        'Без размера' => ['One size', 'Оверсайз'],
    ];

    // особенности ухода — пункты для отметки в форме вещи: раздел => [ключ => название].
    // В БД (care_options) — JSON-массив ключей; ключи не менять, названия можно
    public const CARE_OPTIONS = [
        'Стирка' => [
            'wash_hand' => 'Ручная стирка',
            'wash_delicate' => 'Деликатный режим',
            'wash_cold' => 'В холодной воде',
            'wash_30' => 'Стирка при 30°',
            'wash_40' => 'Стирка при 40°',
            'wash_60' => 'Стирка при 60°',
            'wash_no' => 'Не стирать',
            'wash_separate' => 'Стирать отдельно',
            'wash_inside_out' => 'Стирать наизнанку',
            'wash_bag' => 'В мешке для стирки',
            'wash_no_softener' => 'Без кондиционера',
            'bleach_no' => 'Не отбеливать',
        ],
        'Отжим и сушка' => [
            'spin_no' => 'Не отжимать',
            'spin_gentle' => 'Щадящий отжим',
            'dry_flat' => 'Сушить горизонтально',
            'dry_hanger' => 'Сушить на плечиках',
            'dry_shade' => 'Сушить в тени',
            'tumble_no' => 'Не сушить в машине',
            'tumble_low' => 'Сушка в машине на низкой температуре',
        ],
        'Глажка' => [
            'iron_no' => 'Не гладить',
            'iron_low' => 'Гладить при низкой температуре',
            'iron_medium' => 'Гладить при средней температуре',
            'iron_high' => 'Гладить при высокой температуре',
            'iron_inside' => 'Гладить с изнанки',
            'iron_cloth' => 'Гладить через ткань',
            'steam' => 'Отпаривать',
            'steam_no' => 'Без пара',
        ],
        'Чистка' => [
            'dryclean' => 'Только химчистка',
            'dryclean_no' => 'Химчистка запрещена',
            'clean_brush' => 'Чистить щёткой',
            'clean_damp' => 'Протирать влажной тканью',
            'waterproof' => 'Обрабатывать водоотталкивающей пропиткой',
            'cream' => 'Ухаживать кремом',
        ],
        'Хранение' => [
            'store_folded' => 'Хранить сложенной',
            'store_hanger' => 'Хранить на плечиках',
            'store_cover' => 'Хранить в чехле',
            'store_shape' => 'Хранить с формодержателями',
            'store_moth' => 'Защищать от моли',
        ],
    ];

    // группы стирки — что стирать вместе (страница "Стирка"). В БД (wash_group) — ключ; пусто — группа
    // подбирается сама (suggestWashGroup). Ключи не менять, названия можно
    public const WASH_GROUPS = [
        'white' => 'Белое',
        'black' => 'Чёрное',
        'grey' => 'Серое',
        'beige' => 'Бежевое',
        'muted' => 'Цветное неяркое',
        'bright' => 'Цветное яркое',
        'denim' => 'Джинсы и тёмно-синее',
        'delicate' => 'Деликатное в мешке',
        'hand' => 'Ручная индивидуальная',
        'dryclean' => 'Химчистка',
    ];

    // через сколько дней вещь на странице "Стирка" считается давно не стиранной
    public const WASH_OVERDUE_DAYS = 30;

    // особая стирка — пятна, которые выводят отдельно; в БД (special_wash_types) — JSON-массив ключей
    public const SPECIAL_WASH_TYPES = [
        'armpits' => 'Жёлтые пятна подмышками',
        'deodorant' => 'Белые следы дезодоранта',
        'stain' => 'Пятно',
        'dingy' => 'Белое посерело или пожелтело',
        'smell' => 'Запах',
    ];

    public const WEAR_LEVELS = [
        'new' => 'Новая',
        'excellent' => 'Отличное состояние',
        'good' => 'Хорошее состояние',
        'worn' => 'Заметно поношена',
        'very_worn' => 'Сильно изношена',
    ];

    public const STYLES = [
        'casual' => 'Кэжуал',
        'classic' => 'Классика',
        'business' => 'Деловой',
        'romantic' => 'Романтичный',
        'sport' => 'Спортивный',
        'street' => 'Стритстайл',
        'minimalism' => 'Минимализм',
        'boho' => 'Бохо',
        'evening' => 'Вечерний',
        'glamour' => 'Гламур',
        'preppy' => 'Преппи',
        'grunge' => 'Гранж',
        'home' => 'Домашний',
    ];

    // страны производства для списка в форме вещи (parts/list_select); в БД — текст, свою страну можно вписать
    public const COUNTRIES = ['Австралия', 'Бангладеш', 'Беларусь', 'Болгария', 'Бразилия', 'Великобритания', 'Венгрия',
        'Вьетнам', 'Германия', 'Греция', 'Грузия', 'Египет', 'Израиль', 'Индия', 'Индонезия', 'Иордания', 'Испания',
        'Италия', 'Казахстан', 'Камбоджа', 'Канада', 'Китай', 'Кыргызстан', 'Латвия', 'Литва', 'Малайзия', 'Марокко',
        'Мексика', 'Молдова', 'Мьянма', 'Нидерланды', 'Пакистан', 'Перу', 'Польша', 'Португалия', 'Россия', 'Румыния',
        'Северная Македония', 'Сербия', 'Словакия', 'США', 'Таиланд', 'Тайвань', 'Тунис', 'Турция', 'Узбекистан', 'Украина',
        'Филиппины', 'Франция', 'Чехия', 'Швеция', 'Шри-Ланка', 'Эфиопия', 'Южная Корея', 'Япония'];

    // откуда вещь появилась — список в форме вещи (parts/list_select); в БД — текст, своё можно вписать
    public const ORIGINS = ['Купила в магазине', 'Купила онлайн', 'Купила в секонд-хенде', 'Купила в стоке',
        'Отдала сестра', 'Подарили', 'Сшила в ателье', 'Сшила сама'];

    // фасон — список в форме вещи (parts/list_select); в БД — текст, свой фасон можно вписать
    public const FITS = ['В обтяжку', 'Облегающее', 'Приталенное', 'Прямое', 'Свободное', 'Оверсайз', 'А-силуэт',
        'Трапеция', 'Расклёшенное', 'Кокон', 'С завышенной талией', 'С заниженной талией'];

    // особенности носки — пункты для отметки по разделам; в БД (features) — JSON-массив ключей, ключи не менять
    public const FEATURES = [
        'Что заметно' => [
            'sweat' => 'Видны пятна пота подмышками',
            'see_through' => 'Просвечивает',
            'bra_visible' => 'Видно бельё (швы, бретели)',
            'wrinkles' => 'Быстро мнётся',
            'static' => 'Электризуется',
            'pilling' => 'Скатывается (катышки)',
            'stains_dye' => 'Красит кожу или другие вещи',
            'stretches' => 'Растягивается при носке',
            'itchy' => 'Колется',
        ],
        'Под какое бельё' => [
            'bra_strapless' => 'Под бюстгальтер без бретелей',
            'bra_silicone' => 'Под силиконовый бюстгальтер или накладки',
            'bra_plunge' => 'Под бюстгальтер с глубоким вырезом',
            'bra_open_back' => 'Под бюстгальтер с низкой спинкой',
            'bra_nude' => 'Под телесное бельё',
            'seamless' => 'Под бесшовное бельё',
            'slip' => 'Нужна комбинация или нижняя юбка',
            'shapewear' => 'Под корректирующее бельё',
        ],
    ];

    // подсказки поля "Длина" (и у вещей, и у пинов "Одежда"); в БД — текст, своё значение можно вписать
    public const LENGTHS = ['Мини', 'Выше колена', 'До колена', 'Миди', 'Макси', 'В пол', 'Укороченная', 'До талии',
        'До бедра', 'Удлинённая', '7/8', 'Полная длина'];

    // повод — куда вещь носить (и у вещей, и у пинов "Одежда"). В БД (occasions) — JSON-массив ключей;
    // ключи не менять, названия можно
    public const OCCASIONS = [
        'everyday' => 'Повседневная носка',
        'work' => 'Работа / офис',
        'home' => 'Дом',
        'walk' => 'Прогулка',
        'date' => 'Свидание',
        'party' => 'Вечеринка',
        'celebration' => 'Праздник / торжество',
        'travel' => 'Отпуск и путешествия',
        'sport' => 'Спорт',
        'outdoor' => 'Активный отдых',
        'business' => 'Деловая встреча',
    ];

    // с чем вещь сочетается (wardrobe_item_compat) — разделы в форме и карточке вещи; в какой раздел попадает
    // вторая вещь пары, решает её категория (compatGroup())
    public const COMPAT_GROUPS = [
        'shoes' => 'Подходящая обувь',
        'accessories' => 'Аксессуары',
        'clothes' => 'Совместимые предметы гардероба',
    ];

    // типажи по Кибби — "кому подходит" (и у вещей, и у образов)
    public const BODY_TYPES = [
        'dramatic' => 'Драматик',
        'romantic' => 'Романтик',
        'natural' => 'Натурал',
        'gamine' => 'Гамин',
        'classic' => 'Классик',
    ];

    protected $fillable = [
        'user_id',
        'post_id',
        'name',
        'category',
        'kind',
        'status',
        'image',
        'brand_id',
        'storage_id',
        'color_id',
        'size',
        'length',
        'fit',
        'features',
        'features_note',
        'country',
        'origin',
        'seasons',
        'temp_min',
        'temp_max',
        'occasions',
        'print_id',
        'material_id',
        'composition',
        'care',
        'care_options',
        'wear_level',
        'needs_repair',
        'repair_note',
        'washed_at',
        'wash_group',
        'special_wash',
        'special_wash_types',
        'special_wash_note',
        'special_wash_fixed',
        'styles',
        'body_types',
        'price',
        'shop_url',
        'note',
    ];

    protected $casts = [
        'needs_repair' => 'boolean',
        'washed_at' => 'date',
        'special_wash' => 'boolean',
        'special_wash_types' => 'array',
        'special_wash_fixed' => 'boolean',
        'styles' => 'array',
        'body_types' => 'array',
        'care_options' => 'array',
        'features' => 'array',
        'price' => 'float',
        'seasons' => 'array',
        'occasions' => 'array',
        'temp_min' => 'integer',
        'temp_max' => 'integer',
    ];

    // пары "сочетается с" всех вещей пользователя: user_id => [id вещи => [id вещей]]. Карточки рисуют
    // toFormArray() каждой вещи — держим до конца запроса, чтобы не делать запрос на каждую
    private static array $compatMaps = [];

    /**
     * Раздел, в который вещь попадает в чужом списке "сочетается с": обувь / аксессуары / остальное
     *
     * @param string|null $category slug категории
     * @return string ключ COMPAT_GROUPS
     */
    public static function compatGroup(?string $category): string
    {
        if (in_array($category, self::SHOE_CATEGORIES, true)) {
            return 'shoes';
        }

        return in_array($category, self::ACCESSORY_CATEGORIES, true) ? 'accessories' : 'clothes';
    }

    /**
     * id вещей, с которыми сочетается эта (связь двусторонняя — неважно, у какой из двух её указали)
     *
     * @return int[]
     */
    public function compatibleIds(): array
    {
        $userId = (int)$this->user_id;

        if (!isset(self::$compatMaps[$userId])) {
            $map = [];
            try {
                $pairs = \Illuminate\Support\Facades\DB::table('wardrobe_item_compat')
                    ->whereIn('item_id', self::where('user_id', $userId)->select('id'))
                    ->get(['item_id', 'related_id']);
            } catch (\Illuminate\Database\QueryException $e) {
                $pairs = []; // таблицы ещё нет (миграция не запущена)
            }
            foreach ($pairs as $pair) {
                $map[$pair->item_id][] = (int)$pair->related_id;
                $map[$pair->related_id][] = (int)$pair->item_id;
            }
            self::$compatMaps[$userId] = $map;
        }

        return self::$compatMaps[$userId][$this->id] ?? [];
    }

    /**
     * Вещи, с которыми сочетается эта, по разделам: [ключ COMPAT_GROUPS => коллекция вещей]; пустые разделы пропущены
     *
     * @return array
     */
    public function compatibleByGroup(): array
    {
        $ids = $this->compatibleIds();
        if (!$ids) {
            return [];
        }

        $items = self::where('user_id', $this->user_id)->whereIn('id', $ids)->orderByDesc('id')->get();
        $groups = [];
        foreach (array_keys(self::COMPAT_GROUPS) as $group) {
            $inGroup = $items->filter(fn (self $item) => self::compatGroup($item->category) === $group)->values();
            if ($inGroup->isNotEmpty()) {
                $groups[$group] = $inGroup;
            }
        }

        return $groups;
    }

    /**
     * Сохранить список "сочетается с" из формы вещи: чужие, несуществующие id и саму вещь отбрасываем
     *
     * @param array $ids
     * @return void
     */
    public function syncCompatible(array $ids): void
    {
        $ids = self::where('user_id', $this->user_id)
            ->where('id', '!=', $this->id)
            ->whereIn('id', array_map('intval', $ids))
            ->pluck('id')
            ->all();

        $this->detachCompatible();

        $now = now();
        \Illuminate\Support\Facades\DB::table('wardrobe_item_compat')->insert(array_map(function ($id) use ($now) {
            // одна строка на пару: меньший id — в item_id
            return ['item_id' => min($this->id, $id), 'related_id' => max($this->id, $id), 'created_at' => $now, 'updated_at' => $now];
        }, $ids));
    }

    // убрать все пары вещи (перед сохранением нового списка и при удалении вещи)
    public function detachCompatible(): void
    {
        \Illuminate\Support\Facades\DB::table('wardrobe_item_compat')
            ->where('item_id', $this->id)
            ->orWhere('related_id', $this->id)
            ->delete();

        unset(self::$compatMaps[(int)$this->user_id]);
    }

    // "+5…+15 °C" — комфортная температура, как у образов; null — не указана
    public function temperatureLabel(): ?string
    {
        return WardrobeOutfit::formatTemperature($this->temp_min, $this->temp_max);
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function post()
    {
        return $this->belongsTo(Post::class, 'post_id');
    }

    // общий бренд или свой бренд пользователя (см. WardrobeBrand)
    public function brand()
    {
        return $this->belongsTo(WardrobeBrand::class, 'brand_id');
    }

    // где вещь лежит или висит (свой список пользователя, см. WardrobeStorage)
    public function storage()
    {
        return $this->belongsTo(WardrobeStorage::class, 'storage_id');
    }

    // принт из справочника: общий или свой принт пользователя (см. WardrobePrint)
    public function print()
    {
        return $this->belongsTo(WardrobePrint::class, 'print_id');
    }

    public function getPrintNameAttribute(): ?string
    {
        return $this->print_id ? optional($this->print)->name : null;
    }

    /**
     * Все пункты ухода одним списком: ключ => название
     *
     * @return array
     */
    public static function careOptions(): array
    {
        return array_merge(...array_values(self::CARE_OPTIONS));
    }

    // все особенности носки одним списком: ключ => название
    public static function featureOptions(): array
    {
        return array_merge(...array_values(self::FEATURES));
    }

    // особенности носки для превью: отмеченные пункты и своё
    public function featureLabels(): array
    {
        $labels = self::labelsFor($this->features, self::featureOptions());
        if (trim((string)$this->features_note) !== '') {
            $labels[] = trim($this->features_note);
        }

        return $labels;
    }

    /**
     * Уход за вещью одной строкой: отмеченные пункты (в порядке списка) и то, что вписано вручную
     *
     * @return string|null
     */
    public function careText(): ?string
    {
        $parts = self::labelsFor($this->care_options, self::careOptions());
        if (trim((string)$this->care) !== '') {
            $parts[] = trim($this->care);
        }

        return $parts ? implode(', ', $parts) : null;
    }

    // цвет из справочника: общий или свой цвет пользователя (см. WardrobeColor)
    public function color()
    {
        return $this->belongsTo(WardrobeColor::class, 'color_id');
    }

    public function getColorNameAttribute(): ?string
    {
        return $this->color_id ? optional($this->color)->name : null;
    }

    // материал из справочника: общий или свой материал пользователя (см. WardrobeMaterial)
    public function material()
    {
        return $this->belongsTo(WardrobeMaterial::class, 'material_id');
    }

    public function getMaterialNameAttribute(): ?string
    {
        return $this->material_id ? optional($this->material)->name : null;
    }

    public function getBrandNameAttribute(): ?string
    {
        return $this->brand_id ? optional($this->brand)->name : null;
    }

    /**
     * Фото вещи по порядку; первое — обложка (её копия — в колонке image, см. syncCover())
     */
    public function photos()
    {
        return $this->hasMany(WardrobeItemPhoto::class, 'item_id')->orderBy('sort_order')->orderBy('id');
    }

    /**
     * Обложка = первое фото: копируем его в колонку image, которую читают карточки, конструктор образов
     * и коллажи (одна картинка вещи). Нет фото — обложки нет
     */
    public function syncCover(): void
    {
        $first = $this->photos()->first();
        $this->image = $first ? $first->getRawOriginal('image') : null;
        $this->save();
    }

    public function outfits()
    {
        return $this->belongsToMany(WardrobeOutfit::class, 'wardrobe_outfit_item', 'item_id', 'outfit_id');
    }

    public function getImageDataAttribute(): ?array
    {
        if (empty($this->attributes['image'])) {
            return null;
        }

        $decoded = json_decode($this->attributes['image'], true);

        return is_array($decoded) ? $decoded : null;
    }

    public function getImageUrlAttribute(): ?string
    {
        $data = $this->image_data;

        return !empty($data['path']) ? '/storage' . $data['path'] : null;
    }

    public function isWishlist(): bool
    {
        return $this->status === self::STATUS_WISHLIST;
    }

    public function categoryLabel(): string
    {
        return WardrobeCategory::label($this->category);
    }

    // подпись на карточке: тип вещи ("Худи"), а без него — категория
    public function kindLabel(): string
    {
        return $this->kind ?: $this->categoryLabel();
    }

    // группа стирки: выбранная вручную или подобранная (ключ WASH_GROUPS; null — подобрать не из чего)
    public function washGroup(): ?string
    {
        return $this->wash_group ?: $this->suggestWashGroup();
    }

    /**
     * Группа стирки по вещи: сначала уход (химчистка, ручная, деликатная), потом тип и категория
     * (джинсы, бельё), потом цвет — по группе цвета, а цветное — по насыщенности оттенка (яркое / неяркое)
     *
     * @return string|null ключ WASH_GROUPS; null — цвет не указан
     */
    public function suggestWashGroup(): ?string
    {
        $care = $this->care_options ?: [];
        if (in_array('dryclean', $care, true)) {
            return 'dryclean';
        }
        if (in_array('wash_hand', $care, true)) {
            return 'hand';
        }
        if (in_array('wash_delicate', $care, true) || in_array('wash_bag', $care, true)
            || in_array($this->category, ['underwear', 'hosiery'], true)) {
            return 'delicate';
        }
        if (mb_stripos((string)$this->kind, 'джинс') !== false || mb_stripos((string)$this->name, 'джинс') !== false) {
            return 'denim';
        }

        $color = $this->color;
        if (!$color) {
            return self::washGroupFromName((string)$this->name);
        }
        $byColorGroup = ['white' => 'white', 'black' => 'black', 'grey' => 'grey', 'beige' => 'beige', 'navy' => 'denim',
            'brown' => 'muted', 'burgundy' => 'muted', 'multi' => 'bright'];
        if (isset($byColorGroup[$color->group])) {
            return $byColorGroup[$color->group];
        }

        return self::isBrightHex($color->hex) ? 'bright' : 'muted';
    }

    // цвет не выбран — по цвету в названии ("Рубашка белая" → white); не нашли — null
    private static function washGroupFromName(string $name): ?string
    {
        $words = [
            'white' => 'бел', 'black' => 'ч[её]рн', 'grey' => 'сер(ый|ая|ое|ые)', 'beige' => 'бежев',
            'denim' => 'темно-син|тёмно-син', 'bright' => 'красн|малинов|ярко-|оранжев|ж[её]лт|фуксия',
        ];
        foreach ($words as $group => $pattern) {
            if (preg_match('~(?<!\p{L})(' . $pattern . ')~iu', $name)) {
                return $group;
            }
        }

        return null;
    }

    // яркий оттенок — насыщенный и не слишком тёмный/светлый (HSL); без оттенка — неяркий
    private static function isBrightHex(?string $hex): bool
    {
        $hex = WardrobeColor::normalizeHex($hex);
        if ($hex === null) {
            return false;
        }
        [$r, $g, $b] = array_map(function ($part) {
            return hexdec($part) / 255;
        }, str_split(substr($hex, 1), 2));
        $max = max($r, $g, $b);
        $min = min($r, $g, $b);
        $lightness = ($max + $min) / 2;
        if ($max === $min) {
            return false;
        }
        $saturation = ($max - $min) / (1 - abs(2 * $lightness - 1));

        return $saturation >= 0.5 && $lightness >= 0.25 && $lightness <= 0.7;
    }

    // нужна особая стирка, и пятно ещё не исправлено
    public function needsSpecialWash(): bool
    {
        return $this->special_wash && !$this->special_wash_fixed;
    }

    // "сегодня", "вчера", "5 дн. назад"; null — дата стирки не указана
    public function washedAgo(): ?string
    {
        if (!$this->washed_at) {
            return null;
        }
        // "сегодня" — по часовому поясу пользователя ("Настройки аккаунта")
        $days = $this->washed_at->copy()->startOfDay()->diffInDays(User::today(), false);
        if ($days <= 0) {
            return 'сегодня';
        }

        return $days === 1 ? 'вчера' : $days . ' дн. назад';
    }

    // давно не стиралась: не стиралась ни разу (дата не указана) или дольше WASH_OVERDUE_DAYS
    public function isWashOverdue(): bool
    {
        return !$this->washed_at || $this->washed_at->lt(User::today()->subDays(self::WASH_OVERDUE_DAYS));
    }

    /**
     * Названия по ключам из JSON-колонки (styles, body_types); неизвестные ключи пропускаем
     *
     * @param array|null $keys
     * @param array $labels ключ => название
     * @return string[]
     */
    public static function labelsFor(?array $keys, array $labels): array
    {
        return array_values(array_intersect_key($labels, array_flip($keys ?: [])));
    }

    /**
     * Данные вещи для JS: форма редактирования (wardrobe.js, fillItemForm) и плитки в конструкторе образа
     *
     * @return array
     */
    public function toFormArray(): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'category' => $this->category,
            'category_label' => $this->categoryLabel(),
            'kind' => $this->kind,
            'washed_at' => $this->washed_at ? $this->washed_at->format('Y-m-d') : null,
            'wash_group' => $this->wash_group,
            // подобранная группа — подсказка в форме у "Автоматически"
            'wash_group_auto' => self::WASH_GROUPS[$this->suggestWashGroup()] ?? null,
            'special_wash' => $this->special_wash,
            'special_wash_types' => $this->special_wash_types ?: [],
            'special_wash_note' => $this->special_wash_note,
            'special_wash_fixed' => $this->special_wash_fixed,
            'status' => $this->status,
            'image_url' => $this->image_url,
            // все фото по порядку — галерея в форме вещи (wardrobe.js) и просмотр с карточки
            'photos' => $this->photos->map(function (WardrobeItemPhoto $photo) {
                return ['id' => $photo->id, 'url' => $photo->image_url];
            })->filter(function ($photo) {
                return $photo['url'] !== null;
            })->values()->all(),
            'brand_id' => $this->brand_id,
            'brand' => $this->brand_name,
            'storage_id' => $this->storage_id,
            'color_id' => $this->color_id,
            'color' => $this->color_name,
            'size' => $this->size,
            'length' => $this->length,
            'country' => $this->country,
            'origin' => $this->origin,
            'fit' => $this->fit,
            'features' => $this->features ?: [],
            'features_note' => $this->features_note,
            'seasons' => $this->seasons ?: [],
            'temp_min' => $this->temp_min,
            'temp_max' => $this->temp_max,
            'occasions' => $this->occasions ?: [],
            'compatible_ids' => $this->compatibleIds(),
            'print_id' => $this->print_id,
            'print' => $this->print_name,
            'material_id' => $this->material_id,
            'material' => $this->material_name,
            'composition' => $this->composition,
            'care' => $this->care,
            'care_options' => $this->care_options ?: [],
            'wear_level' => $this->wear_level,
            'needs_repair' => (bool)$this->needs_repair,
            'repair_note' => $this->repair_note,
            'styles' => $this->styles ?: [],
            'body_types' => $this->body_types ?: [],
            'price' => $this->price,
            'shop_url' => $this->shop_url,
            'note' => $this->note,
            'post_id' => $this->post_id,
        ];
    }
}
