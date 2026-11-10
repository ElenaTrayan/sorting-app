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

    // список по умолчанию: slug => [название, типы вещей]. Рабочий — в БД (ведёт админ), этот — запасной, пока таблицы нет
    public const DEFAULTS = [
        'tops' => ['Футболки и топы', ['Майка', 'Футболка', 'Топ', 'Кроп-топ', 'Топ на бретелях', 'Бюстье', 'Корсет', 'Лонгслив', 'Боди']],
        'shirts' => ['Рубашки и блузки', ['Рубашка', 'Блузка', 'Туника', 'Поло']],
        'knitwear' => ['Трикотаж', ['Свитер', 'Джемпер', 'Пуловер', 'Кардиган', 'Водолазка', 'Вязаный жилет']],
        'hoodies' => ['Толстовки', ['Худи', 'Свитшот', 'Зип-худи', 'Олимпийка', 'Флиска']],
        'blazers' => ['Пиджаки и жилеты', ['Пиджак', 'Жакет', 'Блейзер', 'Жилет']],
        'trousers' => ['Брюки и джинсы', ['Джинсы', 'Брюки', 'Кюлоты', 'Палаццо', 'Джоггеры', 'Карго', 'Леггинсы', 'Лосины']],
        'skirts' => ['Юбки', ['Юбка', 'Мини-юбка', 'Юбка-миди', 'Юбка-макси', 'Юбка-карандаш', 'Плиссированная юбка',
            'Джинсовая юбка', 'Юбка-шорты']],
        'shorts' => ['Шорты', ['Шорты', 'Джинсовые шорты', 'Бермуды']],
        'dress' => ['Платья и сарафаны', ['Платье', 'Сарафан', 'Платье-рубашка', 'Платье-комбинация', 'Вечернее платье',
            'Коктейльное платье', 'Вязаное платье']],
        'suits' => ['Комбинезоны и костюмы', ['Комбинезон', 'Ромпер', 'Костюм', 'Брючный костюм', 'Юбочный костюм', 'Двойка']],
        'coats' => ['Пальто и плащи', ['Пальто', 'Полупальто', 'Плащ', 'Тренч']],
        'jackets' => ['Куртки', ['Куртка', 'Косуха', 'Бомбер', 'Ветровка', 'Джинсовка', 'Шакет']],
        'down' => ['Пуховики и шубы', ['Пуховик', 'Парка', 'Шуба', 'Дублёнка', 'Жилет-пуховик']],
        'sneakers' => ['Кроссовки и кеды', ['Кроссовки', 'Кеды', 'Слипоны']],
        'shoes' => ['Туфли', ['Туфли', 'Туфли-лодочки', 'Туфли на каблуке', 'Лоферы', 'Балетки', 'Мюли', 'Оксфорды', 'Мэри Джейн']],
        'boots' => ['Ботинки и сапоги', ['Ботинки', 'Ботильоны', 'Челси', 'Сапоги', 'Ботфорты', 'Угги', 'Дутики']],
        'summer_shoes' => ['Летняя обувь', ['Босоножки', 'Сандалии', 'Шлёпанцы', 'Сабо', 'Эспадрильи']],
        'bag' => ['Сумки и рюкзаки', ['Сумка', 'Сумка через плечо', 'Тоут', 'Клатч', 'Шопер', 'Поясная сумка', 'Рюкзак', 'Кошелёк']],
        'hats' => ['Головные уборы', ['Шапка', 'Бини', 'Кепка', 'Бейсболка', 'Панама', 'Шляпа', 'Берет', 'Повязка']],
        'scarves' => ['Шарфы и платки', ['Шарф', 'Снуд', 'Платок', 'Палантин']],
        'accessory' => ['Аксессуары', ['Ремень', 'Перчатки', 'Очки', 'Солнцезащитные очки', 'Часы', 'Зонт', 'Заколка', 'Ободок']],
        'jewelry' => ['Украшения', ['Серьги', 'Кольцо', 'Колье', 'Цепочка', 'Кулон', 'Браслет', 'Брошь']],
        'underwear' => ['Бельё', ['Бельё', 'Бюстгальтер', 'Бралетт', 'Трусы', 'Комбинация', 'Корректирующее бельё']],
        'hosiery' => ['Колготки и носки', ['Колготки', 'Чулки', 'Носки', 'Гольфы', 'Следки']],
        'homewear' => ['Домашнее', ['Пижама', 'Халат', 'Домашний костюм', 'Ночная сорочка']],
        'swimwear' => ['Купальники и пляжное', ['Купальник', 'Слитный купальник', 'Раздельный купальник', 'Парео', 'Пляжное платье']],
        'sport' => ['Спорт', ['Спортивный топ', 'Спортивный бюстгальтер', 'Спортивные леггинсы', 'Велосипедки',
            'Спортивный костюм', 'Спортивная футболка', 'Спортивная куртка']],
        'other' => ['Другое', []],
    ];

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
     * Все категории по порядку. Пока таблицы нет (миграция не запущена) — список по умолчанию (DEFAULTS).
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
                self::$loaded = collect(self::DEFAULTS)->map(function ($category, $slug) {
                    return new static(['slug' => $slug, 'name' => $category[0], 'kinds' => $category[1] ?: null]);
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

    /**
     * Категория по типу вещи ("свитер" → knitwear, без учёта регистра); свой тип, которого нет в списках, — null
     *
     * @param string|null $kind
     * @return string|null slug
     */
    public static function slugForKind(?string $kind): ?string
    {
        $kind = mb_strtolower(trim((string)$kind));
        foreach (self::kindsBySlug() as $slug => $kinds) {
            foreach ($kinds as $suggestion) {
                if (mb_strtolower($suggestion) === $kind) {
                    return $slug;
                }
            }
        }

        return null;
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
