<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;

/**
 * Данные вещи для пина типа "Одежда" (1:1 с posts, как PostFilm). Бренд, стили и "кому подходит" — те же
 * справочники, что у вещей "Моего гардероба", чтобы кнопка "В гардероб" переносила их в форму вещи
 * (toWardrobeForm(), Wardrobe\ItemController::fromPostImage).
 */
class PostClothes extends Model
{
    protected $table = 'post_clothes';

    // типы вещей по умолчанию — по категориям гардероба. Рабочий список — в БД (WardrobeCategory::kindsBySlug(),
    // ведёт админ): подсказки поля "Тип" (datalist, можно вписать своё), по типу вещи из пина определяется
    // категория вещи в гардеробе. Этот — запасной, пока таблицы нет.
    public const KINDS = [
        'top' => ['Майка', 'Футболка', 'Топ', 'Лонгслив', 'Рубашка', 'Блузка', 'Свитер', 'Джемпер', 'Кардиган',
            'Свитшот', 'Худи', 'Водолазка', 'Жилет', 'Пиджак', 'Жакет'],
        'bottom' => ['Джинсы', 'Брюки', 'Юбка', 'Шорты', 'Леггинсы', 'Кюлоты'],
        'dress' => ['Платье', 'Сарафан', 'Комбинезон', 'Костюм'],
        'outerwear' => ['Пальто', 'Плащ', 'Тренч', 'Куртка', 'Пуховик', 'Парка', 'Шуба', 'Бомбер', 'Косуха', 'Ветровка'],
        'shoes' => ['Кроссовки', 'Кеды', 'Туфли', 'Лоферы', 'Балетки', 'Босоножки', 'Сандалии', 'Ботинки', 'Ботильоны',
            'Сапоги', 'Мюли', 'Шлёпанцы'],
        'bag' => ['Сумка', 'Рюкзак', 'Клатч', 'Шопер', 'Поясная сумка'],
        'accessory' => ['Ремень', 'Шарф', 'Платок', 'Шапка', 'Кепка', 'Панама', 'Шляпа', 'Перчатки', 'Очки',
            'Часы', 'Зонт'],
        'jewelry' => ['Серьги', 'Кольцо', 'Колье', 'Цепочка', 'Браслет', 'Брошь'],
        'underwear' => ['Бельё', 'Пижама', 'Халат', 'Носки', 'Колготки', 'Купальник'],
        'sport' => ['Спортивный костюм', 'Велосипедки', 'Спортивный топ'],
    ];

    protected $fillable = [
        'post_id',
        'brand_id',
        'collection_year',
        'kind',
        'length',
        'seasons',
        'temp_min',
        'temp_max',
        'occasions',
        'color_id',
        'print_id',
        'material_id',
        'composition',
        'styles',
        'body_types',
        'shop_url',
    ];

    protected $casts = [
        'collection_year' => 'integer',
        'styles' => 'array',
        'body_types' => 'array',
        // когда носить — те же поля и списки, что у вещи гардероба (WardrobeOutfit::SEASONS, WardrobeItem::OCCASIONS)
        'seasons' => 'array',
        'occasions' => 'array',
        'temp_min' => 'integer',
        'temp_max' => 'integer',
    ];

    // "+5…+15 °C" — комфортная температура, как у вещей и образов; null — не указана
    public function temperatureLabel(): ?string
    {
        return WardrobeOutfit::formatTemperature($this->temp_min, $this->temp_max);
    }

    public function post()
    {
        return $this->belongsTo(Post::class, 'post_id');
    }

    public function brand()
    {
        return $this->belongsTo(WardrobeBrand::class, 'brand_id');
    }

    // цвет из справочника — тот же, что у вещей гардероба (см. WardrobeColor)
    public function color()
    {
        return $this->belongsTo(WardrobeColor::class, 'color_id');
    }

    // принт из справочника — тот же, что у вещей гардероба (см. WardrobePrint)
    public function print()
    {
        return $this->belongsTo(WardrobePrint::class, 'print_id');
    }

    // материал из справочника — тот же, что у вещей гардероба (см. WardrobeMaterial)
    public function material()
    {
        return $this->belongsTo(WardrobeMaterial::class, 'material_id');
    }

    /**
     * Категория гардероба по типу вещи ("Свитер" → top); свой тип, которого нет в подсказках, — other
     *
     * @return string
     */
    public function wardrobeCategory(): string
    {
        $kind = mb_strtolower(trim((string)$this->kind));
        foreach (WardrobeCategory::kindsBySlug() as $category => $kinds) {
            foreach ($kinds as $suggestion) {
                if (mb_strtolower($suggestion) === $kind) {
                    return $category;
                }
            }
        }

        return 'other';
    }

    /**
     * Поля формы из запроса (имена инпутов — как в parts/clothes_fields.blade.php) -> атрибуты модели.
     * Неверные значения (год "abc", чужой стиль) не валят сохранение поста, а просто не сохраняются.
     *
     * @param Request $request
     * @param int $userId - для бренда: общий или свой бренд пользователя (WardrobeBrand::resolveId)
     * @return array|null - null, если ни одно поле не заполнено
     */
    public static function attributesFromRequest(Request $request, int $userId): ?array
    {
        $text = function (string $name, int $maxLength) use ($request): ?string {
            $value = trim((string)$request->input($name, ''));

            return $value === '' ? null : mb_substr($value, 0, $maxLength);
        };

        $year = $text('clothes-year', 10);
        $year = ($year !== null && preg_match('~\d{4}~', $year, $matches) && $matches[0] >= 1900 && $matches[0] <= 2100)
            ? (int)$matches[0]
            : null;

        $keys = function (string $name, array $allowed) use ($request): ?array {
            $values = array_values(array_intersect((array)$request->input($name, []), array_keys($allowed)));

            return empty($values) ? null : $values;
        };

        // температура: целое от −60 до +60; если границы перепутаны местами — меняем
        $temperature = function (string $name) use ($request): ?int {
            $value = trim((string)$request->input($name, ''));

            return preg_match('~^[+-]?\d{1,2}$~', $value) && abs((int)$value) <= 60 ? (int)$value : null;
        };
        $tempMin = $temperature('clothes-temp-min');
        $tempMax = $temperature('clothes-temp-max');
        if ($tempMin !== null && $tempMax !== null && $tempMax < $tempMin) {
            [$tempMin, $tempMax] = [$tempMax, $tempMin];
        }

        $shopUrl = $text('clothes-shop-url', 1000);
        if ($shopUrl !== null && !preg_match('~^https?://~i', $shopUrl)) {
            $shopUrl = 'https://' . $shopUrl;
        }

        $attributes = [
            // список брендов (wardrobe/parts/brand_select): id из справочника или "new" + название своего
            'brand_id' => WardrobeBrand::resolveFromForm($request->input('clothes-brand-id'), $text('clothes-brand-new', 150), $userId),
            'collection_year' => $year,
            'kind' => $text('clothes-kind', 50),
            'length' => $text('clothes-length', 50),
            'seasons' => $keys('clothes-seasons', WardrobeOutfit::SEASONS),
            'temp_min' => $tempMin,
            'temp_max' => $tempMax,
            'occasions' => $keys('clothes-occasions', WardrobeItem::OCCASIONS),
            // список цветов (wardrobe/parts/color_select): id из справочника или "new" + название и оттенок своего
            'color_id' => WardrobeColor::resolveFromForm($request->input('clothes-color-id'), $text('clothes-color-new', 150), $userId, $request->input('clothes-color-hex')),
            // список принтов (wardrobe/parts/print_select): id из справочника или "new" + название своего
            'print_id' => WardrobePrint::resolveFromForm($request->input('clothes-print-id'), $text('clothes-print-new', 150), $userId),
            // то же для материала (wardrobe/parts/material_select)
            'material_id' => WardrobeMaterial::resolveFromForm($request->input('clothes-material-id'), $text('clothes-material-new', 150), $userId),
            // состав с процентами — свободный текст
            'composition' => $text('clothes-composition', 255),
            'styles' => $keys('clothes-styles', WardrobeItem::STYLES),
            'body_types' => $keys('clothes-body-types', WardrobeItem::BODY_TYPES),
            'shop_url' => $shopUrl,
        ];

        $filled = array_filter($attributes, function ($value) {
            return $value !== null;
        });

        return empty($filled) ? null : $attributes;
    }

    /**
     * Сохраняет/обновляет данные вещи поста; если все поля пустые — удаляет запись
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
     * Поля формы вещи "Моего гардероба" (wardrobe.js, applyPostImage) — для кнопки "В гардероб" у пина
     *
     * @return array
     */
    public function toWardrobeForm(): array
    {
        return [
            'category' => $this->wardrobeCategory(),
            'kind' => $this->kind,
            'brand_id' => $this->brand_id,
            'brand' => optional($this->brand)->name,
            'color_id' => $this->color_id,
            'color' => optional($this->color)->name,
            'print_id' => $this->print_id,
            'print' => optional($this->print)->name,
            'material_id' => $this->material_id,
            'material' => optional($this->material)->name,
            'composition' => $this->composition,
            'length' => $this->length,
            'seasons' => $this->seasons ?: [],
            'temp_min' => $this->temp_min,
            'temp_max' => $this->temp_max,
            'occasions' => $this->occasions ?: [],
            'styles' => $this->styles ?: [],
            'body_types' => $this->body_types ?: [],
            'shop_url' => $this->shop_url,
        ];
    }
}
