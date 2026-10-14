<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// "Когда и с чем носить" — у вещей "Моего гардероба" и у пинов "Одежда" (кнопка "В гардероб" переносит поля в вещь):
// длина (текст, подсказки WardrobeItem::LENGTHS), сезон (JSON-массив ключей WardrobeOutfit::SEASONS),
// комфортная температура от/до (как у образов), повод (JSON-массив ключей WardrobeItem::OCCASIONS).
// wardrobe_item_compat — с чем вещь сочетается: пара вещей гардероба, одна строка на пару (item_id < related_id),
// связь двусторонняя. В форме и карточке пары делятся на "обувь / аксессуары / остальное" по категории второй
// вещи (WardrobeItem::compatGroup) — отдельно это не хранится.
class AddWearDetailsToWardrobeItemsAndPostClothes extends Migration
{
    public function up()
    {
        foreach (['wardrobe_items' => 'size', 'post_clothes' => 'kind'] as $tableName => $after) {
            Schema::table($tableName, function (Blueprint $table) use ($after) {
                $table->string('length', 50)->nullable()->after($after);
                $table->text('seasons')->nullable()->after('length');
                $table->smallInteger('temp_min')->nullable()->after('seasons');
                $table->smallInteger('temp_max')->nullable()->after('temp_min');
                $table->text('occasions')->nullable()->after('temp_max');
            });
        }

        Schema::create('wardrobe_item_compat', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('item_id');
            $table->unsignedBigInteger('related_id');
            $table->timestamps();

            $table->unique(['item_id', 'related_id']);
            $table->index('related_id');
        });
    }

    public function down()
    {
        Schema::dropIfExists('wardrobe_item_compat');

        foreach (['wardrobe_items', 'post_clothes'] as $tableName) {
            Schema::table($tableName, function (Blueprint $table) {
                $table->dropColumn(['length', 'seasons', 'temp_min', 'temp_max', 'occasions']);
            });
        }
    }
}
