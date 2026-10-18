<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// Дата пина — когда это было: день новости, день съёмки фото человека и т.п. (у любого типа пина).
// event_date — сама дата; если известны только месяц или год, это первый день месяца/года, а
// event_date_precision говорит, насколько ей верить: 'day', 'month' или 'year' (поиск по периоду
// сравнивает уже весь месяц/год). Данные не меняются: у всех существующих постов обе колонки NULL
class AddEventDateToPostsTable extends Migration
{
    public function up()
    {
        Schema::table('posts', function (Blueprint $table) {
            $table->date('event_date')->nullable()->after('type')->index();
            $table->string('event_date_precision', 5)->nullable()->after('event_date');
        });
    }

    public function down()
    {
        Schema::table('posts', function (Blueprint $table) {
            $table->dropIndex(['event_date']);
            $table->dropColumn(['event_date', 'event_date_precision']);
        });
    }
}
