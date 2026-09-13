<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddPriorityToWishlistItemsTable extends Migration
{
    public function up()
    {
        Schema::table('wishlist_items', function (Blueprint $table) {
            // насколько сильно хочется именно это желание — slug из config('wishlist.priorities'),
            // либо null ("приоритет не указан")
            $table->string('priority', 50)->nullable()->after('category');
        });
    }

    public function down()
    {
        Schema::table('wishlist_items', function (Blueprint $table) {
            $table->dropColumn('priority');
        });
    }
}
