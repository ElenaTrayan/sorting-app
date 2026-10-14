<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// Материалы фильма: любой пост можно прикрепить к пину "Фильм / Сериал" (блок "Материалы" на странице фильма).
// film_post_id — id поста-фильма, film_material_kind — раздел внутри фильма (Post::MATERIAL_*: кадр / промо;
// "Видео" отдельно не хранится — определяется по наличию видео в посте).
// Внешний ключ не заводим: при удалении фильма материалы открепляются в Post::booted()
class AddFilmPostIdToPostsTable extends Migration
{
    public function up()
    {
        Schema::table('posts', function (Blueprint $table) {
            $table->unsignedBigInteger('film_post_id')->nullable()->after('type')->index();
            $table->string('film_material_kind', 20)->nullable()->after('film_post_id');
        });
    }

    public function down()
    {
        Schema::table('posts', function (Blueprint $table) {
            $table->dropIndex(['film_post_id']);
            $table->dropColumn(['film_post_id', 'film_material_kind']);
        });
    }
}
