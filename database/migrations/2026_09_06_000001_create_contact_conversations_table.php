<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateContactConversationsTable extends Migration
{
    public function up()
    {
        Schema::create('contact_conversations', function (Blueprint $table) {
            $table->id();
            $table->integer('contact_id');
            $table->text('image'); // скрин переписки — {name, extension, path}, как у contact_photos
            $table->string('caption', 255)->nullable(); // например, "Telegram, март 2026", необязательно
            $table->timestamps();

            $table->index('contact_id');
        });
    }

    public function down()
    {
        Schema::dropIfExists('contact_conversations');
    }
}
