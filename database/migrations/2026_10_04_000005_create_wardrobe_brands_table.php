<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

// Бренды одежды ("Мой гардероб"): общий список для всех пользователей (user_id = null, ведёт админ —
// админка → Бренды одежды) и свои бренды пользователя (user_id). У вещи вместо текстового brand —
// brand_id; то, что уже было вписано текстом, переносится: совпало с общим — общий, иначе свой.
class CreateWardrobeBrandsTable extends Migration
{
    private const STARTER_BRANDS = [
        'Zara', 'H&M', 'Mango', 'Bershka', 'Pull&Bear', 'Stradivarius', 'Massimo Dutti', 'Oysho',
        'Reserved', 'Sinsay', 'House', 'Cropp', 'Mohito', 'COS', '& Other Stories', 'Uniqlo',
        'Calzedonia', 'Intimissimi', 'Tezenis', 'Orsay', 'C&A', 'Primark', 'Next', 'Gap', 'Benetton',
        'LC Waikiki', 'Colin\'s', 'Medicine', 'Answear', 'Marks & Spencer', 'Marc O\'Polo', 'Esprit',
        'Levi\'s', 'Tommy Hilfiger', 'Calvin Klein', 'Guess', 'Lacoste', 'Ralph Lauren', 'Max Mara',
        'Michael Kors', 'Furla', 'Nike', 'Adidas', 'Puma', 'New Balance', 'Reebok', 'Converse', 'Vans',
        'Dr. Martens', 'Timberland', 'Ecco', 'Geox',
    ];

    public function up()
    {
        Schema::create('wardrobe_brands', function (Blueprint $table) {
            $table->id();
            $table->integer('user_id')->nullable(); // null — общий бренд, иначе свой бренд пользователя
            $table->string('name', 150);
            $table->timestamps();

            $table->index(['user_id', 'name']);
        });

        $now = now();
        DB::table('wardrobe_brands')->insert(array_map(function ($name) use ($now) {
            return ['user_id' => null, 'name' => $name, 'created_at' => $now, 'updated_at' => $now];
        }, self::STARTER_BRANDS));

        Schema::table('wardrobe_items', function (Blueprint $table) {
            $table->unsignedBigInteger('brand_id')->nullable()->after('image');
            $table->index('brand_id');
        });

        // общий бренд по названию без учёта регистра
        $shared = [];
        foreach (DB::table('wardrobe_brands')->whereNull('user_id')->get(['id', 'name']) as $brand) {
            $shared[mb_strtolower($brand->name)] = $brand->id;
        }

        $own = []; // "user_id|название" => id
        foreach (DB::table('wardrobe_items')->whereNotNull('brand')->get(['id', 'user_id', 'brand']) as $item) {
            $name = trim($item->brand);
            if ($name === '') {
                continue;
            }
            $key = mb_strtolower($name);

            if (isset($shared[$key])) {
                $brandId = $shared[$key];
            } else {
                $ownKey = $item->user_id . '|' . $key;
                if (!isset($own[$ownKey])) {
                    $own[$ownKey] = DB::table('wardrobe_brands')->insertGetId([
                        'user_id' => $item->user_id, 'name' => $name, 'created_at' => $now, 'updated_at' => $now,
                    ]);
                }
                $brandId = $own[$ownKey];
            }

            DB::table('wardrobe_items')->where('id', $item->id)->update(['brand_id' => $brandId]);
        }

        Schema::table('wardrobe_items', function (Blueprint $table) {
            $table->dropColumn('brand');
        });
    }

    public function down()
    {
        Schema::table('wardrobe_items', function (Blueprint $table) {
            $table->string('brand', 150)->nullable()->after('image');
        });

        $names = DB::table('wardrobe_brands')->pluck('name', 'id');
        foreach (DB::table('wardrobe_items')->whereNotNull('brand_id')->get(['id', 'brand_id']) as $item) {
            DB::table('wardrobe_items')->where('id', $item->id)->update(['brand' => $names[$item->brand_id] ?? null]);
        }

        Schema::table('wardrobe_items', function (Blueprint $table) {
            $table->dropIndex(['brand_id']);
            $table->dropColumn('brand_id');
        });

        Schema::dropIfExists('wardrobe_brands');
    }
}
