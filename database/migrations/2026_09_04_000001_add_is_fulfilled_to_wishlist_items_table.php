<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddIsFulfilledToWishlistItemsTable extends Migration
{
    public function up()
    {
        Schema::table('wishlist_items', function (Blueprint $table) {
            // "забронировано" (is_reserved, гость) и "исполнено" (is_fulfilled, владелец) —
            // разные, независимые статусы: гость может забронировать, а владелец отдельно
            // отмечает, что подарок реально куплен/получен
            $table->boolean('is_fulfilled')->default(false)->after('is_reserved');
        });
    }

    public function down()
    {
        Schema::table('wishlist_items', function (Blueprint $table) {
            $table->dropColumn('is_fulfilled');
        });
    }
}
