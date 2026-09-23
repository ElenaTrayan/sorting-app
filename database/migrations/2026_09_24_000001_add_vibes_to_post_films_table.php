<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// "Вайб" фильма — несколько значений из готовых (PostFilm::PRESET_VIBES) или своих, как pros/cons
class AddVibesToPostFilmsTable extends Migration
{
    public function up()
    {
        Schema::table('post_films', function (Blueprint $table) {
            $table->json('vibes')->nullable()->after('description'); // ["уютный", "осенний", ...]
        });
    }

    public function down()
    {
        Schema::table('post_films', function (Blueprint $table) {
            $table->dropColumn('vibes');
        });
    }
}
