<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

// Подписка: какие сервисы (разделы кабинета) включены пользователю. Список сервисов — config/subscription.php.
class CreateUserServicesTable extends Migration
{
    public function up()
    {
        Schema::create('user_services', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id');
            $table->string('service', 50);
            $table->timestamp('expires_at')->nullable(); // null — без срока
            $table->timestamps();

            $table->unique(['user_id', 'service']);
        });

        // до появления подписки все существующие пользователи пользовались всеми разделами —
        // чтобы никто не потерял доступ, включаем им все сервисы
        $now = now();
        $rows = [];
        foreach (DB::table('users')->pluck('id') as $userId) {
            foreach (array_keys(config('subscription.services')) as $service) {
                $rows[] = ['user_id' => $userId, 'service' => $service, 'created_at' => $now, 'updated_at' => $now];
            }
        }
        foreach (array_chunk($rows, 500) as $chunk) {
            DB::table('user_services')->insert($chunk);
        }
    }

    public function down()
    {
        Schema::dropIfExists('user_services');
    }
}
