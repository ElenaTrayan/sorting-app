<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// Товары в запасах пользователя ("Мои запасы"): продукты, аптечка, таблетница,
// косметика, бытовая химия, зоотовары и т.д. Статус срока годности — вычисляемый
// accessor в модели (см. StockItem::getExpiryStatusAttribute()).
class CreateStockItemsTable extends Migration
{
    public function up()
    {
        Schema::create('stock_items', function (Blueprint $table) {
            $table->id();
            $table->integer('user_id');
            $table->unsignedBigInteger('product_id')->nullable();
            $table->unsignedBigInteger('location_id')->nullable();
            $table->unsignedBigInteger('store_id')->nullable();
            $table->unsignedBigInteger('order_item_id')->nullable(); // если товар пришёл из заказа ("Добавить в запасы")
            $table->string('name', 255); // дублируем название/бренд на случай ручного ввода без product_id
            $table->string('brand', 150)->nullable();
            $table->string('category', 50)->default('product'); // product/medicine/cosmetics/household/pet/other
            $table->decimal('quantity', 8, 2)->default(1);
            $table->string('unit', 20)->default('шт');
            $table->date('purchased_at')->nullable();
            $table->date('expires_at')->nullable();
            $table->decimal('price', 10, 2)->nullable();
            $table->text('note')->nullable();
            $table->string('image', 255)->nullable();
            $table->timestamps();

            $table->index(['user_id', 'location_id']);
            $table->index(['user_id', 'expires_at']);
        });
    }

    public function down()
    {
        Schema::dropIfExists('stock_items');
    }
}
