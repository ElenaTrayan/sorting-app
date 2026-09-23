<?php

namespace App\Http\Controllers\User\Stock;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\RegularPurchase;
use App\Models\Store;
use App\Services\Stock\PriceOfferService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class RegularPurchaseController extends Controller
{
    public function index(Request $request)
    {
        $userId = Auth::id();

        $query = RegularPurchase::where('user_id', $userId)->with(['product', 'preferredStore']);

        if ($request->filled('preference')) {
            $query->where('preference', $request->get('preference'));
        }

        $regularPurchases = $query->get()->sortBy(fn ($rp) => optional($rp->product)->name);

        return view('user.stock.regular-purchases', [
            'regularPurchases' => $regularPurchases,
            'stores' => Store::where('user_id', $userId)->orderBy('name')->get(),
            'categories' => Product::CATEGORIES,
            'filters' => $request->only('preference'),
        ]);
    }

    public function store(Request $request)
    {
        $data = $this->validateData($request);
        $userId = Auth::id();

        $product = Product::findOrCreateMatching($userId, [
            'name' => $data['name'],
            'brand' => $data['brand'] ?? null,
            'variant' => $data['variant'] ?? null,
            'size_value' => $data['size_value'] ?? null,
            'size_unit' => $data['size_unit'] ?? null,
            'category' => $data['category'],
        ]);

        RegularPurchase::create([
            'user_id' => $userId,
            'product_id' => $product->id,
            'preferred_store_id' => $data['preferred_store_id'] ?? null,
            'avg_price' => $data['avg_price'] ?? null,
            'last_price' => $data['last_price'] ?? null,
            'last_purchased_at' => $data['last_purchased_at'] ?? null,
            'frequency_days' => $data['frequency_days'] ?? null,
            'preference' => $data['preference'],
        ]);

        return response()->json(['success' => true]);
    }

    public function update(Request $request, RegularPurchase $regularPurchase)
    {
        $this->authorizeItem($regularPurchase);
        $data = $this->validateData($request);

        $regularPurchase->product()->update([
            'name' => $data['name'],
            'brand' => $data['brand'] ?? null,
            'variant' => $data['variant'] ?? null,
            'size_value' => $data['size_value'] ?? null,
            'size_unit' => $data['size_unit'] ?? null,
            'category' => $data['category'],
        ]);

        $regularPurchase->update([
            'preferred_store_id' => $data['preferred_store_id'] ?? null,
            'avg_price' => $data['avg_price'] ?? null,
            'last_price' => $data['last_price'] ?? null,
            'last_purchased_at' => $data['last_purchased_at'] ?? null,
            'frequency_days' => $data['frequency_days'] ?? null,
            'preference' => $data['preference'],
        ]);

        return response()->json(['success' => true]);
    }

    public function destroy(RegularPurchase $regularPurchase)
    {
        $this->authorizeItem($regularPurchase);
        $regularPurchase->delete();
        return response()->json(['success' => true]);
    }

    // "Искать лучшее предложение" — см. раздел ТЗ, ключевая фича; реальных парсеров
    // магазинов ещё нет, поэтому данные приходят через PriceOfferService (мок-провайдер
    // за интерфейсом, см. App\Services\Stock\PriceOfferProviderInterface).
    public function searchOffers(RegularPurchase $regularPurchase, PriceOfferService $service)
    {
        $this->authorizeItem($regularPurchase);
        abort_unless($regularPurchase->product, 404);

        $offers = $service->searchBestOffers($regularPurchase->product, Auth::id());

        return response()->json(['success' => true, 'offers' => $offers]);
    }

    protected function authorizeItem(RegularPurchase $regularPurchase)
    {
        abort_unless($regularPurchase->user_id === Auth::id(), 403);
    }

    protected function validateData(Request $request): array
    {
        return $request->validate([
            'name' => 'required|string|max:255',
            'brand' => 'nullable|string|max:150',
            'variant' => 'nullable|string|max:255',
            'size_value' => 'nullable|numeric|min:0',
            'size_unit' => 'nullable|string|max:20',
            'category' => 'required|string|max:50',
            'preferred_store_id' => 'nullable|integer',
            'avg_price' => 'nullable|numeric|min:0',
            'last_price' => 'nullable|numeric|min:0',
            'last_purchased_at' => 'nullable|date',
            'frequency_days' => 'nullable|integer|min:1',
            'preference' => 'required|in:favorite,normal,alternative,avoid',
        ]);
    }
}
