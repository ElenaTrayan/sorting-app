<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

/**
 * Пускает в раздел кабинета только тех, у кого этот сервис включён в подписке
 * (админ включает его в карточке пользователя). Использование: ->middleware('service:stock').
 * Ключи сервисов — config/subscription.php.
 */
class EnsureServiceEnabled
{
    public function handle(Request $request, Closure $next, string $service)
    {
        $user = $request->user();

        if ($user && $user->hasService($service)) {
            return $next($request);
        }

        $name = config("subscription.services.{$service}.name", $service);
        $message = "Раздел «{$name}» не входит в вашу подписку.";

        if ($request->expectsJson()) {
            return response()->json(['status' => false, 'message' => $message], 403);
        }

        return redirect()->route('cabinet.home')->with('service_denied', $message);
    }
}
