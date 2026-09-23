<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

// Роли admin/user раньше заводились руками (RegisterController::create() делает assignRole('user')
// и падает, если роли нет) — гарантируем, что они существуют.
class EnsureDefaultRoles extends Migration
{
    public function up()
    {
        $now = now();
        foreach (['admin', 'user'] as $name) {
            $exists = DB::table('roles')->where('name', $name)->where('guard_name', 'web')->exists();
            if (!$exists) {
                DB::table('roles')->insert(['name' => $name, 'guard_name' => 'web', 'created_at' => $now, 'updated_at' => $now]);
            }
        }
    }

    public function down()
    {
        // роли могли существовать до миграции — не удаляем
    }
}
