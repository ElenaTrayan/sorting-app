<?php

namespace App\Models;

use App\Components\CollectionColors;
use App\Components\ImagePalette;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

/**
 * Class Post
 * @package App\Models
 */
class Post extends Model
{
    // корзина: delete() только помечает пост удалённым (deleted_at) — он пропадает из всех выборок, но строка
    // и файлы на месте; вернуть или удалить навсегда — App\Components\PostTrash
    use SoftDeletes;

    protected $table = 'posts';

    protected $imagePath = '/uploads/posts/';

    // типы поста (колонка type). Первые три определяются по содержимому и пересчитываются при каждом
    // сохранении (см. booted()): у поста с картинкой появился текст — он стал "Текст + Изображение".
    // Фильм и рецепт выбирают вручную (форма создания, переключатель типа на странице редактирования)
    public const TYPE_TEXT_PICTURE = 'text_picture';
    public const TYPE_PICTURE = 'picture';
    public const TYPE_TEXT = 'text';
    public const TYPE_FILM = 'film';
    public const TYPE_RECIPE = 'recipe';
    // одежда, обувь, аксессуары — такой пин можно добавить в "Мой гардероб" (wardrobe_items.post_id)
    public const TYPE_CLOTHES = 'clothes';
    // новость — своих полей нет, главное у неё — дата (event_date, она есть у пина любого типа)
    public const TYPE_NEWS = 'news';

    public const CONTENT_TYPES = [self::TYPE_TEXT_PICTURE, self::TYPE_PICTURE, self::TYPE_TEXT];
    // выбираются вручную — по содержимому не пересчитываются
    public const MANUAL_TYPES = [self::TYPE_FILM, self::TYPE_RECIPE, self::TYPE_CLOTHES, self::TYPE_NEWS];

    public const TYPE_LABELS = [
        self::TYPE_TEXT_PICTURE => 'Текст + Изображение',
        self::TYPE_PICTURE => 'Изображение',
        self::TYPE_TEXT => 'Текст',
        self::TYPE_FILM => 'Фильм / Сериал',
        self::TYPE_RECIPE => 'Рецепт',
        self::TYPE_CLOTHES => 'Одежда',
        self::TYPE_NEWS => 'Новость',
    ];

    // Дата пина (event_date) — когда это было: день новости, день съёмки фото и т.п. Если известны только месяц
    // или год, в event_date — первый день месяца/года, а точность — в event_date_precision
    public const DATE_DAY = 'day';
    public const DATE_MONTH = 'month';
    public const DATE_YEAR = 'year';

    // месяцы для разбора даты, введённой словами ("12 марта 2024", "март 2024") — по началу слова
    private const MONTH_STEMS = ['янв', 'фев', 'мар', 'апр', 'ма', 'июн', 'июл', 'авг', 'сен', 'окт', 'ноя', 'дек'];
    private const MONTHS_NOMINATIVE = ['январь', 'февраль', 'март', 'апрель', 'май', 'июнь', 'июль', 'август', 'сентябрь', 'октябрь', 'ноябрь', 'декабрь'];
    private const MONTHS_GENITIVE = ['января', 'февраля', 'марта', 'апреля', 'мая', 'июня', 'июля', 'августа', 'сентября', 'октября', 'ноября', 'декабря'];

    // материалы фильма — посты, прикреплённые к пину "Фильм / Сериал" (film_post_id), и их разделы в блоке
    // "Материалы" на странице фильма. Кадр/промо выбирают при прикреплении (film_material_kind),
    // "Видео" не хранится: пост с видео всегда попадает туда (см. filmMaterialSection())
    public const MATERIAL_STILL = 'still';
    public const MATERIAL_PROMO = 'promo';
    public const MATERIAL_VIDEO = 'video';

    public const MATERIAL_KIND_LABELS = [
        self::MATERIAL_STILL => 'Кадр из фильма',
        self::MATERIAL_PROMO => 'Промо',
    ];

    public const MATERIAL_SECTION_LABELS = [
        self::MATERIAL_STILL => 'Кадры',
        self::MATERIAL_PROMO => 'Промо',
        self::MATERIAL_VIDEO => 'Видео',
    ];

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'id',
        'title',
        'alias',
        'user_id',
        'category_id',
        'status',
        'is_used',
        'content',
        'small_image',
        'medium_image',
        'large_image',
        'image_alt',
        'video',
    ];

    protected $casts = [
        // [{"hex": "#D8C3A5", "share": 0.34}, ..., {"hex": "#FAFAFA", "share": 0.5, "background": true}] —
        // фон (если нашёлся) последним; см. booted() и App\Components\ImagePalette
        'palette' => 'array',
        // палитру поправили вручную (PostController::updatePalette) — см. booted()
        'palette_edited' => 'boolean',
        // дата пина, см. DATE_* и setEventDateFromInput()
        'event_date' => 'date',
    ];

    protected static function booted()
    {
        // тип по содержимому — пересчитываем при любом сохранении (текст, картинки, видео меняются в разных
        // местах: форма редактирования, AJAX-панели изображений/видео, слияние дубликатов, парсеры).
        // Фильм/рецепт не трогаем — их выбрали вручную
        static::saving(function (Post $post) {
            if (empty($post->type) || in_array($post->type, self::CONTENT_TYPES, true)) {
                $post->type = $post->detectContentType();
            }
        });

        // палитра обложки — пересчитываем при любом изменении картинок поста (создание, добавление/замена/
        // удаление/переименование на странице редактирования, парсеры): все эти места сохраняют пост через save().
        // Поправленную вручную палитру не трогаем, пока обложка та же (добавили/удалили другие картинки);
        // сменилась обложка — ручные цвета относятся к другой картинке, считаем заново
        static::saving(function (Post $post) {
            if (!$post->isDirty(['original_image', 'medium_image', 'small_image'])) {
                return;
            }

            if ($post->palette_edited && $post->exists) {
                // пост с картинками, какими они были до этого сохранения
                $before = (new static())->setRawAttributes($post->getRawOriginal());
                if ($before->coverImagePath() === $post->coverImagePath()) {
                    return;
                }
            }

            $post->palette = $post->computePalette() ?: null;
            $post->palette_edited = false;
        });

        // цвета коллекции во вкладке "Поиск по цвету" (кружки с числом постов) посчитаны по палитрам —
        // палитра изменилась или пост удалён, значит, пересчитать при следующем открытии
        static::saved(function (Post $post) {
            if ($post->wasChanged('palette') || ($post->wasRecentlyCreated && $post->palette)) {
                CollectionColors::forget((int)$post->user_id);
            }
        });
        // "deleted" приходит и при переносе в корзину, и при удалении навсегда: поста в коллекции нет в обоих случаях
        static::deleted(function (Post $post) {
            if ($post->palette) {
                CollectionColors::forget((int)$post->user_id);
            }
        });
        static::restored(function (Post $post) {
            if ($post->palette) {
                CollectionColors::forget((int)$post->user_id);
            }
        });

        // Пока пост в корзине, его связи не трогаем — вернётся таким же: фильм — со своими материалами,
        // пост группы — на своё место в группе. Рвём их только при удалении навсегда:
        static::deleted(function (Post $post) {
            if (!$post->isForceDeleting()) {
                return;
            }

            // удалили фильм — его материалы остаются обычными постами, просто открепляются
            static::withTrashed()
                ->where('film_post_id', $post->id)
                ->toBase()
                ->update(['film_post_id' => null, 'film_material_kind' => null]);

            // group_id — это id одного из постов группы: удалили именно его — остальным нужен новый ключ
            if ($post->group_id) {
                static::rekeyGroupWithout((int)$post->group_id, (int)$post->id, (int)$post->user_id);
            }
        });
    }

    /**
     * Вид группы, в которую можно положить этот пост. Группы бывают двух видов, между собой не смешиваются:
     *  - изображения (TYPE_PICTURE) — посты с картинкой или видео: "Изображение", "Текст + Изображение" и
     *    "Новость" с картинкой. Текст у такой группы общий (как название): у каждого её поста в content лежит
     *    один и тот же текст группы, правят его на странице группы (PostController::updateGroup);
     *  - тексты (TYPE_TEXT) — посты "Текст", у каждого свой текст.
     * Фильм, рецепт, одежда и новость без картинки не группируются
     *
     * @return string|null TYPE_PICTURE, TYPE_TEXT или null — группировать нельзя
     */
    public function groupKind(): ?string
    {
        if ($this->type === self::TYPE_TEXT) {
            return self::TYPE_TEXT;
        }
        if (in_array($this->type, [self::TYPE_PICTURE, self::TYPE_TEXT_PICTURE], true)) {
            return self::TYPE_PICTURE;
        }
        // новость уже в группе — значит, с картинкой её туда и пустили; group_id проверяем первым: местами посты
        // загружают без колонок картинок (только id, type, group_id), и detectContentType() их бы не увидел
        if ($this->type === self::TYPE_NEWS && ($this->group_id || $this->detectContentType() !== self::TYPE_TEXT)) {
            return self::TYPE_PICTURE;
        }

        return null;
    }

    // cookie галочки "Свернуть группы" в ленте (posts/index.blade.php, admin.js): "1" — галочка снята, посты групп
    // идут отдельными карточками. Ставится из JS, поэтому не шифруется (EncryptCookies::$except)
    public const FEED_UNGROUPED_COOKIE = 'posts_ungrouped';

    /**
     * Показывать ли в ленте и поиске посты групп отдельными карточками, а не одной карточкой-стопкой
     * (снята галочка "Свернуть группы")
     *
     * @return bool
     */
    public static function feedGroupsExpanded(): bool
    {
        return request()->cookie(self::FEED_UNGROUPED_COOKIE) === '1';
    }

    /**
     * Лента, в которой группа — одна карточка: из постов группы остаётся только первый (по sort_order, как на
     * странице группы), посты без группы — все. Остальные условия запроса (тип...) проверяются
     * у этого первого поста: название и категория у группы общие. Хештеги у постов группы бывают и собственные —
     * поиск по хештегам находит группу по любому её посту сам (SearchController). Сколько постов в группе —
     * fillGroupCounts()
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param int $userId чьи посты в запросе
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeCollapsedGroups($query, int $userId)
    {
        $headIds = static::groupHeadIds($userId);

        return $query->where(function ($q) use ($headIds) {
            $q->whereNull('posts.group_id')->orWhereIn('posts.id', $headIds);
        });
    }

    /**
     * Первые посты групп пользователя (по sort_order, как на странице группы) — по одному на группу.
     * Отдельным запросом, а не подзапросом в scopeCollapsedGroups(): индекса по group_id нет, и подзапрос
     * на каждую строку добавлял ленте заметные доли секунды; постов в группах немного
     *
     * @param int $userId
     * @return int[]
     */
    public static function groupHeadIds(int $userId): array
    {
        return array_values(static::groupHeadIdsByGroup($userId));
    }

    /**
     * То же, что groupHeadIds(), с ключами — id групп
     *
     * @param int $userId
     * @return int[] [group_id => id первого поста]
     */
    public static function groupHeadIdsByGroup(int $userId): array
    {
        return static::where('user_id', $userId)
            ->whereNotNull('group_id')
            ->orderBy('sort_order')
            ->orderBy('id')
            ->get(['id', 'group_id'])
            ->unique('group_id')
            ->pluck('id', 'group_id')
            ->all();
    }

    /**
     * Общие хештеги группы — те, что есть у каждого её поста; остальные хештеги поста — его собственные.
     * Отдельно общие нигде не хранятся: у каждого поста группы в hashtag_post лежат и общие, и собственные
     *
     * @param iterable $posts все посты группы, с загруженными hashtags
     * @return int[]
     */
    public static function commonHashtagIds(iterable $posts): array
    {
        $common = null;
        foreach ($posts as $post) {
            $ids = $post->hashtags->pluck('id')->map(fn ($id) => (int)$id)->all();
            $common = $common === null ? $ids : array_values(array_intersect($common, $ids));
        }

        return $common ?? [];
    }

    /**
     * Хештеги постов группы: общие становятся $commonIds, собственные у каждого поста — из $ownIds
     * (нет там поста — его собственные остаются прежними)
     *
     * @param iterable $posts все посты группы, с загруженными hashtags
     * @param int[] $commonIds
     * @param array $ownIds [post_id => int[]]
     * @return void
     */
    public static function syncGroupHashtags(iterable $posts, array $commonIds, array $ownIds = []): void
    {
        $oldCommon = static::commonHashtagIds($posts);
        foreach ($posts as $post) {
            $own = array_key_exists($post->id, $ownIds)
                ? $ownIds[$post->id]
                : array_diff($post->hashtags->pluck('id')->all(), $oldCommon);
            $post->hashtags()->sync(array_values(array_unique(array_map('intval', array_merge($commonIds, $own)))));
        }
    }

    /**
     * Карточкам-стопкам (после fillGroupCounts()) оставляет из загруженных hashtags только общие хештеги группы:
     * у первого поста бывают и собственные, а стопка — это вся группа. Только для показа
     *
     * @param iterable $posts
     * @return void
     */
    public static function fillGroupCommonHashtags(iterable $posts): void
    {
        $stacks = [];
        foreach ($posts as $post) {
            if ($post->group_id && isset($post->group_count) && $post->relationLoaded('hashtags')) {
                $stacks[] = $post;
            }
        }
        $common = static::commonHashtagIdsByGroup(array_map(fn ($post) => $post->group_id, $stacks));

        foreach ($stacks as $post) {
            $commonIds = $common[$post->group_id] ?? [];
            $post->setRelation('hashtags', $post->hashtags->filter(fn ($hashtag) => in_array((int)$hashtag->id, $commonIds, true))->values());
        }
    }

    /**
     * Отдельным карточкам постов групп в ленте (снята галочка "Свернуть группы") проставляет own_hashtag_ids —
     * какие из их хештегов собственные, не общие для группы: на карточке они другого цвета
     * (parts/post_items.blade.php). Только для показа
     *
     * @param iterable $posts
     * @return void
     */
    public static function fillGroupOwnHashtagIds(iterable $posts): void
    {
        $groupPosts = [];
        foreach ($posts as $post) {
            if ($post->group_id && !isset($post->group_count) && $post->relationLoaded('hashtags')) {
                $groupPosts[] = $post;
            }
        }
        $common = static::commonHashtagIdsByGroup(array_map(fn ($post) => $post->group_id, $groupPosts));

        foreach ($groupPosts as $post) {
            $commonIds = $common[$post->group_id] ?? [];
            $post->own_hashtag_ids = $post->hashtags->pluck('id')->map(fn ($id) => (int)$id)->diff($commonIds)->values()->all();
        }
    }

    /**
     * Общие хештеги сразу нескольких групп пользователя — одним запросом: сколько постов группы помечено
     * каждым хештегом, общий — тот, что у всех её постов (см. commonHashtagIds())
     *
     * @param int[] $groupIds
     * @return array [group_id => int[]]
     */
    private static function commonHashtagIdsByGroup(array $groupIds): array
    {
        $groupIds = array_values(array_unique($groupIds));
        if (!$groupIds) {
            return [];
        }

        $sizes = static::whereIn('group_id', $groupIds)
            ->where('user_id', auth()->id())
            ->selectRaw('group_id, count(*) as total')
            ->groupBy('group_id')
            ->pluck('total', 'group_id');

        $rows = DB::table('hashtag_post')
            ->join('posts', 'posts.id', '=', 'hashtag_post.post_id')
            ->whereIn('posts.group_id', $groupIds)
            ->where('posts.user_id', auth()->id())
            ->whereNull('posts.deleted_at')
            ->selectRaw('posts.group_id, hashtag_post.hashtag_id, count(distinct posts.id) as total')
            ->groupBy('posts.group_id', 'hashtag_post.hashtag_id')
            ->get();

        $common = [];
        foreach ($rows as $row) {
            if ((int)$row->total >= (int)($sizes[$row->group_id] ?? PHP_INT_MAX)) {
                $common[$row->group_id][] = (int)$row->hashtag_id;
            }
        }

        return $common;
    }

    /**
     * Проставляет постам из групп group_count — число постов в их группе (карточка-стопка в ленте,
     * parts/post_items.blade.php). Только для показа: такие модели не сохраняют
     *
     * @param iterable $posts
     * @return void
     */
    public static function fillGroupCounts(iterable $posts): void
    {
        // перебором, а не collect($posts): сюда приходит и пагинатор, а он превращается в массив со своими полями
        $groupIds = [];
        foreach ($posts as $post) {
            if ($post->group_id) {
                $groupIds[$post->group_id] = $post->group_id;
            }
        }
        if (!$groupIds) {
            return;
        }

        $counts = static::whereIn('group_id', $groupIds)
            ->where('user_id', auth()->id())
            ->selectRaw('group_id, count(*) as total')
            ->groupBy('group_id')
            ->pluck('total', 'group_id');

        foreach ($posts as $post) {
            if ($post->group_id) {
                $post->group_count = (int)($counts[$post->group_id] ?? 1);
            }
        }
    }

    /**
     * Ключ группы после того, как пост $postId её покинул (убран из группы или удалён): прежний, пока сам пост
     * не был ключом; иначе — id первого из оставшихся постов. null — в группе никого не осталось
     *
     * @param int $groupId
     * @param int $postId
     * @param int $userId
     * @return int|null
     */
    public static function groupIdWithout(int $groupId, int $postId, int $userId): ?int
    {
        $firstLeft = static::where('group_id', $groupId)
            ->where('user_id', $userId)
            ->where('id', '!=', $postId)
            ->orderBy('sort_order')
            ->orderBy('id')
            ->value('id');

        if ($firstLeft === null) {
            return null;
        }

        return $groupId === $postId ? (int)$firstLeft : $groupId;
    }

    /**
     * Пост $postId покинул группу — если он был её ключом, переводит оставшиеся посты на новый (groupIdWithout())
     *
     * @param int $groupId
     * @param int $postId
     * @param int $userId
     * @return int|null ключ группы после этого, null — группы больше нет
     */
    public static function rekeyGroupWithout(int $groupId, int $postId, int $userId): ?int
    {
        $newGroupId = static::groupIdWithout($groupId, $postId, $userId);

        if ($newGroupId !== $groupId) {
            // Вместе с постами группы, лежащими в корзине: иначе они остались бы со старым ключом — id поста,
            // который из группы ушёл, — и при восстановлении попали бы в группу, которую он начнёт заново.
            // Живых постов не осталось (null) — группы больше нет, посты из корзины вернутся обычными.
            // toBase(): не трогаем updated_at — сами посты не менялись
            static::withTrashed()
                ->where('group_id', $groupId)
                ->where('user_id', $userId)
                ->where('id', '!=', $postId)
                ->toBase()
                ->update($newGroupId === null ? ['group_id' => null, 'sort_order' => null] : ['group_id' => $newGroupId]);
        }

        return $newGroupId;
    }

    /**
     * Раздел блока "Материалы" на странице фильма, в который попадает этот пост
     *
     * @return string
     */
    public function filmMaterialSection(): string
    {
        if (!empty($this->attributes['video'] ?? null)) {
            return self::MATERIAL_VIDEO;
        }

        return $this->film_material_kind === self::MATERIAL_PROMO ? self::MATERIAL_PROMO : self::MATERIAL_STILL;
    }

    /**
     * Тип поста по содержимому: нет картинки/видео — "Текст" (даже если текста ещё нет, например только
     * название), есть медиа и текст — "Текст + Изображение", только медиа — "Изображение"
     *
     * @return string
     */
    public function detectContentType(): string
    {
        $hasMedia = $this->coverImagePath() !== null || !empty($this->attributes['video'] ?? null);
        if (!$hasMedia) {
            return self::TYPE_TEXT;
        }

        // пустой абзац из TinyMCE ("<p>&nbsp;</p>") — тоже не текст
        $text = html_entity_decode(strip_tags((string)($this->attributes['content'] ?? '')), ENT_QUOTES | ENT_HTML5, 'UTF-8');
        $hasText = preg_replace('/[\s\x{00A0}]+/u', '', $text) !== '';

        return $hasText ? self::TYPE_TEXT_PICTURE : self::TYPE_PICTURE;
    }

    /**
     * Название типа для интерфейса
     *
     * @return string
     */
    public function typeLabel(): string
    {
        return self::TYPE_LABELS[$this->type] ?? self::TYPE_LABELS[self::TYPE_TEXT_PICTURE];
    }

    /**
     * Разбирает дату пина, как её вводят в поле "Дата": "12.03.2024", "2024-03-12", "12 марта 2024" — день;
     * "03.2024", "2024-03", "март 2024" — месяц; "2024" — год. Точность определяется по тому, что введено
     *
     * @param string|null $input
     * @return array|null ['date' => 'Y-m-d', 'precision' => DATE_*]; null — пусто или не дата
     */
    public static function parseEventDate(?string $input): ?array
    {
        $text = mb_strtolower(trim(preg_replace('/\s+/u', ' ', (string)$input)));
        $text = rtrim(preg_replace('/\s*(г\.?|года?)$/u', '', $text));
        if ($text === '') {
            return null;
        }

        $make = function (int $year, int $month, int $day, string $precision) {
            if ($year < 1000 || $year > 2999 || !checkdate($month, $day, $year)) {
                return null;
            }
            return ['date' => sprintf('%04d-%02d-%02d', $year, $month, $day), 'precision' => $precision];
        };
        $monthByWord = function (string $word): ?int {
            foreach (self::MONTH_STEMS as $index => $stem) {
                if (mb_strpos($word, $stem) === 0) {
                    return $index + 1;
                }
            }
            return null;
        };

        if (preg_match('/^(\d{1,2})[.\/-](\d{1,2})[.\/-](\d{4})$/u', $text, $m)) {
            return $make((int)$m[3], (int)$m[2], (int)$m[1], self::DATE_DAY);
        }
        if (preg_match('/^(\d{4})-(\d{1,2})-(\d{1,2})$/u', $text, $m)) {
            return $make((int)$m[1], (int)$m[2], (int)$m[3], self::DATE_DAY);
        }
        if (preg_match('/^(\d{1,2})[.\/-](\d{4})$/u', $text, $m)) {
            return $make((int)$m[2], (int)$m[1], 1, self::DATE_MONTH);
        }
        if (preg_match('/^(\d{4})-(\d{1,2})$/u', $text, $m)) {
            return $make((int)$m[1], (int)$m[2], 1, self::DATE_MONTH);
        }
        if (preg_match('/^(\d{4})$/u', $text, $m)) {
            return $make((int)$m[1], 1, 1, self::DATE_YEAR);
        }
        if (preg_match('/^(\d{1,2}) (\p{L}+) (\d{4})$/u', $text, $m) && ($month = $monthByWord($m[2]))) {
            return $make((int)$m[3], $month, (int)$m[1], self::DATE_DAY);
        }
        if (preg_match('/^(\p{L}+) (\d{4})$/u', $text, $m) && ($month = $monthByWord($m[1]))) {
            return $make((int)$m[2], $month, 1, self::DATE_MONTH);
        }

        return null;
    }

    /**
     * Ставит дату пина из поля "Дата" (не сохраняет). Пустое поле — даты нет
     *
     * @param string|null $input
     * @return bool false — введено, но это не дата (дату не трогаем)
     */
    public function setEventDateFromInput(?string $input): bool
    {
        if (trim((string)$input) === '') {
            $this->event_date = null;
            $this->event_date_precision = null;
            return true;
        }

        $parsed = static::parseEventDate($input);
        if ($parsed === null) {
            return false;
        }

        $this->event_date = $parsed['date'];
        $this->event_date_precision = $parsed['precision'];
        return true;
    }

    /**
     * Дата пина для карточки и страницы поста: "12 марта 2024", "март 2024", "2024"; null — даты нет
     *
     * @return string|null
     */
    public function eventDateLabel(): ?string
    {
        if (!$this->event_date) {
            return null;
        }

        $month = (int)$this->event_date->format('n') - 1;
        switch ($this->event_date_precision) {
            case self::DATE_YEAR:
                return $this->event_date->format('Y');
            case self::DATE_MONTH:
                return self::MONTHS_NOMINATIVE[$month] . ' ' . $this->event_date->format('Y');
            default:
                return $this->event_date->format('j') . ' ' . self::MONTHS_GENITIVE[$month] . ' ' . $this->event_date->format('Y');
        }
    }

    /**
     * Дата пина для поля "Дата" на формах: "12.03.2024", "03.2024", "2024"; '' — даты нет
     *
     * @return string
     */
    public function eventDateInput(): string
    {
        if (!$this->event_date) {
            return '';
        }

        switch ($this->event_date_precision) {
            case self::DATE_YEAR:
                return $this->event_date->format('Y');
            case self::DATE_MONTH:
                return $this->event_date->format('m.Y');
            default:
                return $this->event_date->format('d.m.Y');
        }
    }

    /**
     * Посты, дата которых попадает в период [$from, $to] (включительно). Дата с точностью до месяца или года —
     * это весь месяц/год: "март 2024" попадает в период "10–20 марта 2024"
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param string|null $from 'Y-m-d'; null — без начала
     * @param string|null $to 'Y-m-d'; null — без конца
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeEventDateBetween($query, ?string $from, ?string $to)
    {
        $query->whereNotNull('posts.event_date');
        if ($to) {
            $query->where('posts.event_date', '<=', $to);
        }
        if ($from) {
            // конец периода даты (не включительно) — позже начала искомого
            $query->whereRaw("(CASE posts.event_date_precision
                    WHEN 'year' THEN DATE_ADD(posts.event_date, INTERVAL 1 YEAR)
                    WHEN 'month' THEN DATE_ADD(posts.event_date, INTERVAL 1 MONTH)
                    ELSE DATE_ADD(posts.event_date, INTERVAL 1 DAY) END) > ?", [$from]);
        }

        return $query;
    }

    /**
     * "В этот день": посты, дата которых (с точностью до дня) — тот же день и месяц в любом году
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param int $month
     * @param int $day
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeEventDateOnDay($query, int $month, int $day)
    {
        return $query->where('posts.event_date_precision', self::DATE_DAY)
            ->whereMonth('posts.event_date', $month)
            ->whereDay('posts.event_date', $day);
    }

    /**
     * Путь к обложке поста (первая картинка, самый маленький из сохранённых размеров) — относительно
     * диска local, например "/uploads/posts/1/foo_350_525.jpg"; null, если картинок нет (видео, текст)
     *
     * @return string|null
     */
    public function coverImagePath(): ?string
    {
        foreach (['small_image', 'medium_image', 'original_image'] as $column) {
            $value = $this->attributes[$column] ?? null;
            if (empty($value)) {
                continue;
            }

            $decoded = json_decode($value, true);
            if (!is_array($decoded)) {
                continue;
            }

            // одна картинка — {path: ...}, карусель — {name: {path: ...}, ...}
            $first = isset($decoded['path']) ? $decoded : reset($decoded);
            if (is_array($first) && !empty($first['path'])) {
                return $first['path'];
            }
        }

        return null;
    }

    /**
     * Палитра обложки: [['hex' => '#D8C3A5', 'share' => 0.34], ...]; [] — нет картинки или её не прочитать
     *
     * @return array
     */
    public function computePalette(): array
    {
        $path = $this->coverImagePath();
        if ($path === null) {
            return [];
        }

        return ImagePalette::fromFile(Storage::disk('local')->path($path));
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     *
     * устанавливаем связь с таблицей posts_categories (модель PostsCategory)
     * Один ко многим по полю category_id (табл. posts) = id (posts_categories)
     */
    public function category()
    {
        return $this->belongsTo(PostsCategory::class, 'category_id');
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     *
     * устанавливаем связь с таблицей users (модель User)
     * Один ко многим по полю user_id (табл. posts) = id (users)
     */
    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsToMany
     *
     * Хэштеги поста
     */
    public function hashtags()
    {
        return $this->belongsToMany(Hashtag::class, 'hashtag_post', 'post_id', 'hashtag_id');
    }

    /**
     * Вещи "Моего гардероба", картинка которых взята из этого поста (пины типа "Одежда")
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function wardrobeItems()
    {
        return $this->hasMany(WardrobeItem::class, 'post_id');
    }

    /**
     * Данные вещи у пина типа "Одежда" (бренд, тип, цвет, принт...)
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasOne
     */
    public function clothes()
    {
        return $this->hasOne(PostClothes::class, 'post_id');
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasOne
     *
     * Данные фильма/сериала (только у пинов типа "Фильм / Сериал")
     */
    public function film()
    {
        return $this->hasOne(PostFilm::class, 'post_id');
    }

    /**
     * Пин "Фильм / Сериал", к которому прикреплён этот пост (кадр, промо, видео)
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function filmPost()
    {
        return $this->belongsTo(self::class, 'film_post_id');
    }

    /**
     * Материалы фильма — посты, прикреплённые к этому пину "Фильм / Сериал"
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function filmMaterials()
    {
        return $this->hasMany(self::class, 'film_post_id');
    }

    public function uploadImage()
    {
        $path = $request->file('avatar')->storeAs(
            'avatars', $request->user()->id
        );
    }

    /**
     * Декодирует поле video ({name, extension, path}) в массив.
     *
     * @return array|null
     */
    public function getVideoDataAttribute(): ?array
    {
        if (empty($this->attributes['video'])) {
            return null;
        }

        $decoded = json_decode($this->attributes['video'], true);

        return is_array($decoded) ? $decoded : null;
    }

    /**
     * Готовый URL видео для <video src="">, либо null, если видео нет.
     *
     * @return string|null
     */
    public function getVideoUrlAttribute(): ?string
    {
        $data = $this->video_data;

        return !empty($data['path']) ? '/storage' . $data['path'] : null;
    }

}
