<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\UserService;
use App\Services\Integrations\IntegrationManager;

/**
 * Главная админ-панели: сводка по пользователям, подписке и внешним API.
 */
class HomeController extends Controller
{
    public function index(IntegrationManager $integrations)
    {
        $usersTotal = User::count();
        $usersBlocked = User::whereNotNull('blocked_at')->count();

        // сколько пользователей пользуются каждым сервисом сейчас (не истёк срок)
        $activeCounts = UserService::where(function ($q) {
                $q->whereNull('expires_at')->orWhere('expires_at', '>', now());
            })
            ->selectRaw('service, count(*) as total')
            ->groupBy('service')
            ->pluck('total', 'service');

        $services = [];
        foreach (config('subscription.services') as $key => $service) {
            $services[$key] = $service + ['subscribers' => (int) ($activeCounts[$key] ?? 0)];
        }

        $integrationRows = [];
        foreach ($integrations->definitions() as $key => $definition) {
            $missing = [];
            foreach ($definition['fields'] as $field => $meta) {
                if ($integrations->credential($key, $field) === null) {
                    $missing[] = $meta['label'];
                }
            }
            $integrationRows[$key] = [
                'name' => $definition['name'],
                'enabled' => $integrations->isEnabled($key),
                'missing' => $missing,
            ];
        }

        return view('admin.home.index', [
            'usersTotal' => $usersTotal,
            'usersBlocked' => $usersBlocked,
            'usersActive' => $usersTotal - $usersBlocked,
            'usersNew' => User::where('created_at', '>=', now()->subDays(7))->count(),
            'latestUsers' => User::orderByDesc('id')->limit(5)->get(),
            'services' => $services,
            'integrations' => $integrationRows,
        ]);
    }
}
