<?php

namespace App\Http\Controllers\Admin\Stock;

use App\Http\Controllers\Controller;
use App\Models\ShoppingListItem;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ShoppingListController extends Controller
{
    public function index()
    {
        $userId = Auth::id();

        $active = ShoppingListItem::where('user_id', $userId)->where('is_purchased', false)->latest()->get();
        $purchased = ShoppingListItem::where('user_id', $userId)->where('is_purchased', true)->latest()->limit(30)->get();

        return view('admin.stock.shopping-list', compact('active', 'purchased'));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name' => 'required|string|max:255',
            'quantity' => 'required|numeric|min:0',
            'unit' => 'required|string|max:20',
            'note' => 'nullable|string|max:1000',
        ]);

        ShoppingListItem::create($data + ['user_id' => Auth::id(), 'source' => 'manual']);

        return response()->json(['success' => true]);
    }

    public function update(Request $request, ShoppingListItem $item)
    {
        $this->authorizeItem($item);

        $data = $request->validate([
            'name' => 'required|string|max:255',
            'quantity' => 'required|numeric|min:0',
            'unit' => 'required|string|max:20',
            'note' => 'nullable|string|max:1000',
        ]);

        $item->update($data);

        return response()->json(['success' => true]);
    }

    public function toggle(ShoppingListItem $item)
    {
        $this->authorizeItem($item);
        $item->update(['is_purchased' => !$item->is_purchased]);
        return response()->json(['success' => true, 'is_purchased' => $item->is_purchased]);
    }

    public function destroy(ShoppingListItem $item)
    {
        $this->authorizeItem($item);
        $item->delete();
        return response()->json(['success' => true]);
    }

    public function clearPurchased()
    {
        ShoppingListItem::where('user_id', Auth::id())->where('is_purchased', true)->delete();
        return response()->json(['success' => true]);
    }

    protected function authorizeItem(ShoppingListItem $item)
    {
        abort_unless($item->user_id === Auth::id(), 403);
    }
}
