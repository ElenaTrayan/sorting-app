<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

/**
 * Заблокированного админом пользователя сразу разлогиниваем (в том числе посреди сессии)
 * и не пускаем обратно — на любой странице приложения.
 */
class EnsureUserIsNotBlocked
{
    public function handle(Request $request, Closure $next)
    {
        $user = $request->user();

        if ($user && $user->isBlocked()) {
            $reason = $user->blocked_reason;

            Auth::guard()->logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            $message = 'Ваш аккаунт заблокирован администратором.' . ($reason ? ' Причина: ' . $reason : '');

            if ($request->expectsJson()) {
                return response()->json(['status' => false, 'message' => $message], 403);
            }

            return redirect()->route('login')->withErrors(['email' => $message]);
        }

        return $next($request);
    }
}
