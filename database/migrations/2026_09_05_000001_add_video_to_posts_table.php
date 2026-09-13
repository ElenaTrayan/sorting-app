<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddVideoToPostsTable extends Migration
{
    public function up()
    {
        Schema::table('posts', function (Blueprint $table) {
            // {name, extension, path} — тот же формат, что и у *_image, но без нескольких
            // размеров (видео не пережимаем/не ресайзим, только сохраняем как есть)
            $table->text('video')->nullable()->after('original_image');
        });
    }

    public function down()
    {
        Schema::table('posts', function (Blueprint $table) {
            $table->dropColumn('video');
        });
    }
}
