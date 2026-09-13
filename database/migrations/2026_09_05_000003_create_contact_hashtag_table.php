<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateContactHashtagTable extends Migration
{
    public function up()
    {
        Schema::create('contact_hashtag', function (Blueprint $table) {
            $table->id();
            $table->integer('contact_id');
            $table->integer('hashtag_id');
            $table->timestamps();

            $table->unique(['contact_id', 'hashtag_id']);
        });
    }

    public function down()
    {
        Schema::dropIfExists('contact_hashtag');
    }
}
