<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateWishlistsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('wishlists', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique(); // публичная ссылка вида /wishlist/{uuid}, доступна без логина
            $table->integer('user_id'); // владелец вишлиста
            $table->string('title', 255);
            $table->text('description')->nullable();
            $table->string('occasion', 100)->nullable(); // повод (см. config/wishlist.php occasions), либо произвольный текст
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('wishlists');
    }
}
