<?php

namespace App\Http\Controllers\User\Stock;

use App\Http\Controllers\Controller;
use App\Models\ReserveItem;
use App\Models\ShoppingListItem;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ReserveController extends Controller
{
    public function index()
    {
        $userId = Auth::id();
        $items = ReserveItem::where('user_id', $userId)->orderBy('category')->orderBy('name')->get();
        $grouped = $items->groupBy('category');

        $totalMinimum = $items->sum('minimum_qty');
        $readiness = $totalMinimum > 0
            ? (int) min(100, round($items->sum(fn ($i) => min($i->current_qty, $i->minimum_qty)) / $totalMinimum * 100))
            : 100;

        return view('user.stock.reserve', [
            'grouped' => $grouped,
            'readiness' => $readiness,
            'categories' => ReserveItem::CATEGORIES,
        ]);
    }

    public function store(Request $request)
    {
        $data = $this->validateData($request);
        ReserveItem::create($data + ['user_id' => Auth::id(), 'is_critical' => $request->boolean('is_critical')]);
        return response()->json(['success' => true]);
    }

    public function update(Request $request, ReserveItem $reserveItem)
    {
        $this->authorizeItem($reserveItem);
        $data = $this->validateData($request);
        $reserveItem->update($data + ['is_critical' => $request->boolean('is_critical')]);
        return response()->json(['success' => true]);
    }

    public function destroy(ReserveItem $reserveItem)
    {
        $this->authorizeItem($reserveItem);
        $reserveItem->delete();
        return response()->json(['success' => true]);
    }

    public function addToShoppingList(ReserveItem $reserveItem)
    {
        $this->authorizeItem($reserveItem);

        ShoppingListItem::create([
            'user_id' => $reserveItem->user_id,
            'name' => $reserveItem->name,
            'quantity' => $reserveItem->missing_qty ?: 1,
            'unit' => $reserveItem->unit,
            'source' => 'reserve',
        ]);

        return response()->json(['success' => true]);
    }

    protected function authorizeItem(ReserveItem $reserveItem)
    {
        abort_unless($reserveItem->user_id === Auth::id(), 403);
    }

    protected function validateData(Request $request): array
    {
        return $request->validate([
            'name' => 'required|string|max:150',
            'category' => 'required|string|max:30',
            'unit' => 'required|string|max:20',
            'minimum_qty' => 'required|numeric|min:0',
            'current_qty' => 'required|numeric|min:0',
        ]);
    }
}
