<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// Стирка вещей "Моего гардероба" (страница "Стирка", WardrobeItem::WASH_GROUPS / SPECIAL_WASH_TYPES):
// дата последней стирки, группа стирки (пусто — подбирается сама по уходу, типу и цвету) и особая стирка —
// пятна, которые надо выводить отдельно, с отметкой "исправлено"
class AddLaundryToWardrobeItemsTable extends Migration
{
    public function up()
    {
        Schema::table('wardrobe_items', function (Blueprint $table) {
            $table->date('washed_at')->nullable()->after('repair_note');
            $table->string('wash_group', 20)->nullable()->after('washed_at'); // ключ WardrobeItem::WASH_GROUPS
            $table->boolean('special_wash')->default(false)->after('wash_group');
            $table->text('special_wash_types')->nullable()->after('special_wash'); // JSON: ключи SPECIAL_WASH_TYPES
            $table->text('special_wash_note')->nullable()->after('special_wash_types');
            $table->boolean('special_wash_fixed')->default(false)->after('special_wash_note');
        });
    }

    public function down()
    {
        Schema::table('wardrobe_items', function (Blueprint $table) {
            $table->dropColumn(['washed_at', 'wash_group', 'special_wash', 'special_wash_types', 'special_wash_note', 'special_wash_fixed']);
        });
    }
}
