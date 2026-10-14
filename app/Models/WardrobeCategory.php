<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\QueryException;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

/**
 * Категория одежды "Моего гардероба" — общий для всех пользователей список (ведёт админ: админка →
 * Категории одежды). У вещи в wardrobe_items.category лежит slug категории; slug при переименовании не меняется.
 * kinds — типы вещей категории: подсказки поля "Тип вещи" у пина "Одежда", по ним же определяется категория
 * вещи при импорте пина в гардероб (PostClothes::wardrobeCategory()).
 * Категория "Другое" (FALLBACK) не удаляется: в неё попадают вещи удалённых категорий и вещи с неизвестным типом.
 */
class WardrobeCategory extends Model
{
    protected $table = 'wardrobe_categories';

    public const FALLBACK = 'other';

    protected $fillable = [
        'slug',
        'name',
        'kinds',
        'sort_order',
    ];

    protected $casts = [
        'kinds' => 'array',
        'sort_order' => 'integer',
    ];

    // список читается на каждой странице гардероба по несколько раз — держим его до конца запроса
    private static ?Collection $loaded = null;

    protected static function booted()
    {
        $forget = function () {
            self::$loaded = null;
        };
        static::saved($forget);
        static::deleted($forget);
    }

    public function isFallback(): bool
    {
        return $this->slug === self::FALLBACK;
    }

    /**
     * Все категории по порядку. Пока таблицы нет (миграция не запущена) — список по умолчанию из
     * WardrobeItem::CATEGORIES и PostClothes::KINDS.
     *
     * @return Collection
     */
    public static function ordered(): Collection
    {
        if (self::$loaded === null) {
            try {
                self::$loaded = static::orderBy('sort_order')->orderBy('id')->get();
            } catch (QueryException $e) {
                self::$loaded = collect();
            }
            if (self::$loaded->isEmpty()) {
                self::$loaded = collect(WardrobeItem::CATEGORIES)->map(function ($name, $slug) {
                    return new static(['slug' => $slug, 'name' => $name, 'kinds' => PostClothes::KINDS[$slug] ?? null]);
                })->values();
            }
        }

        return self::$loaded;
    }

    /**
     * slug => название, по порядку — для списков в формах и фильтрах
     *
     * @return array
     */
    public static function options(): array
    {
        return self::ordered()->pluck('name', 'slug')->all();
    }

    /**
     * slug => типы вещей (только категории, у которых они есть)
     *
     * @return array
     */
    public static function kindsBySlug(): array
    {
        return self::ordered()->filter(function (WardrobeCategory $category) {
            return !empty($category->kinds);
        })->pluck('kinds', 'slug')->all();
    }

    public static function label(?string $slug): string
    {
        $options = self::options();

        return $options[$slug] ?? $options[self::FALLBACK] ?? 'Другое';
    }

    /**
     * Типы вещей из текста формы админки: по одному в строке или через запятую, без пустых и повторов
     *
     * @param string|null $text
     * @return array|null
     */
    public static function kindsFromText(?string $text): ?array
    {
        $kinds = [];
        foreach (preg_split('~[\r\n,;]+~u', (string)$text) as $kind) {
            $kind = mb_substr(trim($kind), 0, 50);
            if ($kind !== '' && !isset($kinds[mb_strtolower($kind)])) {
                $kinds[mb_strtolower($kind)] = $kind;
            }
        }

        return $kinds ? array_values($kinds) : null;
    }

    /**
     * Slug новой категории: латиницей из названия ("Купальники" → "kupalniki"), уникальный
     *
     * @param string $name
     * @return string
     */
    public static function makeSlug(string $name): string
    {
        $base = substr(Str::slug($name), 0, 24) ?: 'category';
        $slug = $base;
        $i = 1;
        while (static::where('slug', $slug)->exists()) {
            $slug = $base . '-' . (++$i);
        }

        return $slug;
    }
}
