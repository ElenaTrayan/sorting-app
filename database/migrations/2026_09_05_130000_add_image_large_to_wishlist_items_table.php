<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddImageLargeToWishlistItemsTable extends Migration
{
    public function up()
    {
        Schema::table('wishlist_items', function (Blueprint $table) {
            // отдельный, более крупный вариант картинки — для просмотра "поближе" в попапе
            // "Подробнее" (клик по фото); "image" остаётся компактным — для карточки в
            // списке и превью при добавлении, чтобы не гонять там лишний вес
            $table->text('image_large')->nullable()->after('image');
        });
    }

    public function down()
    {
        Schema::table('wishlist_items', function (Blueprint $table) {
            $table->dropColumn('image_large');
        });
    }
}
