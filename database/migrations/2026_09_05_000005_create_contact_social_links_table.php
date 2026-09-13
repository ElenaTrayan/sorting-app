<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateContactSocialLinksTable extends Migration
{
    public function up()
    {
        Schema::create('contact_social_links', function (Blueprint $table) {
            $table->id();
            $table->integer('contact_id');
            $table->string('platform', 100); // Instagram, Telegram, VK, Facebook, ... — свободный текст
            $table->string('value', 500); // ссылка или ник
            $table->timestamps();

            $table->index('contact_id');
        });
    }

    public function down()
    {
        Schema::dropIfExists('contact_social_links');
    }
}
