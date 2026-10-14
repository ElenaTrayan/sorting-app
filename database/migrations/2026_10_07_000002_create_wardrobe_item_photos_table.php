<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

// Несколько фото у вещи "Моего гардероба". Первое по sort_order — обложка: её копия остаётся в
// wardrobe_items.image (WardrobeItem::syncCover), поэтому карточки, конструктор образов и коллажи,
// которые читают одну картинку вещи, работают как раньше. Существующее фото каждой вещи переносим
// сюда первым — ничего не теряется. Файлы не трогаем: запись ссылается на тот же файл.
class CreateWardrobeItemPhotosTable extends Migration
{
    public function up()
    {
        Schema::create('wardrobe_item_photos', function (Blueprint $table) {
            $table->id();
            // без внешнего ключа, как и везде в проекте: фото (и их файлы) удаляет ItemController::destroy
            $table->unsignedBigInteger('item_id');
            $table->text('image'); // {name, extension, path} — как wardrobe_items.image
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->timestamps();

            $table->index(['item_id', 'sort_order']);
        });

        $now = now();
        DB::table('wardrobe_items')
            ->whereNotNull('image')
            ->where('image', '!=', '')
            ->orderBy('id')
            ->chunkById(500, function ($items) use ($now) {
                DB::table('wardrobe_item_photos')->insert($items->map(function ($item) use ($now) {
                    return [
                        'item_id' => $item->id,
                        'image' => $item->image,
                        'sort_order' => 0,
                        'created_at' => $now,
                        'updated_at' => $now,
                    ];
                })->all());
            });
    }

    public function down()
    {
        // обложка остаётся в wardrobe_items.image; дополнительные фото после отката в интерфейсе не видны
        // (их файлы остаются в storage/app/wardrobe/{user_id})
        Schema::dropIfExists('wardrobe_item_photos');
    }
}
