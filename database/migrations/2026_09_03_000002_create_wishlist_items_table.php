<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateWishlistItemsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('wishlist_items', function (Blueprint $table) {
            $table->id();
            $table->integer('wishlist_id');
            $table->string('title', 255);
            $table->string('url', 1000)->nullable(); // ссылка на товар в магазине
            $table->decimal('price', 10, 2)->nullable();
            $table->string('category', 100)->nullable(); // см. config/wishlist.php categories
            $table->text('image')->nullable(); // {name, extension, path} — как small/medium/original_image у постов, но один размер
            $table->boolean('is_reserved')->default(false);
            $table->string('reserved_by', 255)->nullable(); // имя гостя, необязательно — вводится при резервировании
            $table->timestamps();

            $table->index('wishlist_id');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('wishlist_items');
    }
}
