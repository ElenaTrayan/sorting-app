<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// Настройки внешних API, которыми админ управляет из панели. Список интеграций — config/integrations.php.
class CreateApiIntegrationsTable extends Migration
{
    public function up()
    {
        Schema::create('api_integrations', function (Blueprint $table) {
            $table->id();
            $table->string('key', 50)->unique();
            $table->boolean('is_enabled')->default(true);
            $table->text('credentials')->nullable(); // зашифрованный json: {поле: значение}
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('api_integrations');
    }
}
