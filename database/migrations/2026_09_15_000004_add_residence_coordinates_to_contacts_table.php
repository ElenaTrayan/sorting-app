<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Координаты выбранного места жительства (центр города — см. residence_cities) —
 * чтобы показать метку на карте под полем "Место жительства" в форме контакта.
 * Заполняются вместе с текстом поля при выборе подсказки; при ручном наборе без
 * выбора из списка остаются пустыми (карта в этом случае просто не показывается).
 */
return new class extends Migration
{
    public function up()
    {
        Schema::table('contacts', function (Blueprint $table) {
            $table->decimal('residence_lat', 9, 6)->nullable()->after('residence');
            $table->decimal('residence_lng', 9, 6)->nullable()->after('residence_lat');
        });
    }

    public function down()
    {
        Schema::table('contacts', function (Blueprint $table) {
            $table->dropColumn(['residence_lat', 'residence_lng']);
        });
    }
};
