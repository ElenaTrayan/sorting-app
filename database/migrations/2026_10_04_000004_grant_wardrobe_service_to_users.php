<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

// "Мой гардероб" — новый сервис подписки (config/subscription.php). Новые пользователи получают его
// при регистрации (default_services), а существующим включаем здесь — как было со всеми разделами
// при появлении подписки (см. create_user_services_table).
class GrantWardrobeServiceToUsers extends Migration
{
    public function up()
    {
        $now = now();
        $withService = DB::table('user_services')->where('service', 'wardrobe')->pluck('user_id')->all();

        $rows = [];
        foreach (DB::table('users')->whereNotIn('id', $withService)->pluck('id') as $userId) {
            $rows[] = ['user_id' => $userId, 'service' => 'wardrobe', 'created_at' => $now, 'updated_at' => $now];
        }
        foreach (array_chunk($rows, 500) as $chunk) {
            DB::table('user_services')->insert($chunk);
        }
    }

    public function down()
    {
        DB::table('user_services')->where('service', 'wardrobe')->delete();
    }
}
