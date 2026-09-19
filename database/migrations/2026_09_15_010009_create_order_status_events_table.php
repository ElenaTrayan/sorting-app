<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// Таймлайн статусов заказа для страницы "Заказ" (детальная).
class CreateOrderStatusEventsTable extends Migration
{
    public function up()
    {
        Schema::create('order_status_events', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('order_id');
            $table->string('status', 50);
            $table->string('note', 255)->nullable();
            $table->dateTime('happened_at');
            $table->timestamps();

            $table->index('order_id');
        });
    }

    public function down()
    {
        Schema::dropIfExists('order_status_events');
    }
}
