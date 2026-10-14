<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// Состав ткани с процентами ("хлопок 95%, эластан 5%") — свободный текст рядом с материалом из справочника:
// у вещей "Моего гардероба" и у пинов "Одежда"
class AddCompositionToWardrobeItemsAndPostClothes extends Migration
{
    public function up()
    {
        foreach (['wardrobe_items', 'post_clothes'] as $tableName) {
            Schema::table($tableName, function (Blueprint $table) {
                $table->string('composition', 255)->nullable()->after('material_id');
            });
        }
    }

    public function down()
    {
        foreach (['wardrobe_items', 'post_clothes'] as $tableName) {
            Schema::table($tableName, function (Blueprint $table) {
                $table->dropColumn('composition');
            });
        }
    }
}
