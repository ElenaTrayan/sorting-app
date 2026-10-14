<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// Личные настройки ИИ пользователя ("Настройки ИИ" в кабинете): свой ключ OpenAI для подбора
// хештегов по картинке. Расходы на API идут на счёт пользователя в OpenAI, а не на ключ сайта.
class CreateUserAiSettingsTable extends Migration
{
    public function up()
    {
        Schema::create('user_ai_settings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->unique()->constrained()->cascadeOnDelete();
            $table->string('provider', 20)->default('openai');
            $table->text('api_key')->nullable(); // зашифрован (cast 'encrypted')
            $table->string('model', 50)->nullable();
            $table->string('tags_mode', 10)->default('suggest'); // suggest | auto
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('user_ai_settings');
    }
}
