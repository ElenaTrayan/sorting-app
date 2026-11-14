<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// Характеристики товара для пина "Товар" — их подставляет "Заполнить по ссылке" из блока "Характеристики"
// страницы магазина (App\Parsers\ProductSpecsParser). Отдельные колонки, а не JSON: по ним фильтровать
// и потом переносить в вещь "Инвентаризации"
class AddSpecsToPostProductsTable extends Migration
{
    public function up()
    {
        Schema::table('post_products', function (Blueprint $table) {
            $table->string('brand_country', 100)->nullable()->after('country'); // страна регистрации бренда
            $table->string('weight', 50)->nullable()->after('variant'); // как на сайте: "520 г", "1.2 кг"
            $table->string('shelf_life', 100)->nullable()->after('expires_at'); // срок хранения текстом: "24 місяці"
            $table->text('storage_conditions')->nullable()->after('shelf_life');
        });
    }

    public function down()
    {
        Schema::table('post_products', function (Blueprint $table) {
            $table->dropColumn(['brand_country', 'weight', 'shelf_life', 'storage_conditions']);
        });
    }
}
