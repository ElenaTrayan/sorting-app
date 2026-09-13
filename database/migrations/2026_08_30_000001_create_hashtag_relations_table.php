<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateHashtagRelationsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        // Связь "связанные хештеги" (many-to-many, сам на себя).
        // Хранится симметрично: если добавили hashtag_id => related_hashtag_id,
        // то в паре с ней всегда создаётся и обратная строка related_hashtag_id => hashtag_id
        // (см. Hashtag::attachRelated()) — так belongsToMany() отдаёт полный список в любую сторону
        // без OR/UNION в запросах.
        Schema::create('hashtag_relations', function (Blueprint $table) {
            $table->integer('hashtag_id');
            $table->integer('related_hashtag_id');
            $table->primary(['hashtag_id', 'related_hashtag_id']);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('hashtag_relations');
    }
}
