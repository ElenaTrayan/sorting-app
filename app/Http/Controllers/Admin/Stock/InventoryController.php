<?php

namespace App\Http\Controllers\Admin\Stock;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\RegularPurchase;
use App\Models\ShoppingListItem;
use App\Models\StockItem;
use App\Models\StockLocation;
use App\Models\Store;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class InventoryController extends Controller
{
    public function index(Request $request)
    {
        $userId = Auth::id();
        StockLocation::ensureDefaultsFor($userId);

        $locations = StockLocation::where('user_id', $userId)->orderBy('sort_order')->get();
        $stores = Store::where('user_id', $userId)->orderBy('name')->get();

        $query = StockItem::where('user_id', $userId)->with(['location', 'product', 'store']);

        if ($request->filled('location') && $request->get('location') !== 'all') {
            $query->whereHas('location', fn ($q) => $q->where('slug', $request->get('location')));
        }
        if ($request->filled('search')) {
            $search = $request->get('search');
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")->orWhere('brand', 'like', "%{$search}%");
            });
        }
        if ($request->filled('brand')) {
            $query->where('brand', $request->get('brand'));
        }
        if ($request->filled('category')) {
            $query->where('category', $request->get('category'));
        }
        if ($request->filled('store_id')) {
            $query->where('store_id', $request->get('store_id'));
        }
        if ($request->filled('date_from')) {
            $query->whereDate('expires_at', '>=', $request->get('date_from'));
        }
        if ($request->filled('date_to')) {
            $query->whereDate('expires_at', '<=', $request->get('date_to'));
        }

        $items = $query->orderBy('expires_at')->get();

        if ($request->filled('status')) {
            $items = $items->filter(fn (StockItem $item) => $item->expiry_status === $request->get('status'))->values();
        }

        $brands = StockItem::where('user_id', $userId)->whereNotNull('brand')->distinct()->orderBy('brand')->pluck('brand');

        return view('admin.stock.inventory', [
            'items' => $items,
            'locations' => $locations,
            'stores' => $stores,
            'brands' => $brands,
            'categories' => Product::CATEGORIES,
            'activeLocation' => $request->get('location', 'all'),
            'filters' => $request->only(['search', 'brand', 'category', 'status', 'store_id', 'date_from', 'date_to']),
        ]);
    }

    public function store(Request $request)
    {
        $data = $this->validateData($request);
        $userId = Auth::id();

        $product = Product::findOrCreateMatching($userId, [
            'name' => $data['name'],
            'brand' => $data['brand'] ?? null,
            'category' => $data['category'],
        ]);

        $storeId = $this->resolveStoreId($userId, $data['store_name'] ?? null);

        StockItem::create([
            'user_id' => $userId,
            'product_id' => $product->id,
            'location_id' => $data['location_id'],
            'store_id' => $storeId,
            'name' => $data['name'],
            'brand' => $data['brand'] ?? null,
            'category' => $data['category'],
            'quantity' => $data['quantity'],
            'unit' => $data['unit'],
            'purchased_at' => $data['purchased_at'] ?? null,
            'expires_at' => $data['expires_at'] ?? null,
            'price' => $data['price'] ?? null,
            'note' => $data['note'] ?? null,
        ]);

        return response()->json(['success' => true]);
    }

    public function update(Request $request, StockItem $item)
    {
        $this->authorizeItem($item);
        $data = $this->validateData($request);
        $storeId = $this->resolveStoreId(Auth::id(), $data['store_name'] ?? null);

        $item->update([
            'location_id' => $data['location_id'],
            'store_id' => $storeId,
            'name' => $data['name'],
            'brand' => $data['brand'] ?? null,
            'category' => $data['category'],
            'quantity' => $data['quantity'],
            'unit' => $data['unit'],
            'purchased_at' => $data['purchased_at'] ?? null,
            'expires_at' => $data['expires_at'] ?? null,
            'price' => $data['price'] ?? null,
            'note' => $data['note'] ?? null,
        ]);

        return response()->json(['success' => true]);
    }

    public function destroy(StockItem $item)
    {
        $this->authorizeItem($item);
        $item->delete();
        return response()->json(['success' => true]);
    }

    // "Сохранить как постоянную покупку" — связка StockItem → Product → RegularPurchase
    public function saveAsRegular(StockItem $item)
    {
        $this->authorizeItem($item);

        if (!$item->product_id) {
            $item->product_id = Product::findOrCreateMatching($item->user_id, [
                'name' => $item->name,
                'brand' => $item->brand,
                'category' => $item->category,
            ])->id;
            $item->save();
        }

        RegularPurchase::updateOrCreate(
            ['user_id' => $item->user_id, 'product_id' => $item->product_id],
            [
                'preferred_store_id' => $item->store_id,
                'last_price' => $item->price,
                'avg_price' => $item->price,
                'last_purchased_at' => $item->purchased_at,
                'preference' => 'normal',
            ]
        );

        return response()->json(['success' => true]);
    }

    // "Добавить в список покупок" — когда запас на исходе
    public function addToShoppingList(StockItem $item)
    {
        $this->authorizeItem($item);

        ShoppingListItem::create([
            'user_id' => $item->user_id,
            'product_id' => $item->product_id,
            'name' => $item->name,
            'quantity' => 1,
            'unit' => $item->unit,
            'source' => 'low_stock',
        ]);

        return response()->json(['success' => true]);
    }

    protected function authorizeItem(StockItem $item)
    {
        abort_unless($item->user_id === Auth::id(), 403);
    }

    protected function resolveStoreId(int $userId, ?string $storeName): ?int
    {
        if (!$storeName) {
            return null;
        }
        return Store::firstOrCreate(['user_id' => $userId, 'name' => $storeName])->id;
    }

    protected function validateData(Request $request): array
    {
        return $request->validate([
            'name' => 'required|string|max:255',
            'brand' => 'nullable|string|max:150',
            'category' => 'required|string|max:50',
            'location_id' => 'required|integer',
            'quantity' => 'required|numeric|min:0',
            'unit' => 'required|string|max:20',
            'purchased_at' => 'nullable|date',
            'expires_at' => 'nullable|date',
            'price' => 'nullable|numeric|min:0',
            'store_name' => 'nullable|string|max:150',
            'note' => 'nullable|string|max:2000',
        ]);
    }
}
