<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// Раздел "Мои запасы": места хранения / категории списка запасов
// (Холодильник, Морозилка, Кладовка, Аптечка, Таблетница, Косметика, Уборка, Зоотовары, Другое).
// Заводятся дефолтным набором на пользователя при первом обращении (см. StockLocation::defaultsFor()).
class CreateStockLocationsTable extends Migration
{
    public function up()
    {
        Schema::create('stock_locations', function (Blueprint $table) {
            $table->id();
            $table->integer('user_id');
            $table->string('name', 100);
            $table->string('slug', 100);
            $table->string('icon', 50)->nullable(); // ключ иконки fontawesome, см. layouts/parts/sidebar_icon.blade.php
            $table->integer('sort_order')->default(0);
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('stock_locations');
    }
}
