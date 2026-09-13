<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// Скриншоты, приложенные к высказыванию (блок "Изображения (скриншоты)" в поп-апе
// добавления) — та же механика хранения, что и у contact_photos.
class CreateContactQuoteImagesTable extends Migration
{
    public function up()
    {
        Schema::create('contact_quote_images', function (Blueprint $table) {
            $table->id();
            $table->integer('contact_quote_id');
            $table->text('image'); // {name, extension, path}
            $table->timestamps();

            $table->index('contact_quote_id');
        });
    }

    public function down()
    {
        Schema::dropIfExists('contact_quote_images');
    }
}
