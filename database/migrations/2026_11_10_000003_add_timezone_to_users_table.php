<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// Часовой пояс пользователя ("Настройки аккаунта") — по нему считается "сегодня" (дата стирки и т. п.).
// Время в БД по-прежнему в UTC. Пусто — пояс по умолчанию (User::DEFAULT_TIMEZONE)
class AddTimezoneToUsersTable extends Migration
{
    public function up()
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('timezone', 64)->nullable()->after('email');
        });
    }

    public function down()
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('timezone');
        });
    }
}
