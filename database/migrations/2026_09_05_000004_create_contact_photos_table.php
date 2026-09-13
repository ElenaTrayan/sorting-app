<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateContactPhotosTable extends Migration
{
    public function up()
    {
        Schema::create('contact_photos', function (Blueprint $table) {
            $table->id();
            $table->integer('contact_id');
            $table->text('image'); // {name, extension, path}
            $table->string('caption', 255)->nullable(); // например, "скрин из инстаграм", необязательно
            $table->timestamps();

            $table->index('contact_id');
        });
    }

    public function down()
    {
        Schema::dropIfExists('contact_photos');
    }
}
