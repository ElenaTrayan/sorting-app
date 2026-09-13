<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddPerceptualHashToImageHashesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('image_hashes', function (Blueprint $table) {
            // второй хеш (PerceptualHash/DCT) — по отдельности каждый из хешей (DifferenceHash
            // или PerceptualHash) слишком часто путает инфографику/карточки с текстом,
            // совпадение требуем по обоим сразу, это резко снижает ложные срабатывания
            $table->string('perceptual_hash', 128)->nullable()->after('phash');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('image_hashes', function (Blueprint $table) {
            $table->dropColumn('perceptual_hash');
        });
    }
}
