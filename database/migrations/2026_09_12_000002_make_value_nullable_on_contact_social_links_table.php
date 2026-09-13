<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class MakeValueNullableOnContactSocialLinksTable extends Migration
{
    public function up()
    {
        Schema::table('contact_social_links', function (Blueprint $table) {
            // "Ссылка на аккаунт" в поп-апе добавления соцсети — необязательное поле
            $table->string('value', 500)->nullable()->change();
        });
    }

    public function down()
    {
        Schema::table('contact_social_links', function (Blueprint $table) {
            $table->string('value', 500)->nullable(false)->change();
        });
    }
}
