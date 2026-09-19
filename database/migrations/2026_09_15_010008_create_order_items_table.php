<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateOrderItemsTable extends Migration
{
    public function up()
    {
        Schema::create('order_items', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('order_id');
            $table->unsignedBigInteger('product_id')->nullable();
            $table->string('name', 255);
            $table->string('brand', 150)->nullable();
            $table->decimal('quantity', 8, 2)->default(1);
            $table->string('unit', 20)->default('шт');
            $table->decimal('price', 10, 2)->nullable();
            $table->boolean('added_to_stock')->default(false);
            $table->timestamps();

            $table->index('order_id');
        });
    }

    public function down()
    {
        Schema::dropIfExists('order_items');
    }
}
