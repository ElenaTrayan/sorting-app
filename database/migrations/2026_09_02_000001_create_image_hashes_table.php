<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateImageHashesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        // Перцептивные хеши оригинальных изображений постов — для поиска дублей
        // (medium/small не хешируются отдельно: они производные от original,
        // достаточно хранить и сравнивать хеш только оригинала)
        Schema::create('image_hashes', function (Blueprint $table) {
            $table->id();
            $table->integer('post_id');
            $table->string('image_name'); // ключ в original_image JSON у поста
            $table->string('path');
            $table->unsignedInteger('width');
            $table->unsignedInteger('height');
            $table->string('phash', 128); // двоичная строка (Hash::toBits())
            $table->timestamps();

            $table->index('post_id');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('image_hashes');
    }
}
