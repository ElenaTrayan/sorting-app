<?php

namespace App\Models;

use App\Components\CollectionColors;
use App\Components\ImagePalette;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Http\Request;
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

    public const CONTENT_TYPES = [self::TYPE_TEXT_PICTURE, self::TYPE_PICTURE, self::TYPE_TEXT];
    // выбираются вручную — по содержимому не пересчитываются
    public const MANUAL_TYPES = [self::TYPE_FILM, self::TYPE_RECIPE, self::TYPE_CLOTHES];

    public const TYPE_LABELS = [
        self::TYPE_TEXT_PICTURE => 'Текст + Изображение',
        self::TYPE_PICTURE => 'Изображение',
        self::TYPE_TEXT => 'Текст',
        self::TYPE_FILM => 'Фильм / Сериал',
        self::TYPE_RECIPE => 'Рецепт',
        self::TYPE_CLOTHES => 'Одежда',
    ];

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
     * Вид группы, в которую можно положить этот пост. Группируются только посты "Изображение" и только посты
     * "Текст" (между собой не смешиваются): у группы общие название, категория и хештеги, у текстов — ещё
     * и свой текст у каждого поста. Пост с текстом и картинкой, фильм, рецепт, одежда — не группируются
     *
     * @return string|null TYPE_PICTURE, TYPE_TEXT или null — группировать нельзя
     */
    public function groupKind(): ?string
    {
        return in_array($this->type, [self::TYPE_PICTURE, self::TYPE_TEXT], true) ? $this->type : null;
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
     * странице группы), посты без группы — все. Остальные условия запроса (хештеги, тип...) проверяются
     * у этого первого поста: название, категория и хештеги у группы общие. Сколько постов в группе —
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
        return static::where('user_id', $userId)
            ->whereNotNull('group_id')
            ->orderBy('sort_order')
            ->orderBy('id')
            ->get(['id', 'group_id'])
            ->unique('group_id')
            ->pluck('id')
            ->all();
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
