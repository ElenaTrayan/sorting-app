<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

// Принты одежды: общий справочник для всех пользователей (user_id = null, ведёт админ — админка →
// Принты одежды) и свои принты пользователя (user_id). У принта — группа (ключи WardrobePrint::GROUPS)
// и картинка-образец. У вещи гардероба и у пина "Одежда" вместо текстового print — print_id; то, что
// уже было вписано текстом, переносится: совпало с общим — общий, иначе свой принт владельца.
class CreateWardrobePrintsTable extends Migration
{
    // группа => принты; большой стартовый список, чтобы свои принты почти не приходилось добавлять
    private const STARTER_PRINTS = [
        'plain' => [
            'Однотонный', 'Меланж', 'Омбре (градиент)', 'Тай-дай', 'Варёнка', 'Батик', 'Металлик', 'Пайетки',
            'Люрекс', 'Жаккард', 'Кружево', 'Вышивка', 'Стёжка', 'Косы (вязка)', 'Рубчик', 'Колор-блок',
        ],
        'stripes' => [
            'Полоска', 'Полоска горизонтальная', 'Полоска вертикальная', 'Полоска диагональная',
            'Тельняшка (бретонская полоска)', 'Тонкая полоска (пинстрайп)', 'Меловая полоска', 'Широкая полоска',
            'Разноцветная полоска', 'Матрасная полоска (тик)', 'Карандашная полоска', 'Регби-полоска',
            'Полоска байадер', 'Маркизная полоска', 'Полоска с люрексом',
        ],
        'checks' => [
            'Клетка', 'Тартан (шотландка)', 'Виши (гингем)', 'Гусиная лапка', 'Пье-де-пуль',
            'Принц Уэльский (гленчек)', 'Мадрас', 'Клетка буффало', 'Оконная клетка (виндоупейн)',
            'Аргайл (ромбы)', 'Шахматная клетка', 'Клетка нова', 'Таттерсол', 'Пастушья клетка',
            'Мелкая клетка', 'Крупная клетка', 'Клетка пепита',
        ],
        'dots' => [
            'Горох', 'Мелкий горох', 'Средний горох', 'Крупный горох', 'Разноцветный горох',
            'Плюмети (швейцарский горошек)', 'Конфетти', 'Крапинка',
        ],
        'floral' => [
            'Цветочный', 'Мелкий цветочек (мильфлёр)', 'Крупные цветы', 'Акварельные цветы', 'Розы', 'Пионы',
            'Маки', 'Ромашки', 'Подсолнухи', 'Лаванда', 'Тюльпаны', 'Сакура', 'Тропические цветы', 'Гибискус',
            'Либерти', 'Ботанический', 'Винтажный цветочный', 'Цветы на тёмном фоне', 'Полевые цветы',
            'Цветочный бордюр', 'Туаль де Жуи',
        ],
        'animal' => [
            'Анималистичный', 'Леопард', 'Гепард', 'Ягуар', 'Зебра', 'Тигр', 'Жираф', 'Змеиный (питон)',
            'Крокодил', 'Корова', 'Далматин', 'Черепаховый', 'Павлиньи перья', 'Перья',
        ],
        'geometric' => [
            'Геометрический', 'Ромбы', 'Зигзаг (шеврон)', 'Ёлочка', 'Треугольники', 'Квадраты', 'Круги',
            'Соты (шестиугольники)', 'Волны', 'Линии', 'Сетка', 'Решётка (трельяж)', 'Спирали', 'Оп-арт',
            'Мозаика', 'Кубы (3D)', 'Арт-деко', 'Мемфис', 'Баухаус',
        ],
        'ethnic' => [
            'Пейсли (огурцы)', 'Икат', 'Ацтекский', 'Навахо', 'Африканский (анкара)', 'Дамаск', 'Барокко',
            'Марокканский', 'Азулежу (плитка)', 'Скандинавский орнамент', 'Фэр-Айл', 'Украинский орнамент (вышиванка)',
            'Петриковская роспись', 'Бандана', 'Греческий меандр', 'Арабеска', 'Японские волны (сейгайха)',
            'Кельтский узор', 'Килим', 'Индийский блок-принт', 'Мандала', 'Шинуазри', 'Этнический',
        ],
        'nature' => [
            'Тропические листья', 'Пальмы', 'Папоротник', 'Листья', 'Ветки', 'Фрукты', 'Лимоны', 'Вишни',
            'Клубника', 'Ягоды', 'Ананасы', 'Кактусы', 'Грибы', 'Ракушки', 'Кораллы', 'Рыбы', 'Птицы', 'Ласточки',
            'Бабочки', 'Стрекозы', 'Пчёлы', 'Кошки', 'Собаки', 'Лошади', 'Облака', 'Горы', 'Пейзаж',
        ],
        'abstract' => [
            'Абстрактный', 'Мазки кисти', 'Акварель', 'Брызги краски', 'Мрамор', 'Разводы', 'Граффити',
            'Камуфляж', 'Пиксели', 'Терраццо', 'Психоделика', 'Каракули (дудл)', 'Фотопринт', 'Коллаж',
            'Цифровой принт',
        ],
        'text' => [
            'Надпись', 'Логотип', 'Монограмма (логомания)', 'Слоган', 'Буквы', 'Цифры', 'Каллиграфия',
            'Иероглифы', 'Газетный принт',
        ],
        'novelty' => [
            'Сердечки', 'Звёзды', 'Бантики', 'Губы', 'Глаза', 'Смайлики', 'Мультперсонажи', 'Комиксы',
            'Новогодний', 'Снежинки', 'Олени', 'Хэллоуин', 'Морской (якоря)', 'Космос', 'Карта мира',
            'Игральные карты', 'Ноты', 'Еда', 'Кофе', 'Автомобили', 'Велосипеды', 'Города', 'Черепа',
            'Цепи и ремни', 'Короны', 'Ключи', 'Зонтики', 'Самолёты', 'Динозавры', 'Единороги', 'Радуга',
        ],
    ];

    // подсказки, которые были зашиты в форму (WardrobeItem::PRINT_SUGGESTIONS), и названия в справочнике
    private const ALIASES = [
        'геометрия' => 'геометрический',
        'абстракция' => 'абстрактный',
        'надпись / логотип' => 'надпись',
        'пейсли' => 'пейсли (огурцы)',
    ];

    public function up()
    {
        Schema::create('wardrobe_prints', function (Blueprint $table) {
            $table->id();
            $table->integer('user_id')->nullable(); // null — общий принт, иначе свой принт пользователя
            $table->string('group', 30)->default('other');
            $table->string('name', 150);
            $table->text('image')->nullable(); // образец: {name, extension, path}
            $table->timestamps();

            $table->index(['user_id', 'name']);
        });

        $now = now();
        $rows = [];
        foreach (self::STARTER_PRINTS as $group => $names) {
            foreach ($names as $name) {
                $rows[] = ['user_id' => null, 'group' => $group, 'name' => $name, 'created_at' => $now, 'updated_at' => $now];
            }
        }
        DB::table('wardrobe_prints')->insert($rows);

        Schema::table('wardrobe_items', function (Blueprint $table) {
            $table->unsignedBigInteger('print_id')->nullable()->after('size');
            $table->index('print_id');
        });
        Schema::table('post_clothes', function (Blueprint $table) {
            $table->unsignedBigInteger('print_id')->nullable()->after('color');
            $table->index('print_id');
        });

        // общий принт по названию без учёта регистра
        $shared = [];
        foreach (DB::table('wardrobe_prints')->whereNull('user_id')->get(['id', 'name']) as $print) {
            $shared[mb_strtolower($print->name)] = $print->id;
        }
        $own = []; // "user_id|название" => id

        $resolve = function ($userId, $text) use (&$shared, &$own, $now) {
            $name = trim((string)$text);
            if ($name === '') {
                return null;
            }
            $key = mb_strtolower($name);
            $key = self::ALIASES[$key] ?? $key;

            if (isset($shared[$key])) {
                return $shared[$key];
            }

            $ownKey = $userId . '|' . $key;
            if (!isset($own[$ownKey])) {
                $own[$ownKey] = DB::table('wardrobe_prints')->insertGetId([
                    'user_id' => $userId, 'group' => 'other', 'name' => $name, 'created_at' => $now, 'updated_at' => $now,
                ]);
            }

            return $own[$ownKey];
        };

        foreach (DB::table('wardrobe_items')->whereNotNull('print')->get(['id', 'user_id', 'print']) as $item) {
            DB::table('wardrobe_items')->where('id', $item->id)->update(['print_id' => $resolve($item->user_id, $item->print)]);
        }

        $clothes = DB::table('post_clothes')
            ->join('posts', 'posts.id', '=', 'post_clothes.post_id')
            ->whereNotNull('post_clothes.print')
            ->get(['post_clothes.id', 'posts.user_id', 'post_clothes.print']);
        foreach ($clothes as $row) {
            DB::table('post_clothes')->where('id', $row->id)->update(['print_id' => $resolve($row->user_id, $row->print)]);
        }

        Schema::table('wardrobe_items', function (Blueprint $table) {
            $table->dropColumn('print');
        });
        Schema::table('post_clothes', function (Blueprint $table) {
            $table->dropColumn('print');
        });
    }

    public function down()
    {
        Schema::table('wardrobe_items', function (Blueprint $table) {
            $table->string('print', 100)->nullable()->after('size');
        });
        Schema::table('post_clothes', function (Blueprint $table) {
            $table->string('print', 100)->nullable()->after('color');
        });

        $names = DB::table('wardrobe_prints')->pluck('name', 'id');
        foreach (['wardrobe_items', 'post_clothes'] as $tableName) {
            foreach (DB::table($tableName)->whereNotNull('print_id')->get(['id', 'print_id']) as $row) {
                DB::table($tableName)->where('id', $row->id)->update(['print' => mb_substr((string)($names[$row->print_id] ?? ''), 0, 100) ?: null]);
            }

            Schema::table($tableName, function (Blueprint $table) {
                $table->dropIndex(['print_id']);
                $table->dropColumn('print_id');
            });
        }

        Schema::dropIfExists('wardrobe_prints');
    }
}
