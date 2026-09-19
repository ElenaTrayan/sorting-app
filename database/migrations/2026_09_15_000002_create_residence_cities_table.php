<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Локальная база городов (GeoNames cities5000 — весь мир, населённые пункты от
 * ~5000 жителей) для автоподсказок в поле "Место жительства". Основной источник
 * подсказок теперь она (быстро, всегда на русском/английском), внешние геосервисы
 * (Photon/Nominatim) остаются как подстраховка для городов, которых тут нет —
 * см. ContactController::searchResidence.
 */
return new class extends Migration
{
    public function up()
    {
        Schema::create('residence_cities', function (Blueprint $table) {
            $table->unsignedInteger('geoname_id')->primary();
            $table->string('name');
            $table->string('name_ascii')->nullable();
            $table->string('name_en')->nullable();
            $table->string('name_ru')->nullable();
            $table->char('country_code', 2)->nullable();
            $table->unsignedInteger('population')->default(0);

            $table->index('name_en');
            $table->index('name_ru');
            $table->index('population');
            $table->index('country_code');
        });
    }

    public function down()
    {
        Schema::dropIfExists('residence_cities');
    }
};
