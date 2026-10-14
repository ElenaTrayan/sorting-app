<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// Корзина постов: удалённый пост не стирается сразу, а помечается временем удаления (posts.deleted_at,
// SoftDeletes у модели Post) и лежит в корзине App\Components\PostTrash::KEEP_DAYS дней — оттуда его
// можно вернуть. Данные не меняются: у всех существующих постов deleted_at = NULL
class AddDeletedAtToPostsTable extends Migration
{
    public function up()
    {
        Schema::table('posts', function (Blueprint $table) {
            $table->softDeletes();
        });
    }

    public function down()
    {
        Schema::table('posts', function (Blueprint $table) {
            $table->dropSoftDeletes();
        });
    }
}
