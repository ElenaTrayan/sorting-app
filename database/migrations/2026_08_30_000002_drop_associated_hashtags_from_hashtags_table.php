<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class DropAssociatedHashtagsFromHashtagsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        // Колонка не использовалась (write-путь был закомментирован, данных в ней нет) —
        // связанные хештеги теперь хранятся в таблице hashtag_relations
        Schema::table('hashtags', function (Blueprint $table) {
            $table->dropColumn('associated_hashtags');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('hashtags', function (Blueprint $table) {
            $table->string('associated_hashtags')->nullable();
        });
    }
}
