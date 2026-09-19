<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// История покупок — записи создаются автоматически при получении заказа
// (Order::delivery_status = delivered) и вручную для оффлайн-покупок.
class CreatePurchaseHistoryEntriesTable extends Migration
{
    public function up()
    {
        Schema::create('purchase_history_entries', function (Blueprint $table) {
            $table->id();
            $table->integer('user_id');
            $table->unsignedBigInteger('order_id')->nullable();
            $table->unsignedBigInteger('store_id')->nullable();
            $table->text('items_summary')->nullable(); // json-снимок позиций: [{name, brand, quantity, unit, price}]
            $table->decimal('total_amount', 10, 2)->default(0);
            $table->string('payment_method', 50)->nullable();
            $table->string('category', 50)->nullable();
            $table->date('purchased_at');
            $table->boolean('had_discount')->default(false);
            $table->timestamps();

            $table->index(['user_id', 'purchased_at']);
        });
    }

    public function down()
    {
        Schema::dropIfExists('purchase_history_entries');
    }
}
