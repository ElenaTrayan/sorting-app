<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;

/**
 * "Настройки аккаунта" в кабинете. Пока — часовой пояс: по нему считается "сегодня" (User::today —
 * дата стирки в "Моём гардеробе" и т. п.); время в БД остаётся в UTC.
 */
class AccountSettingsController extends Controller
{
    public function index()
    {
        $user = Auth::user();

        return view('user.account-settings.index', [
            'timezone' => $user->timezoneName(),
            'popular' => User::POPULAR_TIMEZONES,
            // остальные пояса по регионам: "Europe" => ["Europe/Amsterdam", ...]
            'others' => collect(timezone_identifiers_list())
                ->reject(function ($timezone) {
                    return isset(User::POPULAR_TIMEZONES[$timezone]);
                })
                ->groupBy(function ($timezone) {
                    return strtok($timezone, '/');
                }),
            'localNow' => now($user->timezoneName()),
        ]);
    }

    public function update(Request $request)
    {
        // с устаревшими названиями (ALL_WITH_BC): в новых версиях PHP Europe/Kiev уходит в них (стал Europe/Kyiv)
        $data = $request->validate([
            'timezone' => ['required', 'string', Rule::in(timezone_identifiers_list(\DateTimeZone::ALL_WITH_BC))],
        ], [
            'timezone.in' => 'Выберите часовой пояс из списка.',
        ]);

        $user = Auth::user();
        $user->timezone = $data['timezone'];
        $user->save();

        return redirect()->route('account-settings.index')
            ->with('success', 'Сохранено. Сейчас у вас ' . now($user->timezoneName())->format('H:i, d.m.Y') . '.');
    }

    /**
     * "UTC+02:00" — смещение пояса сейчас (летом/зимой разное)
     *
     * @param string $timezone
     * @return string
     */
    public static function offsetLabel(string $timezone): string
    {
        return 'UTC' . now($timezone)->format('P');
    }
}
