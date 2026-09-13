<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateContactFactsTable extends Migration
{
    public function up()
    {
        Schema::create('contact_facts', function (Blueprint $table) {
            $table->id();
            $table->integer('contact_id');
            $table->text('fact'); // произвольный факт о человеке
            $table->timestamps();

            $table->index('contact_id');
        });
    }

    public function down()
    {
        Schema::dropIfExists('contact_facts');
    }
}
