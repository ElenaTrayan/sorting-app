<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateShoppingListItemsTable extends Migration
{
    public function up()
    {
        Schema::create('shopping_list_items', function (Blueprint $table) {
            $table->id();
            $table->integer('user_id');
            $table->unsignedBigInteger('product_id')->nullable();
            $table->string('name', 255);
            $table->decimal('quantity', 8, 2)->default(1);
            $table->string('unit', 20)->default('шт');
            $table->text('note')->nullable();
            $table->string('source', 20)->default('manual'); // manual/regular/order/low_stock/reserve/promotion
            $table->boolean('is_purchased')->default(false);
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('shopping_list_items');
    }
}
