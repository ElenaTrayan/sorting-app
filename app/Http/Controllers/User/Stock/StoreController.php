<?php

namespace App\Http\Controllers\User\Stock;

use App\Http\Controllers\Controller;
use App\Models\Store;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class StoreController extends Controller
{
    public function index()
    {
        $stores = Store::where('user_id', Auth::id())->withCount('promotions')->orderBy('name')->get();

        return view('user.stock.stores', compact('stores'));
    }

    public function store(Request $request)
    {
        $data = $this->validateData($request);
        $store = new Store($this->prepare($data, $request));
        $store->user_id = Auth::id();
        $store->save();
        return response()->json(['success' => true]);
    }

    public function update(Request $request, Store $store)
    {
        $this->authorizeItem($store);
        $data = $this->validateData($request);
        $store->fill($this->prepare($data, $request));
        $store->save();
        return response()->json(['success' => true]);
    }

    public function destroy(Store $store)
    {
        $this->authorizeItem($store);
        $store->delete();
        return response()->json(['success' => true]);
    }

    public function toggleMonitoring(Store $store)
    {
        $this->authorizeItem($store);
        $store->update(['monitor_promotions' => !$store->monitor_promotions]);
        return response()->json(['success' => true, 'monitor_promotions' => $store->monitor_promotions]);
    }

    protected function prepare(array $data, Request $request): array
    {
        $data['categories_list'] = collect(explode(',', $data['categories'] ?? ''))->map(fn ($c) => trim($c))->filter()->all();
        unset($data['categories']);
        // чекбоксы из FormData не приходят вовсе, если не отмечены — считаем явно
        $data['delivery'] = $request->boolean('delivery');
        $data['pickup'] = $request->boolean('pickup');
        $data['monitor_promotions'] = $request->boolean('monitor_promotions');
        return $data;
    }

    protected function authorizeItem(Store $store)
    {
        abort_unless($store->user_id === Auth::id(), 403);
    }

    protected function validateData(Request $request): array
    {
        return $request->validate([
            'name' => 'required|string|max:150',
            'type' => 'nullable|string|max:50',
            'categories' => 'nullable|string|max:500',
            'delivery' => 'nullable|boolean',
            'pickup' => 'nullable|boolean',
            'website' => 'nullable|string|max:255',
            'monitor_promotions' => 'nullable|boolean',
        ]);
    }
}
