<?php

use App\Models\Hashtag;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

// Направление связанных хештегов: строка (hashtag_id, related_hashtag_id) с auto_add = 1 — "выбрали hashtag_id
// на форме поста — related_hashtag_id добавляется к посту сам", 0 — только предлагается. Обратная строка пары
// настраивается отдельно (страница хештега). Существующим связям ставим то же, что раньше вычислялось на лету
// по числу постов (Hashtag::defaultAutoAdd): общий хештег у конкретного — добавлять, наоборот — предлагать
class AddAutoAddToHashtagRelationsTable extends Migration
{
    public function up()
    {
        Schema::table('hashtag_relations', function (Blueprint $table) {
            $table->boolean('auto_add')->default(false);
        });

        foreach (DB::table('hashtag_relations')->get() as $relation) {
            DB::table('hashtag_relations')
                ->where('hashtag_id', $relation->hashtag_id)
                ->where('related_hashtag_id', $relation->related_hashtag_id)
                ->update(['auto_add' => Hashtag::defaultAutoAdd((int)$relation->hashtag_id, (int)$relation->related_hashtag_id)]);
        }
    }

    public function down()
    {
        Schema::table('hashtag_relations', function (Blueprint $table) {
            $table->dropColumn('auto_add');
        });
    }
}
