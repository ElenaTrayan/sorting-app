<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

// Цвета одежды — справочник по образцу принтов и материалов: общий для всех пользователей (user_id = null,
// ведёт админ — админка → Цвета одежды) и свои цвета пользователя (user_id). У цвета — группа (ключи
// WardrobeColor::GROUPS), название и сам цвет: hex (#RRGGBB), у двухцветных — ещё hex2. У вещи гардероба
// и у пина "Одежда" вместо текстового color — color_id; то, что уже было вписано текстом, переносится:
// совпало с общим — общий, иначе свой цвет владельца (без образца, пока он его не выберет).
class CreateWardrobeColorsTable extends Migration
{
    // группа => [название => hex | [hex, hex2] | null]; первым в группе идёт её основной цвет
    private const STARTER_COLORS = [
        'white' => [
            'Белый' => 'FFFFFF', 'Молочный' => 'FDF6E7', 'Айвори (слоновая кость)' => 'FFFFF0', 'Кремовый' => 'FFF4D6',
            'Экрю' => 'F0EAD6', 'Ванильный' => 'F3E5AB', 'Жемчужный' => 'F1ECE4', 'Шампань' => 'F7E7CE',
        ],
        'beige' => [
            'Бежевый' => 'E8D5B7', 'Светло-бежевый' => 'F2E6D0', 'Песочный' => 'DCC39A', 'Нюдовый (телесный)' => 'E6C3A5',
            'Пудровый беж' => 'E9CFC0', 'Кэмел (верблюжий)' => 'C19A6B', 'Карамельный' => 'C68E4E', 'Тауп (серо-коричневый)' => '8B7D6B',
            'Латте' => 'C8AD8D', 'Капучино' => 'A68B6D', 'Овсяный' => 'DED3BF', 'Льняной' => 'E4DCC8',
        ],
        'brown' => [
            'Коричневый' => '7B4A2B', 'Светло-коричневый' => 'A9764C', 'Тёмно-коричневый' => '4A2C17', 'Шоколадный' => '4E2A1E',
            'Кофейный' => '6F4E37', 'Мокко' => '8A6A54', 'Каштановый' => '7A3E2A', 'Коньячный' => '9A5B2D',
            'Ореховый' => '8B5E3C', 'Рыжий' => 'B5651D', 'Терракотовый' => 'C2603F', 'Кирпичный' => '9C4A33',
            'Ржавый' => 'A9441F', 'Корица' => 'A0592B',
        ],
        'grey' => [
            'Серый' => '8E9196', 'Светло-серый' => 'D3D5D9', 'Тёмно-серый' => '55585E', 'Серый меланж' => 'A9ACB1',
            'Графитовый' => '3C3F45', 'Антрацит' => '2E3035', 'Стальной' => '71797E', 'Мокрый асфальт' => '4E5258',
            'Дымчатый' => '9A9CA3', 'Серо-голубой' => '8A9BA8', 'Серо-бежевый (грейдж)' => 'B8AFA2',
        ],
        'black' => [
            'Чёрный' => '111111', 'Угольный' => '26272B', 'Чёрный выцветший' => '3A3A3C',
        ],
        'red' => [
            'Красный' => 'D7263D', 'Алый' => 'E81E25', 'Томатный' => 'E5492F', 'Вишнёвый' => '8E1B32',
            'Малиновый' => 'C2185B', 'Гранатовый' => '8A1C2B', 'Клубничный' => 'E0354F', 'Рубиновый' => '9B111E',
            'Карминовый' => 'A91B2E',
        ],
        'burgundy' => [
            'Бордовый' => '6D1A2B', 'Винный' => '722F37', 'Марсала' => '8A4B45', 'Бургунди' => '5E1425',
            'Сливово-винный' => '5A2338', 'Свекольный' => '7A1F3D',
        ],
        'pink' => [
            'Розовый' => 'F28CB1', 'Светло-розовый' => 'F9D1DC', 'Пудровый' => 'E8C4C4', 'Пыльная роза' => 'C99A9E',
            'Фуксия' => 'D9167A', 'Маджента' => 'C8187E', 'Ярко-розовый' => 'F0468A', 'Лососёвый' => 'F4A28C',
            'Персиково-розовый' => 'F7B7A3', 'Розово-бежевый' => 'D9A6A0', 'Чайная роза' => 'E7B5B0', 'Зефирный' => 'F6DCE4',
        ],
        'orange' => [
            'Оранжевый' => 'F57C1F', 'Коралловый' => 'F26B5B', 'Персиковый' => 'F9C7A1', 'Абрикосовый' => 'F6B27A',
            'Мандариновый' => 'F28C1E', 'Тыквенный' => 'D96F1E', 'Морковный' => 'E8662A', 'Апельсиновый' => 'F7941D',
            'Янтарный' => 'D99A2B',
        ],
        'yellow' => [
            'Жёлтый' => 'F7D23E', 'Лимонный' => 'F4E33B', 'Светло-жёлтый' => 'F8EFA6', 'Горчичный' => 'C99A2E',
            'Медовый' => 'D9A441', 'Шафрановый' => 'E8A317', 'Сливочно-жёлтый' => 'F5E6A8', 'Банановый' => 'F3DE7C',
            'Охра' => 'C08A2B',
        ],
        'green' => [
            'Зелёный' => '2E8B57', 'Светло-зелёный' => 'A9D8A1', 'Тёмно-зелёный' => '1E4D2B', 'Изумрудный' => '0F7B5F',
            'Бутылочный' => '0B4F3C', 'Хаки' => '6B6B3A', 'Оливковый' => '708238', 'Фисташковый' => 'B5CC8E',
            'Шалфей' => '9CAF88', 'Травяной' => '4C9A2A', 'Салатовый' => 'A6D608', 'Лаймовый' => 'BFE23A',
            'Мох' => '5A6B3A', 'Хвойный' => '1F4D3A', 'Нефритовый' => '2D8C6A', 'Милитари' => '4B5320',
        ],
        'teal' => [
            'Бирюзовый' => '1FB5AD', 'Мятный' => 'A8E0CB', 'Аквамарин' => '6FD6C6', 'Морская волна' => '1A8C8C',
            'Петроль (сине-зелёный)' => '1B5E6B', 'Тиффани' => '7FD9D0', 'Ментол' => 'C4EBDD',
        ],
        'blue' => [
            'Голубой' => '8EC5E8', 'Светло-голубой' => 'CFE6F5', 'Небесный' => '79B8E8', 'Пыльно-голубой' => '9DB3C6',
            'Лазурный' => '2D9CDB', 'Васильковый' => '5B8DEF', 'Джинсовый' => '5F86B3', 'Светлый деним' => 'A7BFD9',
            'Серенити' => '92A8D1',
        ],
        'navy' => [
            'Синий' => '1F4FA3', 'Тёмно-синий' => '1B2A4E', 'Нэви (морской синий)' => '16233F', 'Королевский синий' => '2447B5',
            'Электрик' => '1E5BFF', 'Кобальтовый' => '1B47A1', 'Индиго' => '2F3D7E', 'Сапфировый' => '14418B',
            'Чернильный' => '1A1F45', 'Тёмный деним' => '2B3E5C', 'Ультрамарин' => '2A2FBF',
        ],
        'purple' => [
            'Фиолетовый' => '7B3FA0', 'Сиреневый' => 'C3A6D8', 'Лавандовый' => 'B7A8E0', 'Лиловый' => 'B48BC9',
            'Сливовый' => '5E2B5C', 'Баклажановый' => '432A47', 'Пурпурный' => '8E1F7A', 'Аметистовый' => '8B5FBF',
            'Глициния' => 'C6B3E6', 'Ежевичный' => '4A2545', 'Виноградный' => '6A3A7C', 'Орхидея' => 'C77BC0',
        ],
        'metallic' => [
            'Золотой' => 'D4AF37', 'Серебряный' => 'C0C0C8', 'Бронзовый' => '9C6B30', 'Розовое золото' => 'C9917F',
            'Медный' => 'B87333', 'Платиновый' => 'DADBDD', 'Оружейная сталь (ганметалл)' => '55595E',
        ],
        'multi' => [
            'Разноцветный' => null, 'Чёрно-белый' => ['111111', 'FFFFFF'], 'Сине-белый' => ['1F4FA3', 'FFFFFF'],
            'Красно-белый' => ['D7263D', 'FFFFFF'], 'Красно-чёрный' => ['D7263D', '111111'], 'Бежево-чёрный' => ['E8D5B7', '111111'],
            'Розово-белый' => ['F28CB1', 'FFFFFF'], 'Серо-белый' => ['8E9196', 'FFFFFF'], 'Чёрно-золотой' => ['111111', 'D4AF37'],
            'Бежево-белый' => ['E8D5B7', 'FFFFFF'], 'Сине-красный' => ['1F4FA3', 'D7263D'], 'Зелёно-белый' => ['2E8B57', 'FFFFFF'],
        ],
    ];

    public function up()
    {
        Schema::create('wardrobe_colors', function (Blueprint $table) {
            $table->id();
            $table->integer('user_id')->nullable(); // null — общий цвет, иначе свой цвет пользователя
            $table->string('group', 30)->default('other');
            $table->string('name', 150);
            $table->string('hex', 7)->nullable();  // #RRGGBB; null — без образца ("Разноцветный")
            $table->string('hex2', 7)->nullable(); // второй цвет двухцветных ("Чёрно-белый")
            $table->timestamps();

            $table->index(['user_id', 'name']);
        });

        $now = now();
        $rows = [];
        foreach (self::STARTER_COLORS as $group => $colors) {
            foreach ($colors as $name => $hex) {
                $pair = (array)$hex;
                $rows[] = [
                    'user_id' => null, 'group' => $group, 'name' => $name,
                    'hex' => isset($pair[0]) ? '#' . $pair[0] : null,
                    'hex2' => isset($pair[1]) ? '#' . $pair[1] : null,
                    'created_at' => $now, 'updated_at' => $now,
                ];
            }
        }
        DB::table('wardrobe_colors')->insert($rows);

        Schema::table('wardrobe_items', function (Blueprint $table) {
            $table->unsignedBigInteger('color_id')->nullable()->after('color');
            $table->index('color_id');
        });
        Schema::table('post_clothes', function (Blueprint $table) {
            $table->unsignedBigInteger('color_id')->nullable()->after('color');
            $table->index('color_id');
        });

        // общий цвет по названию без учёта регистра; "Нюдовый (телесный)" находится и по "нюдовый", и по "телесный"
        $shared = [];
        foreach (DB::table('wardrobe_colors')->whereNull('user_id')->orderByDesc('id')->get(['id', 'name']) as $color) {
            $name = mb_strtolower($color->name);
            $shared[$name] = $color->id;
            if (preg_match('/^(.+?)\s*\((.+)\)$/u', $name, $matches)) {
                $shared[trim($matches[1])] = $color->id;
                $shared[trim($matches[2])] = $color->id;
            }
        }
        $own = []; // "user_id|название" => id

        $resolve = function ($userId, $text) use (&$shared, &$own, $now) {
            $name = trim((string)$text);
            if ($name === '') {
                return null;
            }
            $key = str_replace('ё', 'е', mb_strtolower($name));
            foreach ($shared as $sharedName => $id) {
                if (str_replace('ё', 'е', $sharedName) === $key) {
                    return $id;
                }
            }

            $ownKey = $userId . '|' . mb_strtolower($name);
            if (!isset($own[$ownKey])) {
                $own[$ownKey] = DB::table('wardrobe_colors')->insertGetId([
                    'user_id' => $userId, 'group' => 'other', 'name' => mb_substr($name, 0, 150), 'created_at' => $now, 'updated_at' => $now,
                ]);
            }

            return $own[$ownKey];
        };

        foreach (DB::table('wardrobe_items')->whereNotNull('color')->get(['id', 'user_id', 'color']) as $item) {
            DB::table('wardrobe_items')->where('id', $item->id)->update(['color_id' => $resolve($item->user_id, $item->color)]);
        }

        $clothes = DB::table('post_clothes')
            ->join('posts', 'posts.id', '=', 'post_clothes.post_id')
            ->whereNotNull('post_clothes.color')
            ->get(['post_clothes.id', 'posts.user_id', 'post_clothes.color']);
        foreach ($clothes as $row) {
            DB::table('post_clothes')->where('id', $row->id)->update(['color_id' => $resolve($row->user_id, $row->color)]);
        }

        Schema::table('wardrobe_items', function (Blueprint $table) {
            $table->dropColumn('color');
        });
        Schema::table('post_clothes', function (Blueprint $table) {
            $table->dropColumn('color');
        });
    }

    public function down()
    {
        Schema::table('wardrobe_items', function (Blueprint $table) {
            $table->string('color', 100)->nullable()->after('color_id');
        });
        Schema::table('post_clothes', function (Blueprint $table) {
            $table->string('color', 100)->nullable()->after('color_id');
        });

        $names = DB::table('wardrobe_colors')->pluck('name', 'id');
        foreach (['wardrobe_items', 'post_clothes'] as $tableName) {
            foreach (DB::table($tableName)->whereNotNull('color_id')->get(['id', 'color_id']) as $row) {
                DB::table($tableName)->where('id', $row->id)->update(['color' => isset($names[$row->color_id]) ? mb_substr($names[$row->color_id], 0, 100) : null]);
            }

            Schema::table($tableName, function (Blueprint $table) {
                $table->dropIndex(['color_id']);
                $table->dropColumn('color_id');
            });
        }

        Schema::dropIfExists('wardrobe_colors');
    }
}
