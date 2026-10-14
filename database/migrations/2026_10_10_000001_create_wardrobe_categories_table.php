<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

// Категории одежды "Моего гардероба" — общий для всех пользователей список, который ведёт админ
// (админка → Категории одежды). Раньше был константой WardrobeItem::CATEGORIES, типы вещей — PostClothes::KINDS.
// У вещей в wardrobe_items.category по-прежнему лежит slug категории — данные вещей миграция не трогает.
class CreateWardrobeCategoriesTable extends Migration
{
    public function up()
    {
        Schema::create('wardrobe_categories', function (Blueprint $table) {
            $table->id();
            $table->string('slug', 30)->unique();
            $table->string('name', 100);
            // типы вещей этой категории (JSON-массив): подсказки поля "Тип вещи" у пина "Одежда";
            // по типу вещи из пина определяется категория вещи в гардеробе
            $table->text('kinds')->nullable();
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();
        });

        $categories = [
            'top' => ['Верх', ['Майка', 'Футболка', 'Топ', 'Лонгслив', 'Рубашка', 'Блузка', 'Свитер', 'Джемпер', 'Кардиган',
                'Свитшот', 'Худи', 'Водолазка', 'Жилет', 'Пиджак', 'Жакет']],
            'bottom' => ['Низ', ['Джинсы', 'Брюки', 'Юбка', 'Шорты', 'Леггинсы', 'Кюлоты']],
            'dress' => ['Платья и комбинезоны', ['Платье', 'Сарафан', 'Комбинезон', 'Костюм']],
            'outerwear' => ['Верхняя одежда', ['Пальто', 'Плащ', 'Тренч', 'Куртка', 'Пуховик', 'Парка', 'Шуба', 'Бомбер', 'Косуха', 'Ветровка']],
            'shoes' => ['Обувь', ['Кроссовки', 'Кеды', 'Туфли', 'Лоферы', 'Балетки', 'Босоножки', 'Сандалии', 'Ботинки', 'Ботильоны',
                'Сапоги', 'Мюли', 'Шлёпанцы']],
            'bag' => ['Сумки', ['Сумка', 'Рюкзак', 'Клатч', 'Шопер', 'Поясная сумка']],
            'accessory' => ['Аксессуары', ['Ремень', 'Шарф', 'Платок', 'Шапка', 'Кепка', 'Панама', 'Шляпа', 'Перчатки', 'Очки',
                'Часы', 'Зонт']],
            'jewelry' => ['Украшения', ['Серьги', 'Кольцо', 'Колье', 'Цепочка', 'Браслет', 'Брошь']],
            'underwear' => ['Бельё и домашнее', ['Бельё', 'Пижама', 'Халат', 'Носки', 'Колготки', 'Купальник']],
            'sport' => ['Спорт', ['Спортивный костюм', 'Велосипедки', 'Спортивный топ']],
            'other' => ['Другое', []],
        ];

        $now = now();
        $order = 0;
        foreach ($categories as $slug => [$name, $kinds]) {
            DB::table('wardrobe_categories')->insert([
                'slug' => $slug,
                'name' => $name,
                'kinds' => $kinds ? json_encode($kinds, JSON_UNESCAPED_UNICODE) : null,
                'sort_order' => ++$order,
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }
    }

    public function down()
    {
        Schema::dropIfExists('wardrobe_categories');
    }
}
