<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// Акции в магазинах пользователя. source = manual (добавлено вручную) | mock (демо-данные
// от MockPriceOfferProvider — заготовка под реальный парсер/API магазинов в будущем).
class CreatePromotionsTable extends Migration
{
    public function up()
    {
        Schema::create('promotions', function (Blueprint $table) {
            $table->id();
            $table->integer('user_id');
            $table->unsignedBigInteger('product_id')->nullable();
            $table->unsignedBigInteger('store_id');
            $table->string('title', 255);
            $table->string('image', 255)->nullable();
            $table->decimal('price', 10, 2);
            $table->decimal('old_price', 10, 2)->nullable();
            $table->integer('discount_percent')->nullable();
            $table->string('availability', 30)->default('in_stock'); // in_stock/limited/out_of_stock
            $table->string('weight_volume', 50)->nullable();
            $table->date('valid_until')->nullable();
            $table->string('url', 500)->nullable();
            $table->string('source', 20)->default('manual'); // manual/mock
            $table->timestamps();

            $table->index(['user_id', 'store_id']);
        });
    }

    public function down()
    {
        Schema::dropIfExists('promotions');
    }
}
