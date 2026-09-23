<?php

namespace App\Services\Integrations;

use App\Models\ApiIntegration;

/**
 * Единая точка доступа к внешним API: включена ли интеграция и какие у неё ключи.
 * Порядок ключей: значение, заданное админом в панели → значение из config/.env
 * (config/integrations.php, поле 'config'). Если строки в api_integrations нет —
 * интеграция считается включённой.
 */
class IntegrationManager
{
    /** @var array<string, ApiIntegration|null> */
    private $rows = [];

    /** Описания всех интеграций из config/integrations.php. */
    public function definitions(): array
    {
        return config('integrations', []);
    }

    public function definition(string $key): ?array
    {
        return $this->definitions()[$key] ?? null;
    }

    public function isEnabled(string $key): bool
    {
        $row = $this->row($key);

        return $row ? $row->is_enabled : true;
    }

    /** Значение ключа интеграции (из панели, иначе из config/.env). */
    public function credential(string $key, string $field): ?string
    {
        $stored = $this->storedCredential($key, $field);
        if ($stored !== null) {
            return $stored;
        }

        $configPath = $this->definition($key)['fields'][$field]['config'] ?? null;
        $value = $configPath ? config($configPath) : null;

        return $value === '' ? null : $value;
    }

    /** Откуда сейчас берётся значение: 'panel', 'env' или null (не задано). */
    public function credentialSource(string $key, string $field): ?string
    {
        if ($this->storedCredential($key, $field) !== null) {
            return 'panel';
        }

        return $this->credential($key, $field) !== null ? 'env' : null;
    }

    /**
     * Сохранить настройки из панели.
     *
     * @param array<string, string|null> $credentials новые значения полей; пустое/null — оставить как есть
     * @param string[] $clear поля, значения которых из панели нужно стереть (вернуться к .env)
     */
    public function save(string $key, bool $isEnabled, array $credentials = [], array $clear = []): ApiIntegration
    {
        $row = ApiIntegration::firstOrNew(['key' => $key]);
        $stored = $row->credentials ?: [];

        foreach ($this->definition($key)['fields'] ?? [] as $field => $meta) {
            if (in_array($field, $clear, true)) {
                unset($stored[$field]);
            } elseif (isset($credentials[$field]) && trim($credentials[$field]) !== '') {
                $stored[$field] = trim($credentials[$field]);
            }
        }

        $row->is_enabled = $isEnabled;
        $row->credentials = $stored ?: null;
        $row->save();

        unset($this->rows[$key]);

        return $row;
    }

    private function storedCredential(string $key, string $field): ?string
    {
        $row = $this->row($key);
        $value = ($row && is_array($row->credentials)) ? ($row->credentials[$field] ?? null) : null;

        return ($value === null || $value === '') ? null : $value;
    }

    private function row(string $key): ?ApiIntegration
    {
        if (!array_key_exists($key, $this->rows)) {
            $this->rows[$key] = ApiIntegration::where('key', $key)->first();
        }

        return $this->rows[$key];
    }
}
