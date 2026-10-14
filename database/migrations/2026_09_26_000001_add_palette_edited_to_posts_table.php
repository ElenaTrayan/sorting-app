<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// Палитру поправили вручную (страница редактирования поста, блок "Палитра") — такую палитру не пересчитываем
// автоматически, пока не сменится обложка (Post::booted) и не трогает posts:backfill-palettes
class AddPaletteEditedToPostsTable extends Migration
{
    public function up()
    {
        Schema::table('posts', function (Blueprint $table) {
            $table->boolean('palette_edited')->default(false)->after('palette');
        });
    }

    public function down()
    {
        Schema::table('posts', function (Blueprint $table) {
            $table->dropColumn('palette_edited');
        });
    }
}
