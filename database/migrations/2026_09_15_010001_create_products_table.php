<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// Нормализованный каталог товаров — общая сущность, которая связывает
// запасы / постоянные покупки / заказы / акции (см. план "Мои запасы").
// Разные названия одного и того же товара сопоставляются по brand+name+variant+size (+barcode).
class CreateProductsTable extends Migration
{
    public function up()
    {
        Schema::create('products', function (Blueprint $table) {
            $table->id();
            $table->integer('user_id');
            $table->string('name', 255);
            $table->string('brand', 150)->nullable();
            $table->string('variant', 255)->nullable(); // конкретный вариант товара, напр. "Cremette", "Color"
            $table->decimal('size_value', 8, 2)->nullable();
            $table->string('size_unit', 20)->nullable(); // кг, л, мл, г, шт...
            $table->string('category', 50)->default('product'); // product/medicine/cosmetics/household/pet/other
            $table->string('barcode', 64)->nullable();
            $table->string('image', 255)->nullable();
            $table->timestamps();

            $table->index(['user_id', 'brand', 'name']);
        });
    }

    public function down()
    {
        Schema::dropIfExists('products');
    }
}
