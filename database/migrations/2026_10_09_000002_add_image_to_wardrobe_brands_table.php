<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// Логотип бренда одежды (админка → Бренды одежды): {name, extension, path}, как у образцов принтов и материалов
class AddImageToWardrobeBrandsTable extends Migration
{
    public function up()
    {
        Schema::table('wardrobe_brands', function (Blueprint $table) {
            $table->text('image')->nullable()->after('name');
        });
    }

    public function down()
    {
        Schema::table('wardrobe_brands', function (Blueprint $table) {
            $table->dropColumn('image');
        });
    }
}
