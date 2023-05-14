<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreatePostsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('posts', function (Blueprint $table) {
            $table->id();
            $table->string('title', 255)->nullable();
            $table->string('alias', 255)->unique();
            $table->integer('user_id'); //id пользователя, создавшего пост
            $table->integer('category_id'); //страница-родитель (в какой категории находится)
            $table->enum('status', ['not_active', 'active', 'remote'])->default('active'); //активен, удален
            $table->boolean('is_used')->default(0); //использован ли материал
            $table->text('content')->nullable();
            $table->text('small_image')->nullable();
            $table->text('medium_image')->nullable();
            $table->text('original_image')->nullable();
            $table->string('image_alt', 100)->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('posts');
    }
}
