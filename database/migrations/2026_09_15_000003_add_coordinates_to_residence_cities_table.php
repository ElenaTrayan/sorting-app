<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Координаты центра города — чтобы под полем "Место жительства" в форме контакта
 * можно было сразу показать метку на карте (см. ContactController::searchResidence
 * и residence_lat/residence_lng на contacts).
 */
return new class extends Migration
{
    public function up()
    {
        Schema::table('residence_cities', function (Blueprint $table) {
            $table->decimal('lat', 9, 6)->nullable()->after('population');
            $table->decimal('lng', 9, 6)->nullable()->after('lat');
        });
    }

    public function down()
    {
        Schema::table('residence_cities', function (Blueprint $table) {
            $table->dropColumn(['lat', 'lng']);
        });
    }
};
