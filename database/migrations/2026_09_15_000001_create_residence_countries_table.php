<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Локальная база стран (GeoNames countryInfo.txt) — источник переводов для поля
 * "Место жительства" в форме контакта, чтобы не зависеть от языка внешних
 * геосервисов (Photon/Nominatim) и не терять русские/английские названия.
 * См. также residence_cities и App\Console\Commands\ImportResidenceGeoData.
 */
return new class extends Migration
{
    public function up()
    {
        Schema::create('residence_countries', function (Blueprint $table) {
            $table->unsignedInteger('geoname_id')->primary();
            $table->char('iso_code', 2)->unique();
            $table->string('name');
            $table->string('name_en')->nullable();
            $table->string('name_ru')->nullable();
        });
    }

    public function down()
    {
        Schema::dropIfExists('residence_countries');
    }
};
