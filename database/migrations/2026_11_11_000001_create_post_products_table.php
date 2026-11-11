<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// Данные товара для пинов типа "Товар" (страница создания поста — блок #tab-product, редактирования — карточка
// "Товар"). Отдельной таблицей 1:1, как post_films / post_clothes. Первый шаг к сервису "Инвентаризация вещей":
// поля задуманы так, чтобы потом переноситься в вещь инвентаризации, как пин "Одежда" — в гардероб.
// wishlist_item_id / shopping_list_item_id / stock_item_id — куда товар уже отправлен кнопками на странице поста
class CreatePostProductsTable extends Migration
{
    public function up()
    {
        Schema::create('post_products', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('post_id')->unique();

            $table->string('category', 100)->nullable(); // подсказки PostProduct::CATEGORIES, можно своё
            $table->string('brand', 150)->nullable();
            $table->string('manufacturer', 150)->nullable();
            $table->string('country', 100)->nullable(); // страна производства
            $table->string('variant', 150)->nullable(); // цвет / объём / размер: "50 мл", "128 ГБ"
            $table->string('article', 100)->nullable(); // артикул или штрихкод

            $table->string('status', 20)->nullable(); // ключ PostProduct::STATUSES
            $table->decimal('price', 12, 2)->nullable();
            $table->string('currency', 3)->nullable(); // ключ PostProduct::CURRENCIES
            $table->string('store', 150)->nullable();
            $table->string('shop_url', 1000)->nullable();
            $table->string('promo', 255)->nullable(); // промокод / где была скидка

            $table->date('purchased_at')->nullable();
            $table->date('expires_at')->nullable(); // срок годности (годен до)
            $table->unsignedSmallInteger('warranty_months')->nullable(); // гарантия, месяцев (от даты покупки)

            // моё мнение
            $table->unsignedTinyInteger('my_rating')->nullable(); // 1–5
            $table->text('my_opinion')->nullable();
            $table->text('my_pros')->nullable();
            $table->text('my_cons')->nullable();

            // отзывы людей — сводка: средняя оценка и число отзывов, что хвалят, что ругают, где читать
            $table->decimal('reviews_rating', 2, 1)->nullable(); // 0.0–5.0
            $table->unsignedInteger('reviews_count')->nullable();
            $table->text('reviews_pros')->nullable();
            $table->text('reviews_cons')->nullable();
            $table->string('reviews_url', 1000)->nullable();

            $table->unsignedBigInteger('wishlist_item_id')->nullable();
            $table->unsignedBigInteger('shopping_list_item_id')->nullable();
            $table->unsignedBigInteger('stock_item_id')->nullable();

            $table->timestamps();

            $table->index('category');
            $table->index('status');
        });
    }

    public function down()
    {
        Schema::dropIfExists('post_products');
    }
}
