<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\Integrations\IntegrationManager;
use Illuminate\Http\Request;

/**
 * Админка → Внешние API: включение/выключение интеграций и их ключи.
 * Список интеграций — config/integrations.php, значения — IntegrationManager.
 */
class IntegrationController extends Controller
{
    /** @var IntegrationManager */
    private $integrations;

    public function __construct(IntegrationManager $integrations)
    {
        $this->integrations = $integrations;
    }

    public function index()
    {
        $rows = [];
        foreach ($this->integrations->definitions() as $key => $definition) {
            $rows[$key] = $definition + [
                'enabled' => $this->integrations->isEnabled($key),
                'sources' => $this->sources($key, $definition),
            ];
        }

        return view('admin.integrations.index', ['integrations' => $rows]);
    }

    public function edit(string $key)
    {
        $definition = $this->integrations->definition($key);
        abort_if($definition === null, 404);

        return view('admin.integrations.edit', [
            'key' => $key,
            'integration' => $definition,
            'enabled' => $this->integrations->isEnabled($key),
            'sources' => $this->sources($key, $definition),
        ]);
    }

    public function update(Request $request, string $key)
    {
        $definition = $this->integrations->definition($key);
        abort_if($definition === null, 404);

        $data = $request->validate([
            'is_enabled' => 'nullable|boolean',
            'credentials' => 'nullable|array',
            'credentials.*' => 'nullable|string|max:1000',
            'clear' => 'nullable|array',
            'clear.*' => 'string',
        ]);

        $this->integrations->save(
            $key,
            (bool) ($data['is_enabled'] ?? false),
            $data['credentials'] ?? [],
            $data['clear'] ?? []
        );

        return redirect()->route('admin.integrations.index')->withSuccess('Настройки «' . $definition['name'] . '» сохранены');
    }

    /** @return array<string, string|null> поле => 'panel' | 'env' | null */
    private function sources(string $key, array $definition): array
    {
        $sources = [];
        foreach ($definition['fields'] as $field => $meta) {
            $sources[$field] = $this->integrations->credentialSource($key, $field);
        }

        return $sources;
    }
}
