<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateStoresTable extends Migration
{
    public function up()
    {
        Schema::create('stores', function (Blueprint $table) {
            $table->id();
            $table->integer('user_id');
            $table->string('name', 150);
            $table->string('logo', 255)->nullable();
            $table->string('type', 50)->nullable(); // supermarket/pharmacy/marketplace/other
            $table->text('categories')->nullable(); // json: ["продукты", "бытовая химия", ...]
            $table->boolean('delivery')->default(false);
            $table->boolean('pickup')->default(false);
            $table->string('website', 255)->nullable();
            $table->boolean('monitor_promotions')->default(true);
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('stores');
    }
}
