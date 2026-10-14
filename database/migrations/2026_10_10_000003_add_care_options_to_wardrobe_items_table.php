<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// Особенности ухода за вещью — отмеченные пункты из списка (JSON-массив ключей WardrobeItem::CARE_OPTIONS,
// как styles и body_types). Текстовое поле care остаётся — для того, чего нет в списке.
class AddCareOptionsToWardrobeItemsTable extends Migration
{
    public function up()
    {
        Schema::table('wardrobe_items', function (Blueprint $table) {
            $table->text('care_options')->nullable()->after('care');
        });
    }

    public function down()
    {
        Schema::table('wardrobe_items', function (Blueprint $table) {
            $table->dropColumn('care_options');
        });
    }
}
