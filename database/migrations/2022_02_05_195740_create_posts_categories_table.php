<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreatePostsCategoriesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('posts_categories', function (Blueprint $table) {
            $table->id();
            $table->string('title', 255);
            $table->string('alias', 255)->unique();
            $table->integer('parent_id')->default(0); //id родителя
            $table->integer('user_id'); //id пользователя, создавшего раздел для постов
            $table->enum('status', ['not_active', 'active', 'remote'])->default('active'); //активен, удален
            $table->integer('sort')->default(0);
            $table->text('short_description')->nullable(); //короткое описание раздела
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('posts_categories');
    }
}
