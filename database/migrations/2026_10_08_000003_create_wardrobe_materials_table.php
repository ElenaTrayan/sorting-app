<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

// Материалы одежды — справочник по образцу принтов (wardrobe_prints): общий для всех пользователей
// (user_id = null, ведёт админ — админка → Материалы одежды) и свои материалы пользователя (user_id).
// У материала — группа (ключи WardrobeMaterial::GROUPS) и картинка-образец. У вещи гардероба и у пина
// "Одежда" вместо текстового material — material_id; то, что уже было вписано текстом, переносится:
// совпало с общим — общий, иначе свой материал владельца.
class CreateWardrobeMaterialsTable extends Migration
{
    // группа => материалы; большой стартовый список, чтобы свои почти не приходилось добавлять
    private const STARTER_MATERIALS = [
        'plant' => [
            'Хлопок', 'Органический хлопок', 'Лён', 'Конопля', 'Рами', 'Джут', 'Бамбук',
        ],
        'animal' => [
            'Шерсть', 'Меринос', 'Кашемир', 'Мохер', 'Альпака', 'Ангора', 'Верблюжья шерсть', 'Шерсть яка',
            'Шёлк', 'Дикий шёлк (туссар)',
        ],
        'artificial' => [
            'Вискоза', 'Модал', 'Лиоцелл (тенсел)', 'Купро', 'Ацетат', 'Триацетат',
        ],
        'synthetic' => [
            'Полиэстер', 'Переработанный полиэстер', 'Полиамид (нейлон)', 'Эластан (спандекс, лайкра)', 'Акрил',
            'Полиуретан', 'Полипропилен', 'Микрофибра', 'Неопрен',
        ],
        'woven' => [
            'Деним', 'Твид', 'Вельвет', 'Бархат', 'Велюр', 'Атлас', 'Сатин', 'Шифон', 'Органза', 'Фатин',
            'Кружево', 'Гипюр', 'Поплин', 'Батист', 'Муслин', 'Марлёвка', 'Креп', 'Крепдешин', 'Жоржет', 'Кади',
            'Габардин', 'Саржа', 'Твил', 'Фланель', 'Драп', 'Букле', 'Жаккард', 'Гобелен', 'Парча', 'Тафта',
            'Канвас (парусина)', 'Оксфорд', 'Шамбре', 'Вуаль', 'Штапель', 'Сирсакер', 'Плащёвка',
            'Костюмная ткань', 'Пальтовая ткань', 'Сетка',
        ],
        'knit' => [
            'Трикотаж', 'Джерси', 'Футер', 'Кулирка', 'Интерлок', 'Рибана', 'Кашкорсе', 'Пике (лакоста)',
            'Лапша (рубчик)', 'Вязаное полотно', 'Милано', 'Флис', 'Велсофт', 'Махра', 'Плюш', 'Капитоний',
            'Бифлекс',
        ],
        'leather' => [
            'Натуральная кожа', 'Замша', 'Нубук', 'Лакированная кожа', 'Кожа рептилии', 'Экокожа',
            'Искусственная замша', 'Натуральный мех', 'Овчина', 'Искусственный мех', 'Экомех (тедди)',
        ],
        'filling' => [
            'Пух', 'Перо', 'Биопух', 'Синтепон', 'Холлофайбер', 'Тинсулейт', 'Мембрана',
        ],
        'hard' => [
            'Металл', 'Золото', 'Серебро', 'Позолота', 'Сталь', 'Латунь', 'Бижутерный сплав', 'Жемчуг',
            'Натуральные камни', 'Стекло', 'Бисер', 'Керамика', 'Дерево', 'Пробка', 'Пластик', 'Акрил (пластик)',
            'Смола', 'Резина', 'ПВХ', 'Рафия (солома)', 'Текстиль',
        ],
    ];

    public function up()
    {
        Schema::create('wardrobe_materials', function (Blueprint $table) {
            $table->id();
            $table->integer('user_id')->nullable(); // null — общий материал, иначе свой материал пользователя
            $table->string('group', 30)->default('other');
            $table->string('name', 150);
            $table->text('image')->nullable(); // образец: {name, extension, path}
            $table->timestamps();

            $table->index(['user_id', 'name']);
        });

        $now = now();
        $rows = [];
        foreach (self::STARTER_MATERIALS as $group => $names) {
            foreach ($names as $name) {
                $rows[] = ['user_id' => null, 'group' => $group, 'name' => $name, 'created_at' => $now, 'updated_at' => $now];
            }
        }
        DB::table('wardrobe_materials')->insert($rows);

        Schema::table('wardrobe_items', function (Blueprint $table) {
            $table->unsignedBigInteger('material_id')->nullable()->after('print_id');
            $table->index('material_id');
        });
        Schema::table('post_clothes', function (Blueprint $table) {
            $table->unsignedBigInteger('material_id')->nullable()->after('print_id');
            $table->index('material_id');
        });

        // общий материал по названию без учёта регистра
        $shared = [];
        foreach (DB::table('wardrobe_materials')->whereNull('user_id')->get(['id', 'name']) as $material) {
            $shared[mb_strtolower($material->name)] = $material->id;
        }
        $own = []; // "user_id|название" => id

        $resolve = function ($userId, $text) use (&$shared, &$own, $now) {
            $name = trim((string)$text);
            if ($name === '') {
                return null;
            }
            // "100% хлопок", "хлопок 100%" — это общий "Хлопок"
            $key = mb_strtolower(trim(preg_replace('/\s*100\s*%\s*/u', ' ', $name)));

            if (isset($shared[$key])) {
                return $shared[$key];
            }

            $ownKey = $userId . '|' . mb_strtolower($name);
            if (!isset($own[$ownKey])) {
                $own[$ownKey] = DB::table('wardrobe_materials')->insertGetId([
                    'user_id' => $userId, 'group' => 'other', 'name' => mb_substr($name, 0, 150), 'created_at' => $now, 'updated_at' => $now,
                ]);
            }

            return $own[$ownKey];
        };

        foreach (DB::table('wardrobe_items')->whereNotNull('material')->get(['id', 'user_id', 'material']) as $item) {
            DB::table('wardrobe_items')->where('id', $item->id)->update(['material_id' => $resolve($item->user_id, $item->material)]);
        }

        $clothes = DB::table('post_clothes')
            ->join('posts', 'posts.id', '=', 'post_clothes.post_id')
            ->whereNotNull('post_clothes.material')
            ->get(['post_clothes.id', 'posts.user_id', 'post_clothes.material']);
        foreach ($clothes as $row) {
            DB::table('post_clothes')->where('id', $row->id)->update(['material_id' => $resolve($row->user_id, $row->material)]);
        }

        Schema::table('wardrobe_items', function (Blueprint $table) {
            $table->dropColumn('material');
        });
        Schema::table('post_clothes', function (Blueprint $table) {
            $table->dropColumn('material');
        });
    }

    public function down()
    {
        Schema::table('wardrobe_items', function (Blueprint $table) {
            $table->string('material', 255)->nullable()->after('print_id');
        });
        Schema::table('post_clothes', function (Blueprint $table) {
            $table->string('material', 255)->nullable()->after('print_id');
        });

        $names = DB::table('wardrobe_materials')->pluck('name', 'id');
        foreach (['wardrobe_items', 'post_clothes'] as $tableName) {
            foreach (DB::table($tableName)->whereNotNull('material_id')->get(['id', 'material_id']) as $row) {
                DB::table($tableName)->where('id', $row->id)->update(['material' => $names[$row->material_id] ?? null]);
            }

            Schema::table($tableName, function (Blueprint $table) {
                $table->dropIndex(['material_id']);
                $table->dropColumn('material_id');
            });
        }

        Schema::dropIfExists('wardrobe_materials');
    }
}
