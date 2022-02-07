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
            $table->string('title', 255);
            $table->string('alias')->unique();
            $table->integer('user_id'); //id пользователя, создавшего пост
            $table->integer('category_id'); //страница-родитель (в какой категории находится)
            $table->enum('status', ['not_active', 'active', 'remote'])->default('active'); //активен, удален
            $table->boolean('is_used')->default(0); //использован ли материал
            $table->text('content')->nullable();
            $table->string('small_image', 300)->nullable();
            $table->string('medium_image', 300)->nullable();
            $table->string('large_image', 300)->nullable();
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
