<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// Образы из вещей гардероба: сам образ, из каких вещей он составлен (wardrobe_outfit_item)
// и куда его надевали (wardrobe_outfit_wears).
class CreateWardrobeOutfitsTable extends Migration
{
    public function up()
    {
        Schema::create('wardrobe_outfits', function (Blueprint $table) {
            $table->id();
            $table->integer('user_id');
            $table->string('name', 255);
            $table->text('image')->nullable(); // своё фото образа; нет — на карточке коллаж из вещей
            $table->text('seasons')->nullable(); // JSON-массив: winter/spring/summer/autumn
            $table->smallInteger('temp_min')->nullable(); // комфортная температура, °C
            $table->smallInteger('temp_max')->nullable();
            $table->text('body_types')->nullable(); // JSON-массив: кому подходит
            $table->text('note')->nullable();
            $table->timestamps();

            $table->index('user_id');
        });

        Schema::create('wardrobe_outfit_item', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('outfit_id');
            $table->unsignedBigInteger('item_id');
            $table->unsignedInteger('sort_order')->default(0);

            $table->unique(['outfit_id', 'item_id']);
            $table->index('item_id');
        });

        Schema::create('wardrobe_outfit_wears', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('outfit_id');
            $table->date('worn_at')->nullable();
            $table->string('place', 255); // куда надевала
            $table->text('note')->nullable();
            $table->timestamps();

            $table->index('outfit_id');
        });
    }

    public function down()
    {
        Schema::dropIfExists('wardrobe_outfit_wears');
        Schema::dropIfExists('wardrobe_outfit_item');
        Schema::dropIfExists('wardrobe_outfits');
    }
}
