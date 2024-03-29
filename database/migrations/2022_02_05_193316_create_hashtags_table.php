<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateHashtagsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('hashtags', function (Blueprint $table) {
            $table->id();
            $table->string('title', 150)->unique();
            $table->integer('parent_id')->default(0); // id хэштега-родителя
            $table->integer('user_id'); // id пользователя, создавшего пост
            $table->string('associated_hashtags')->nullable(); // связанные хештеги
            $table->integer('count_posts')->default(0); // количество постов с этим хэштегом=
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('hashtags');
    }
}
