<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;

/**
 * Данные фильма/сериала поста (таблица post_films, 1:1 с posts).
 * Заполняется с формы создания/редактирования поста — поля из resources/views/user/posts/parts/film_fields.blade.php
 */
class PostFilm extends Model
{
    protected $table = 'post_films';

    public const KINDS = ['Фильм', 'Сериал', 'Мини-сериал', 'Мультфильм', 'Мультсериал', 'Аниме', 'Документальный'];

    public const STATUSES = ['Посмотрел(а)', 'Смотрю', 'Хочу посмотреть', 'Бросил(а)'];

    // готовые варианты чипов "Достоинства"/"Недостатки" на форме (свои пользователь может дописать)
    public const PRESET_PROS = [
        'увлекательный сюжет', 'отличные актёры', 'красивый фильм', 'красивые пейзажи',
        'лёгкий', 'атмосферный', 'хороший юмор', 'держит в напряжении',
        'неожиданная концовка', 'отличный саундтрек', 'качественные спецэффекты',
        'глубокий смысл', 'хочется пересмотреть', 'для всей семьи',
    ];

    public const PRESET_CONS = [
        'посредственная игра актёров', 'слабо поставленные сцены боёв и трюков',
        'не новый сюжет', 'скучное повествование', 'качество звука', 'одноразовый',
        'затянутый', 'предсказуемый', 'слабая концовка', 'дыры в сюжете',
        'слабая графика', 'плохой перевод/озвучка',
    ];

    // "Вайб" — можно выбрать несколько. Группа => [вайб => эмодзи].
    // В базе хранится только название вайба — эмодзи лишь подпись на форме/карточке, её можно менять.
    // Не добавлены как дубли: "готический" (= "готика"), "романтичный" (= "романтический").
    public const VIBE_GROUPS = [
        'Настроение' => [
            'уютный' => '☕', 'тёплый' => '🧣', 'спокойный' => '🍃', 'умиротворяющий' => '🧘', 'лёгкий' => '🎈',
            'весёлый' => '😄', 'жизнерадостный' => '🌻', 'оптимистичный' => '🌈', 'вдохновляющий' => '✨',
            'романтический' => '💕', 'искренний' => '🤍', 'дружеский' => '🤝', 'ностальгический' => '📷',
            'повседневный' => '🛋️', 'праздничный' => '🎄', 'меланхолия' => '🌧️', 'грустный' => '😢',
            'драматичный' => '🎭', 'тревожный' => '😰', 'напряжённый' => '😬', 'скучный' => '😴',
        ],
        'Мрачное и мистическое' => [
            'готика' => '🦇', 'мистика' => '🔮', 'мистика без хоррора' => '🕯️', 'жуткий' => '👻',
            'мрачный' => '🌑', 'загадочный' => '🗝️', 'странный' => '👽', 'сюрреалистичный' => '🌀',
            'сказочный' => '🧚', 'кровавый' => '🩸', 'постапокалиптический' => '☢️',
            'про маньяков' => '🔪', 'слешер' => '🪓', 'про месть' => '🗡️',
        ],
        'Стиль и характер' => [
            'эстетичный' => '🎨', 'яркий' => '🎆', 'необычный' => '🦄', 'причудливый' => '🍄',
            'эксцентричный' => '🤹', 'смелый' => '🦁', 'уверенный' => '😎', 'дерзкий' => '💋',
            'женственный' => '🌷', 'гламурный' => '💎', 'игривый' => '😜', 'амбициозный' => '🏆',
            'провокационный' => '🌶️', 'возбуждающий' => '🔥', 'бунтарский' => '✊', 'контрастный' => '🌓',
            'молодёжный' => '🛹', 'авантюрный' => '🧭', 'динамичный' => '⚡',
        ],
        'Безумное и смешное' => [
            'смешной' => '😂', 'абсурдный' => '🤪', 'безумный' => '🤯', 'хаотичный' => '🌪️',
            'хулиганский' => '😈', 'пародийный' => '🃏', 'пошлый' => '🍑', 'непредсказуемый' => '🎲',
            'безбашенный' => '🤘', 'чёрный юмор' => '💀',
        ],
        'Время года' => [
            'осенний' => '🍂', 'зимний' => '❄️', 'весенний' => '🌸', 'летний' => '☀️',
        ],
        'Эпоха' => [
            'ретро' => '📻', 'old money' => '🎩', '1920s' => '🥂', '1960s' => '🕺', '1980s' => '📼',
            '1990s' => '💿', '2000s' => '📱', 'киберпанк' => '🦾', 'футуристический' => '🛸', 'будущее' => '⏳',
        ],
        'Место' => [
            'лес' => '🌲', 'море' => '🌊', 'деревня' => '🏡', 'город' => '🏙️',
            'маленький европейский город' => '🏘️', 'университет' => '🎓', 'космос' => '🚀', 'дорога' => '🚗',
        ],
    ];

    protected $fillable = [
        'post_id',
        'original_title',
        'kind',
        'seasons',
        'status',
        'year',
        'countries',
        'genres',
        'directors',
        'actors',
        'duration',
        'imdb_rating',
        'my_rating',
        'mpaa_rating',
        'description',
        'vibes',
        'pros',
        'cons',
    ];

    protected $casts = [
        'seasons' => 'integer',
        'year' => 'integer',
        'imdb_rating' => 'float',
        'my_rating' => 'integer',
        'vibes' => 'array',
        'pros' => 'array',
        'cons' => 'array',
    ];

    public function post()
    {
        return $this->belongsTo(Post::class, 'post_id');
    }

    /**
     * Поля фильма из запроса формы (имена инпутов — как в film_fields.blade.php) -> атрибуты модели.
     * Неверные значения (год "abc", рейтинг 15) не валят сохранение поста, а просто не сохраняются.
     *
     * @param Request $request
     * @return array|null - null, если ни одно поле не заполнено
     */
    public static function attributesFromRequest(Request $request): ?array
    {
        $text = function (string $name, int $maxLength = 0) use ($request): ?string {
            $value = trim((string)$request->input($name, ''));
            if ($value === '') {
                return null;
            }

            return $maxLength ? mb_substr($value, 0, $maxLength) : $value;
        };

        $int = function (string $name, int $min, int $max) use ($text): ?int {
            $value = $text($name);
            if ($value === null || !preg_match('~\d+~', $value, $matches)) {
                return null;
            }
            $number = (int)$matches[0];

            return ($number >= $min && $number <= $max) ? $number : null;
        };

        $imdbRating = $text('imdb-rating');
        if ($imdbRating !== null) {
            $imdbRating = preg_match('~\d+(?:[.,]\d+)?~', $imdbRating, $matches) ? (float)str_replace(',', '.', $matches[0]) : null;
            if ($imdbRating !== null && ($imdbRating < 0 || $imdbRating > 10)) {
                $imdbRating = null;
            }
        }

        $kind = $text('film-kind');
        $status = $text('film-status');

        $attributes = [
            'original_title' => $text('film-original-title', 255),
            'kind' => in_array($kind, self::KINDS, true) ? $kind : null,
            // сезоны — только у сериалов
            'seasons' => ($kind !== null && mb_stripos($kind, 'сериал') !== false) ? $int('film-seasons', 1, 999) : null,
            'status' => in_array($status, self::STATUSES, true) ? $status : null,
            'year' => $int('film-year', 1870, 2100),
            'countries' => $text('film-country', 255),
            'genres' => $text('film-genres', 255),
            'directors' => $text('film-director', 255),
            'actors' => $text('film-actors'),
            'duration' => $text('film-duration', 50),
            'imdb_rating' => $imdbRating,
            // select "Моя оценка": value1..value10, value0 — не выбрано
            'my_rating' => $int('my-assessment', 1, 10),
            'mpaa_rating' => $text('film-rating-mpaa', 20),
            'description' => $text('film-description'),
            'vibes' => self::chipsFromRequest($request, 'film-vibes'),
            'pros' => self::chipsFromRequest($request, 'film-pros'),
            'cons' => self::chipsFromRequest($request, 'film-cons'),
        ];

        $filled = array_filter($attributes, function ($value) {
            return $value !== null;
        });

        return empty($filled) ? null : $attributes;
    }

    /**
     * Выбранные чипы: скрытый инпут с JSON-массивом строк (его ведёт public/admin/film-fields.js)
     *
     * @param Request $request
     * @param string $name
     * @return array|null
     */
    private static function chipsFromRequest(Request $request, string $name): ?array
    {
        $decoded = json_decode((string)$request->input($name, ''), true);
        if (!is_array($decoded)) {
            return null;
        }

        $items = [];
        foreach ($decoded as $item) {
            if (is_string($item) && ($item = trim($item)) !== '' && !isset($items[mb_strtolower($item)])) {
                $items[mb_strtolower($item)] = mb_substr($item, 0, 100);
            }
        }

        return empty($items) ? null : array_slice(array_values($items), 0, 50);
    }

    /**
     * Сохраняет/обновляет данные фильма поста; если все поля пустые — удаляет запись
     *
     * @param Post $post
     * @param array|null $attributes - результат attributesFromRequest()
     * @return void
     */
    public static function saveForPost(Post $post, ?array $attributes): void
    {
        if ($attributes === null) {
            self::where('post_id', $post->id)->delete();
            return;
        }

        self::updateOrCreate(['post_id' => $post->id], $attributes);
    }

    /**
     * Чипы для формы: готовые варианты (по группам) + свои сохранённые (которых нет среди готовых), с отметкой выбранных
     *
     * @param string $kind - vibes|pros|cons
     * @param PostFilm|null $film
     * @return array [
     *     'groups' => [название группы ('' — без заголовка) => [['text' => ..., 'emoji' => ..., 'active' => bool], ...]],
     *     'custom' => [['text' => ..., 'emoji' => '', 'active' => true], ...],
     * ]
     */
    public static function chipOptions(string $kind, ?PostFilm $film = null): array
    {
        $groups = [
            'vibes' => self::VIBE_GROUPS,
            'pros' => ['' => array_fill_keys(self::PRESET_PROS, '')],
            'cons' => ['' => array_fill_keys(self::PRESET_CONS, '')],
        ][$kind];
        $selected = $film ? ($film->{$kind} ?? []) : [];
        $selectedLower = array_map('mb_strtolower', $selected);

        $result = ['groups' => [], 'custom' => []];
        $presetsLower = [];
        foreach ($groups as $label => $items) {
            foreach ($items as $text => $emoji) {
                $presetsLower[] = mb_strtolower($text);
                $result['groups'][$label][] = [
                    'text' => $text,
                    'emoji' => $emoji,
                    'active' => in_array(mb_strtolower($text), $selectedLower, true),
                ];
            }
        }

        foreach ($selected as $item) {
            if (!in_array(mb_strtolower($item), $presetsLower, true)) {
                $result['custom'][] = ['text' => $item, 'emoji' => '', 'active' => true];
            }
        }

        return $result;
    }

    /**
     * Эмодзи вайба для карточки поста ('' — свой вайб без эмодзи)
     *
     * @param string $vibe
     * @return string
     */
    public static function vibeEmoji(string $vibe): string
    {
        $vibe = mb_strtolower($vibe);
        foreach (self::VIBE_GROUPS as $items) {
            foreach ($items as $text => $emoji) {
                if (mb_strtolower($text) === $vibe) {
                    return $emoji;
                }
            }
        }

        return '';
    }
}
