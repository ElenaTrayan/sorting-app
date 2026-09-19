<?php

namespace App\Http\Controllers\Admin\Stock;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\Store;
use App\Models\StockSetting;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class SettingsController extends Controller
{
    public function index()
    {
        $settings = StockSetting::forUser(Auth::id());
        $stores = Store::where('user_id', Auth::id())->orderBy('name')->get();

        return view('admin.stock.settings', [
            'settings' => $settings,
            'stores' => $stores,
            'notificationKeys' => StockSetting::NOTIFICATION_KEYS,
            'categories' => Product::CATEGORIES,
        ]);
    }

    public function update(Request $request)
    {
        $data = $request->validate([
            'currency' => 'required|string|max:10',
            'region' => 'nullable|string|max:100',
            'preferred_store_ids' => 'nullable|array',
            'preferred_store_ids.*' => 'integer',
            'email_integration_enabled' => 'nullable|boolean',
            'notifications' => 'nullable|array',
        ]);

        // чекбоксы уведомлений: то, что не отмечено, вообще не приходит в запросе —
        // поэтому явно считаем false для всех ключей, кроме присланных как true
        $notifications = array_merge(
            array_fill_keys(array_keys(StockSetting::NOTIFICATION_KEYS), false),
            $data['notifications'] ?? []
        );

        $settings = StockSetting::forUser(Auth::id());
        $settings->update([
            'currency' => $data['currency'],
            'region' => $data['region'] ?? null,
            'preferred_store_ids' => json_encode($data['preferred_store_ids'] ?? []),
            'email_integration_enabled' => $request->boolean('email_integration_enabled'),
            'notifications' => json_encode($notifications),
        ]);

        return response()->json(['success' => true]);
    }
}
