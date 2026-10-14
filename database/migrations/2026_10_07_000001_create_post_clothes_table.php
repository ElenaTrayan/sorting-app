<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// Данные вещи для пинов типа "Одежда" (страница создания поста — блок #tab-clothes, редактирования — карточка
// "Одежда"). Отдельной таблицей 1:1, как post_films: у остальных типов пинов эти поля всегда пустые.
// Бренд, стили и "кому подходит" — те же справочники, что у вещей "Моего гардероба" (wardrobe_brands,
// WardrobeItem::STYLES / BODY_TYPES): кнопка "В гардероб" переносит эти поля в форму вещи
class CreatePostClothesTable extends Migration
{
    public function up()
    {
        Schema::create('post_clothes', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('post_id')->unique();
            $table->unsignedBigInteger('brand_id')->nullable(); // wardrobe_brands: общий или свой бренд пользователя
            $table->unsignedSmallInteger('collection_year')->nullable();
            $table->string('kind', 50)->nullable(); // майка, свитер, плащ... — подсказки PostClothes::KINDS, можно своё
            $table->string('color', 100)->nullable();
            $table->string('print', 100)->nullable();
            $table->string('material', 255)->nullable();
            $table->text('styles')->nullable(); // JSON-массив ключей WardrobeItem::STYLES
            $table->text('body_types')->nullable(); // JSON-массив ключей WardrobeItem::BODY_TYPES
            $table->string('shop_url', 1000)->nullable();
            $table->timestamps();

            $table->index('brand_id');
            $table->index('kind');
        });
    }

    public function down()
    {
        Schema::dropIfExists('post_clothes');
    }
}
