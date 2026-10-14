<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// Места хранения одежды ("Мой гардероб"): у каждого пользователя свой список (тремпель, ящик,
// коробка, конкретный органайзер...), у вещи — storage_id. Стартовый набор заводится при первом
// заходе в раздел (WardrobeStorage::ensureDefaultsFor), дальше список правит сам пользователь.
class CreateWardrobeStoragesTable extends Migration
{
    public function up()
    {
        Schema::create('wardrobe_storages', function (Blueprint $table) {
            $table->id();
            $table->integer('user_id');
            $table->string('name', 255);
            $table->text('note')->nullable(); // что это и где стоит: комната, шкаф, артикул...
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();

            $table->index('user_id');
        });

        Schema::table('wardrobe_items', function (Blueprint $table) {
            $table->unsignedBigInteger('storage_id')->nullable()->after('brand_id');
            $table->index('storage_id');
        });
    }

    public function down()
    {
        Schema::table('wardrobe_items', function (Blueprint $table) {
            $table->dropIndex(['storage_id']);
            $table->dropColumn('storage_id');
        });

        Schema::dropIfExists('wardrobe_storages');
    }
}
