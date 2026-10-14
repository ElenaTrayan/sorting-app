<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// Вещи пользователя ("Мой гардероб"): то, что уже есть (status = owned), и то, что нужно купить
// (status = wishlist — обычно с картинкой из поста, см. WardrobeItemController::imageFromPost()).
// Списки значений category/wear_level/styles/body_types — константы модели WardrobeItem.
class CreateWardrobeItemsTable extends Migration
{
    public function up()
    {
        Schema::create('wardrobe_items', function (Blueprint $table) {
            $table->id();
            $table->integer('user_id');
            $table->unsignedBigInteger('post_id')->nullable(); // пост, из которого взята картинка
            $table->string('name', 255);
            $table->string('category', 30)->default('other');
            $table->string('status', 20)->default('owned'); // owned / wishlist
            $table->text('image')->nullable(); // {name, extension, path} — как у contact_photos
            $table->string('brand', 150)->nullable();
            $table->string('color', 100)->nullable();
            $table->string('size', 50)->nullable();
            $table->string('print', 100)->nullable();
            $table->string('material', 255)->nullable();
            $table->text('care')->nullable(); // особенности ухода
            $table->string('wear_level', 20)->nullable(); // степень изношенности
            $table->boolean('needs_repair')->default(false);
            $table->text('repair_note')->nullable(); // какой именно ремонт нужен
            $table->text('styles')->nullable(); // JSON-массив ключей стилей
            $table->text('body_types')->nullable(); // JSON-массив: кому подходит (драматик, романтик...)
            $table->decimal('price', 10, 2)->nullable();
            $table->string('shop_url', 1000)->nullable(); // где купить
            $table->text('note')->nullable();
            $table->timestamps();

            $table->index(['user_id', 'status']);
            $table->index(['user_id', 'category']);
        });
    }

    public function down()
    {
        Schema::dropIfExists('wardrobe_items');
    }
}
