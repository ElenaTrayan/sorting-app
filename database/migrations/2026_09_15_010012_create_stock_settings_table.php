<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// Настройки раздела "Мои запасы" — одна строка на пользователя.
class CreateStockSettingsTable extends Migration
{
    public function up()
    {
        Schema::create('stock_settings', function (Blueprint $table) {
            $table->id();
            $table->integer('user_id')->unique();
            $table->text('notifications')->nullable(); // json: {low_stock, expiry, price_drop, order_status, order_ready, return_deadline, weekly_summary}
            $table->string('currency', 10)->default('UAH');
            $table->string('region', 100)->nullable();
            $table->text('preferred_store_ids')->nullable(); // json-массив id магазинов
            $table->boolean('email_integration_enabled')->default(false);
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('stock_settings');
    }
}
