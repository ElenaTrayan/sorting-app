<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateOrdersTable extends Migration
{
    public function up()
    {
        Schema::create('orders', function (Blueprint $table) {
            $table->id();
            $table->integer('user_id');
            $table->unsignedBigInteger('store_id')->nullable();
            $table->string('order_number', 100)->nullable();
            $table->date('ordered_at')->nullable();
            $table->decimal('total_amount', 10, 2)->default(0);
            $table->string('payment_status', 20)->default('paid'); // paid/cod (наложенный платёж)
            $table->string('payment_method', 50)->nullable();
            $table->string('delivery_status', 30)->default('pending'); // pending/shipped/ready_for_pickup/delivered/returning/returned/cancelled
            $table->string('fulfillment_method', 20)->default('delivery'); // delivery/pickup
            $table->string('pickup_location', 255)->nullable();
            $table->string('carrier', 100)->nullable();
            $table->string('tracking_number', 100)->nullable();
            $table->date('ready_until')->nullable(); // срок хранения на отделении/до какого числа можно забрать
            $table->string('delivery_window', 100)->nullable(); // напр. "Сегодня, 18:00–20:00"
            $table->date('delivered_at')->nullable();
            $table->date('return_deadline')->nullable();
            $table->string('return_status', 20)->nullable(); // needs_return/in_progress/returned
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index(['user_id', 'delivery_status']);
        });
    }

    public function down()
    {
        Schema::dropIfExists('orders');
    }
}
