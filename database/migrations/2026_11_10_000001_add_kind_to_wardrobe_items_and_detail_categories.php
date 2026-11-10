<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

// Тип вещи (wardrobe_items.kind: "Худи", "Спортивный топ") — чтобы считать вещи по типам; категория остаётся
// крупной группой. Категории — подробный список вместо прежних 11 ("Верх" делится на футболки, рубашки, трикотаж…),
// у каждой — свои типы. Вещам тип подбирается по названию ("Платье зелёное Zara" → "Платье"), категория — по типу;
// у вещей без узнаваемого типа категория переводится со старой (OLD_CATEGORY_FALLBACK).
class AddKindToWardrobeItemsAndDetailCategories extends Migration
{
    private const CATEGORIES = [
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

    // прежние категории, которых больше нет, — куда деть их вещи, если тип по названию не узнался
    private const OLD_CATEGORY_FALLBACK = [
        'top' => 'tops',
        'bottom' => 'trousers',
        'outerwear' => 'jackets',
    ];

    // слова в названиях, которые не начинаются с названия типа
    private const NAME_SYNONYMS = [
        'топик' => 'Топ',
        'пижамн' => 'Пижама',
    ];

    public function up()
    {
        Schema::table('wardrobe_items', function (Blueprint $table) {
            // тип вещи — из типов её категории (wardrobe_categories.kinds) или свой
            $table->string('kind', 50)->nullable()->after('category');
        });

        $now = now();
        $order = 0;
        foreach (self::CATEGORIES as $slug => [$name, $kinds]) {
            DB::table('wardrobe_categories')->updateOrInsert(['slug' => $slug], [
                'name' => $name,
                'kinds' => $kinds ? json_encode($kinds, JSON_UNESCAPED_UNICODE) : null,
                'sort_order' => ++$order,
                'updated_at' => $now,
            ]);
            DB::table('wardrobe_categories')->where('slug', $slug)->whereNull('created_at')->update(['created_at' => $now]);
        }

        // тип => категория; длинные типы первыми: "Юбка-шорты" раньше "Юбка", "Спортивный топ" раньше "Топ"
        $kindCategory = [];
        foreach (self::CATEGORIES as $slug => [, $kinds]) {
            foreach ($kinds as $kind) {
                $kindCategory[$kind] = $slug;
            }
        }
        uksort($kindCategory, function ($a, $b) {
            return mb_strlen($b) <=> mb_strlen($a);
        });

        DB::table('wardrobe_items')->orderBy('id')->get(['id', 'name', 'category'])->each(function ($item) use ($kindCategory) {
            $kind = $this->guessKind((string)$item->name, array_keys($kindCategory));
            if ($kind !== null) {
                $category = $kindCategory[$kind];
            } else {
                $category = self::OLD_CATEGORY_FALLBACK[$item->category] ?? $item->category;
            }
            DB::table('wardrobe_items')->where('id', $item->id)->update(['kind' => $kind, 'category' => $category]);
        });

        // прежние категории, которых нет в новом списке (и вещей в них уже нет), — убрать
        $known = array_keys(self::CATEGORIES);
        DB::table('wardrobe_categories')->whereIn('slug', array_keys(self::OLD_CATEGORY_FALLBACK))->whereNotIn('slug', $known)->delete();
    }

    /**
     * Тип вещи по названию: тип, который стоит в названии отдельным словом ("Однобортный пиджак" → "Пиджак"),
     * или слово из NAME_SYNONYMS в начале слова ("Пижамный комплект" → "Пижама")
     *
     * @param string $name
     * @param string[] $kinds от длинных к коротким
     * @return string|null
     */
    private function guessKind(string $name, array $kinds): ?string
    {
        $name = mb_strtolower($name);
        foreach ($kinds as $kind) {
            // слово целиком: "Поло" не должно находиться в "Полосатая футболка"
            if (preg_match('~(?<!\p{L})' . preg_quote(mb_strtolower($kind), '~') . '(?!\p{L})~u', $name)) {
                return $kind;
            }
        }
        foreach (self::NAME_SYNONYMS as $word => $kind) {
            if (preg_match('~(?<!\p{L})' . preg_quote($word, '~') . '~u', $name)) {
                return $kind;
            }
        }

        return null;
    }

    public function down()
    {
        // категории и перенесённые вещи назад не возвращаем — только убираем тип
        Schema::table('wardrobe_items', function (Blueprint $table) {
            $table->dropColumn('kind');
        });
    }
}
