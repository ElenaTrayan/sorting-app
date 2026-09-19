<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// Резерв на случай ЧС: минимально необходимое количество vs фактически имеющееся.
class CreateReserveItemsTable extends Migration
{
    public function up()
    {
        Schema::create('reserve_items', function (Blueprint $table) {
            $table->id();
            $table->integer('user_id');
            $table->string('name', 150);
            $table->string('category', 30)->default('other'); // water/food/medicine/hygiene/household/power/other
            $table->string('unit', 20)->default('шт');
            $table->decimal('minimum_qty', 8, 2)->default(0);
            $table->decimal('current_qty', 8, 2)->default(0);
            $table->boolean('is_critical')->default(false);
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('reserve_items');
    }
}
