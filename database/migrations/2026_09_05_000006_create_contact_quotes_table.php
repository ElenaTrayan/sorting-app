<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateContactQuotesTable extends Migration
{
    public function up()
    {
        Schema::create('contact_quotes', function (Blueprint $table) {
            $table->id();
            $table->integer('contact_id');
            $table->text('quote'); // высказывание
            $table->timestamps();

            $table->index('contact_id');
        });
    }

    public function down()
    {
        Schema::dropIfExists('contact_quotes');
    }
}
