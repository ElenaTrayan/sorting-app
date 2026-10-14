<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// Фото места хранения ("Мой гардероб" → Места хранения): {name, extension, path}, как у вещей и образов
class AddImageToWardrobeStoragesTable extends Migration
{
    public function up()
    {
        Schema::table('wardrobe_storages', function (Blueprint $table) {
            $table->text('image')->nullable()->after('name');
        });
    }

    public function down()
    {
        Schema::table('wardrobe_storages', function (Blueprint $table) {
            $table->dropColumn('image');
        });
    }
}
