<?php

namespace App\Http\Controllers\Admin\Stock;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\Promotion;
use App\Models\ReserveItem;
use App\Models\ShoppingListItem;
use App\Models\Store;
use App\Models\StockItem;
use App\Models\StockLocation;
use Illuminate\Support\Facades\Auth;

class OverviewController extends Controller
{
    public function index()
    {
        $userId = Auth::id();
        StockLocation::ensureDefaultsFor($userId);

        $expiredCount = StockItem::where('user_id', $userId)->expired()->count();
        $soonCount = StockItem::where('user_id', $userId)->expiringWithinDays(StockItem::SOON_DAYS)->count();
        $monthCount = StockItem::where('user_id', $userId)
            ->whereNotNull('expires_at')
            ->whereBetween('expires_at', [now()->addDays(StockItem::SOON_DAYS + 1)->startOfDay(), now()->addDays(StockItem::MONTH_DAYS)->endOfDay()])
            ->count();
        $shoppingListCount = ShoppingListItem::where('user_id', $userId)->where('is_purchased', false)->count();

        $categories = StockLocation::where('user_id', $userId)
            ->withCount('items')
            ->orderBy('sort_order')
            ->get();

        $expiringItems = StockItem::where('user_id', $userId)
            ->whereNotNull('expires_at')
            ->where('expires_at', '<=', now()->addDays(StockItem::MONTH_DAYS))
            ->with(['location', 'product'])
            ->orderBy('expires_at')
            ->limit(6)
            ->get();

        $shoppingList = ShoppingListItem::where('user_id', $userId)
            ->where('is_purchased', false)
            ->latest()
            ->limit(5)
            ->get();

        $promotions = Promotion::where('user_id', $userId)
            ->whereNotNull('discount_percent')
            ->where(function ($q) {
                $q->whereNull('valid_until')->orWhere('valid_until', '>=', now());
            })
            ->with(['store', 'product'])
            ->orderByDesc('discount_percent')
            ->limit(4)
            ->get();

        $stores = Store::where('user_id', $userId)->limit(4)->get();

        $reserveItems = ReserveItem::where('user_id', $userId)->get();
        $reserveReadiness = $reserveItems->isEmpty()
            ? null
            : (int) min(100, round(
                ($reserveItems->sum('minimum_qty') > 0
                    ? $reserveItems->sum(fn ($i) => min($i->current_qty, $i->minimum_qty)) / $reserveItems->sum('minimum_qty')
                    : 1) * 100
            ));
        $reserveNeedsRestock = $reserveItems->filter->needs_restock->count();

        $ordersNeedingAttention = Order::where('user_id', $userId)
            ->with('store')
            ->get()
            ->filter->needs_attention
            ->take(3);

        return view('admin.stock.overview', compact(
            'expiredCount',
            'soonCount',
            'monthCount',
            'shoppingListCount',
            'categories',
            'expiringItems',
            'shoppingList',
            'promotions',
            'stores',
            'reserveReadiness',
            'reserveNeedsRestock',
            'ordersNeedingAttention'
        ));
    }
}
