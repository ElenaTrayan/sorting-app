<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Настройки внешнего API, заданные админом из панели (вкл/выкл и ключи).
 * Какие интеграции существуют — config/integrations.php; читать значения нужно
 * через App\Services\Integrations\IntegrationManager, а не напрямую отсюда.
 */
class ApiIntegration extends Model
{
    protected $table = 'api_integrations';

    protected $fillable = [
        'key',
        'is_enabled',
        'credentials',
    ];

    protected $casts = [
        'is_enabled' => 'boolean',
        'credentials' => 'encrypted:array',
    ];
}
