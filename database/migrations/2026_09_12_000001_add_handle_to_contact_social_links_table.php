<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddHandleToContactSocialLinksTable extends Migration
{
    public function up()
    {
        Schema::table('contact_social_links', function (Blueprint $table) {
            // раньше в value писали и ссылку, и ник вперемешку — теперь ссылка всегда
            // в value, а ник/название аккаунта отдельно (поп-ап "Добавить социальную сеть")
            $table->string('handle', 255)->nullable()->after('platform');
        });
    }

    public function down()
    {
        Schema::table('contact_social_links', function (Blueprint $table) {
            $table->dropColumn('handle');
        });
    }
}
