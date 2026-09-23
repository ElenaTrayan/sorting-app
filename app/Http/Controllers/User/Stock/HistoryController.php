<?php

namespace App\Http\Controllers\User\Stock;

use App\Http\Controllers\Controller;
use App\Models\PurchaseHistoryEntry;
use App\Models\Store;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Response;

class HistoryController extends Controller
{
    public function index(Request $request)
    {
        $userId = Auth::id();

        $query = PurchaseHistoryEntry::where('user_id', $userId)->with('store');

        if ($request->filled('date_from')) {
            $query->whereDate('purchased_at', '>=', $request->get('date_from'));
        }
        if ($request->filled('date_to')) {
            $query->whereDate('purchased_at', '<=', $request->get('date_to'));
        }
        if ($request->filled('store_id')) {
            $query->where('store_id', $request->get('store_id'));
        }
        if ($request->filled('category')) {
            $query->where('category', $request->get('category'));
        }
        if ($request->filled('payment_method')) {
            $query->where('payment_method', $request->get('payment_method'));
        }

        $entries = $query->orderByDesc('purchased_at')->get();

        if ($request->filled('product')) {
            $needle = mb_strtolower($request->get('product'));
            $entries = $entries->filter(function (PurchaseHistoryEntry $e) use ($needle) {
                foreach ($e->items as $item) {
                    if (str_contains(mb_strtolower($item['name'] ?? ''), $needle)) {
                        return true;
                    }
                }
                return false;
            })->values();
        }

        $stats = [
            'count' => $entries->count(),
            'total' => $entries->sum('total_amount'),
            'avg' => $entries->count() ? round($entries->avg('total_amount'), 2) : 0,
            'discounted' => $entries->where('had_discount', true)->count(),
        ];

        $byMonth = $entries->groupBy(fn ($e) => $e->purchased_at->format('Y-m'))
            ->map(fn ($group) => round($group->sum('total_amount'), 2))
            ->sortKeys();

        $byCategory = $entries->groupBy(fn ($e) => $e->category ?: 'Без категории')
            ->map(fn ($group) => round($group->sum('total_amount'), 2))
            ->sortDesc();

        $byStore = $entries->groupBy(fn ($e) => optional($e->store)->name ?: 'Без магазина')
            ->map(fn ($group) => round($group->sum('total_amount'), 2))
            ->sortDesc();

        $productCounts = [];
        foreach ($entries as $entry) {
            foreach ($entry->items as $item) {
                $name = $item['name'] ?? null;
                if (!$name) {
                    continue;
                }
                $productCounts[$name] = ($productCounts[$name] ?? 0) + 1;
            }
        }
        arsort($productCounts);
        $topProducts = array_slice($productCounts, 0, 8, true);

        return view('user.stock.history', [
            'entries' => $entries,
            'stats' => $stats,
            'byMonth' => $byMonth,
            'byCategory' => $byCategory,
            'byStore' => $byStore,
            'topProducts' => $topProducts,
            'stores' => Store::where('user_id', $userId)->orderBy('name')->get(),
            'filters' => $request->only(['date_from', 'date_to', 'store_id', 'category', 'payment_method', 'product']),
        ]);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'store_id' => 'nullable|integer',
            'purchased_at' => 'required|date',
            'total_amount' => 'required|numeric|min:0',
            'payment_method' => 'nullable|string|max:50',
            'category' => 'nullable|string|max:50',
            'items_text' => 'nullable|string|max:2000',
        ]);

        $items = collect(explode("\n", $data['items_text'] ?? ''))
            ->map(fn ($line) => trim($line))
            ->filter()
            ->map(fn ($line) => ['name' => $line])
            ->values()
            ->all();

        PurchaseHistoryEntry::create([
            'user_id' => Auth::id(),
            'store_id' => $data['store_id'] ?? null,
            'purchased_at' => $data['purchased_at'],
            'total_amount' => $data['total_amount'],
            'payment_method' => $data['payment_method'] ?? null,
            'category' => $data['category'] ?? null,
            'items_summary' => json_encode($items),
            'had_discount' => false,
        ]);

        return response()->json(['success' => true]);
    }

    public function export(Request $request)
    {
        $entries = PurchaseHistoryEntry::where('user_id', Auth::id())->with('store')->orderByDesc('purchased_at')->get();

        $csv = "Дата;Магазин;Сумма;Способ оплаты;Категория;Товары\n";
        foreach ($entries as $entry) {
            $items = collect($entry->items)->pluck('name')->implode(', ');
            $csv .= implode(';', [
                $entry->purchased_at->format('d.m.Y'),
                optional($entry->store)->name,
                number_format($entry->total_amount, 2, ',', ''),
                $entry->payment_method,
                $entry->category,
                str_replace(["\n", ';'], ' ', $items),
            ]) . "\n";
        }

        return Response::make($csv, 200, [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename="purchase-history.csv"',
        ]);
    }
}
