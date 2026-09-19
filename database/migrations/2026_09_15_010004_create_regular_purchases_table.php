<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// "Постоянные покупки" — товары, которые пользователь регулярно покупает.
class CreateRegularPurchasesTable extends Migration
{
    public function up()
    {
        Schema::create('regular_purchases', function (Blueprint $table) {
            $table->id();
            $table->integer('user_id');
            $table->unsignedBigInteger('product_id');
            $table->unsignedBigInteger('preferred_store_id')->nullable();
            $table->decimal('avg_price', 10, 2)->nullable();
            $table->decimal('last_price', 10, 2)->nullable();
            $table->date('last_purchased_at')->nullable();
            $table->integer('frequency_days')->nullable(); // примерная частота покупки, в днях
            $table->string('preference', 20)->default('normal'); // favorite/normal/alternative/avoid
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('regular_purchases');
    }
}
