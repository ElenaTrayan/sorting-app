<?php

namespace App\Http\Controllers\User\Stock;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\Promotion;
use App\Models\ShoppingListItem;
use App\Models\Store;
use App\Services\Stock\PromotionSyncService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class PromotionController extends Controller
{
    public function index(Request $request, PromotionSyncService $syncService)
    {
        $userId = Auth::id();

        // Лёгкий "мониторинг" mock-акций по постоянным покупкам — см. PromotionSyncService.
        $syncService->syncForUser($userId);

        $query = Promotion::where('user_id', $userId)
            ->where(function ($q) {
                $q->whereNull('valid_until')->orWhere('valid_until', '>=', now());
            })
            ->with(['store', 'product']);

        if ($request->filled('store_id')) {
            $query->where('store_id', $request->get('store_id'));
        }
        if ($request->filled('category') && $request->get('category') !== 'all') {
            $query->whereHas('product', fn ($q) => $q->where('category', $request->get('category')));
        }
        if ($request->filled('min_discount')) {
            $query->where('discount_percent', '>=', (int) $request->get('min_discount'));
        }
        if ($request->filled('availability')) {
            $query->where('availability', $request->get('availability'));
        }
        if ($request->filled('max_price')) {
            $query->where('price', '<=', (float) $request->get('max_price'));
        }

        $promotions = $query->get()
            ->sortByDesc(fn (Promotion $p) => [$p->is_for_regular_purchase ? 1 : 0, $p->discount_percent])
            ->values();

        return view('user.stock.promotions', [
            'promotions' => $promotions,
            'stores' => Store::where('user_id', $userId)->orderBy('name')->get(),
            'categories' => Product::CATEGORIES,
            'filters' => $request->only(['store_id', 'category', 'min_discount', 'availability', 'max_price']),
        ]);
    }

    public function sync(PromotionSyncService $syncService)
    {
        $syncService->syncForUser(Auth::id());
        return response()->json(['success' => true]);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'title' => 'required|string|max:255',
            'store_id' => 'required|integer',
            'price' => 'required|numeric|min:0',
            'old_price' => 'nullable|numeric|min:0',
            'availability' => 'required|in:in_stock,limited,out_of_stock',
            'weight_volume' => 'nullable|string|max:50',
            'valid_until' => 'nullable|date',
            'url' => 'nullable|string|max:500',
        ]);

        $discount = null;
        if (!empty($data['old_price']) && $data['old_price'] > 0) {
            $discount = (int) round((1 - $data['price'] / $data['old_price']) * 100);
        }

        Promotion::create($data + [
            'user_id' => Auth::id(),
            'discount_percent' => $discount,
            'source' => 'manual',
        ]);

        return response()->json(['success' => true]);
    }

    public function destroy(Promotion $promotion)
    {
        abort_unless($promotion->user_id === Auth::id(), 403);
        $promotion->delete();
        return response()->json(['success' => true]);
    }

    public function addToShoppingList(Promotion $promotion)
    {
        abort_unless($promotion->user_id === Auth::id(), 403);

        ShoppingListItem::create([
            'user_id' => $promotion->user_id,
            'product_id' => $promotion->product_id,
            'name' => $promotion->title,
            'quantity' => 1,
            'unit' => 'шт',
            'source' => 'promotion',
        ]);

        return response()->json(['success' => true]);
    }
}
