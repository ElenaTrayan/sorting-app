<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// Теги высказывания (поле "Теги" в поп-апе добавления) — те же хештеги, что и везде
// в проекте, просто привязанные к конкретному высказыванию, а не к посту/контакту.
class CreateContactQuoteHashtagTable extends Migration
{
    public function up()
    {
        Schema::create('contact_quote_hashtag', function (Blueprint $table) {
            $table->id();
            $table->integer('contact_quote_id');
            $table->integer('hashtag_id');
            $table->timestamps();

            $table->unique(['contact_quote_id', 'hashtag_id']);
        });
    }

    public function down()
    {
        Schema::dropIfExists('contact_quote_hashtag');
    }
}
