<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// Вещь "Моего гардероба": страна производства, откуда вещь появилась ("Купила в стоке", "Отдала сестра") и фасон
// ("Оверсайз") — текстом: значение из списка (WardrobeItem::COUNTRIES / ORIGINS / FITS) или своё;
// особенности носки ("Видны пятна пота", "Под бюстгальтер без бретелей") — JSON-массив ключей
// WardrobeItem::FEATURES и своё — текстом
class AddItemDetailsToWardrobeItemsTable extends Migration
{
    public function up()
    {
        Schema::table('wardrobe_items', function (Blueprint $table) {
            $table->string('country', 60)->nullable()->after('brand_id');
            $table->string('origin', 100)->nullable()->after('country');
            $table->string('fit', 50)->nullable()->after('length');
            $table->text('features')->nullable()->after('fit');
            $table->text('features_note')->nullable()->after('features');
        });
    }

    public function down()
    {
        Schema::table('wardrobe_items', function (Blueprint $table) {
            $table->dropColumn(['country', 'origin', 'fit', 'features', 'features_note']);
        });
    }
}
