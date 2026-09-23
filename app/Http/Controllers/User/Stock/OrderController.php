<?php

namespace App\Http\Controllers\User\Stock;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\OrderStatusEvent;
use App\Models\Product;
use App\Models\PurchaseHistoryEntry;
use App\Models\StockItem;
use App\Models\StockLocation;
use App\Models\Store;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class OrderController extends Controller
{
    public function index(Request $request)
    {
        $userId = Auth::id();

        $query = Order::where('user_id', $userId)->with('store');

        if ($request->filled('store_id')) {
            $query->where('store_id', $request->get('store_id'));
        }
        if ($request->filled('status')) {
            $query->where('delivery_status', $request->get('status'));
        }
        if ($request->filled('date_from')) {
            $query->whereDate('ordered_at', '>=', $request->get('date_from'));
        }
        if ($request->filled('date_to')) {
            $query->whereDate('ordered_at', '<=', $request->get('date_to'));
        }

        $orders = $query->orderByDesc('ordered_at')->get();

        $all = Order::where('user_id', $userId)->get();
        $summary = [
            'shipped' => $all->where('delivery_status', 'shipped')->count(),
            'ready_for_pickup' => $all->where('delivery_status', 'ready_for_pickup')->count(),
            'delivered' => $all->where('delivery_status', 'delivered')->count(),
            'needs_return' => $all->where('return_status', 'needs_return')->count(),
            'return_soon' => $all->filter(fn (Order $o) => !is_null($o->days_until_return_deadline) && $o->days_until_return_deadline >= 0 && $o->days_until_return_deadline <= 3)->count(),
        ];

        $attention = $all->filter->needs_attention->sortBy('return_deadline')->values();

        return view('user.stock.orders.index', [
            'orders' => $orders,
            'summary' => $summary,
            'attention' => $attention,
            'stores' => Store::where('user_id', $userId)->orderBy('name')->get(),
        ]);
    }

    public function show(Order $order)
    {
        $this->authorizeItem($order);
        $order->load(['store', 'items', 'statusEvents']);

        return view('user.stock.orders.show', compact('order'));
    }

    public function store(Request $request)
    {
        $data = $this->validateData($request);
        $userId = Auth::id();

        $order = Order::create($this->prepareOrderData($data) + ['user_id' => $userId]);
        $this->syncItems($order, $data['items'] ?? []);
        $this->logStatus($order, $order->delivery_status, 'Заказ создан');

        return response()->json(['success' => true, 'id' => $order->id]);
    }

    public function update(Request $request, Order $order)
    {
        $this->authorizeItem($order);
        $data = $this->validateData($request);

        $order->update($this->prepareOrderData($data));
        $this->syncItems($order, $data['items'] ?? []);

        return response()->json(['success' => true]);
    }

    public function destroy(Order $order)
    {
        $this->authorizeItem($order);
        $order->delete();
        return response()->json(['success' => true]);
    }

    // Смена статуса доставки — пишет событие в таймлайн; при "Получен" — авто-запись в историю покупок.
    public function updateStatus(Request $request, Order $order)
    {
        $this->authorizeItem($order);
        $data = $request->validate([
            'delivery_status' => 'required|in:pending,shipped,ready_for_pickup,delivered,returning,returned,cancelled',
            'note' => 'nullable|string|max:255',
        ]);

        $order->delivery_status = $data['delivery_status'];
        if ($data['delivery_status'] === 'delivered' && !$order->delivered_at) {
            $order->delivered_at = now();
        }
        $order->save();

        $this->logStatus($order, $data['delivery_status'], $data['note'] ?? null);

        if ($data['delivery_status'] === 'delivered') {
            PurchaseHistoryEntry::fromOrder($order->fresh('items'));
        }

        return response()->json(['success' => true]);
    }

    public function registerReturn(Request $request, Order $order)
    {
        $this->authorizeItem($order);
        $data = $request->validate([
            'return_status' => 'required|in:needs_return,in_progress,returned',
            'return_deadline' => 'nullable|date',
        ]);

        $order->update($data);
        $this->logStatus($order, 'returning', 'Статус возврата: ' . $order->return_status_label);

        return response()->json(['success' => true]);
    }

    // "Добавить в запасы" — из полученного заказа сразу в "Мои запасы" (см. связку в ТЗ)
    public function addItemToStock(OrderItem $orderItem)
    {
        $order = $orderItem->order;
        $this->authorizeItem($order);

        if (!$orderItem->added_to_stock) {
            $product = $orderItem->product_id
                ? $orderItem->product
                : Product::findOrCreateMatching($order->user_id, [
                    'name' => $orderItem->name,
                    'brand' => $orderItem->brand,
                ]);

            $defaultLocation = StockLocation::where('user_id', $order->user_id)->orderBy('sort_order')->first();

            StockItem::create([
                'user_id' => $order->user_id,
                'product_id' => $product->id,
                'location_id' => optional($defaultLocation)->id,
                'store_id' => $order->store_id,
                'order_item_id' => $orderItem->id,
                'name' => $orderItem->name,
                'brand' => $orderItem->brand,
                'category' => $product->category ?? 'product',
                'quantity' => $orderItem->quantity,
                'unit' => $orderItem->unit,
                'purchased_at' => $order->delivered_at ?? now(),
                'price' => $orderItem->price,
            ]);

            $orderItem->update(['added_to_stock' => true]);
        }

        return response()->json(['success' => true]);
    }

    protected function syncItems(Order $order, array $items): void
    {
        $order->items()->delete();
        foreach ($items as $item) {
            if (empty($item['name'])) {
                continue;
            }
            OrderItem::create([
                'order_id' => $order->id,
                'name' => $item['name'],
                'brand' => $item['brand'] ?? null,
                'quantity' => $item['quantity'] ?? 1,
                'unit' => $item['unit'] ?? 'шт',
                'price' => $item['price'] ?? null,
            ]);
        }
    }

    protected function logStatus(Order $order, string $status, ?string $note): void
    {
        OrderStatusEvent::create([
            'order_id' => $order->id,
            'status' => $status,
            'note' => $note,
            'happened_at' => now(),
        ]);
    }

    protected function prepareOrderData(array $data): array
    {
        unset($data['items']);
        return $data;
    }

    protected function authorizeItem(Order $order)
    {
        abort_unless($order->user_id === Auth::id(), 403);
    }

    protected function validateData(Request $request): array
    {
        return $request->validate([
            'store_id' => 'nullable|integer',
            'order_number' => 'nullable|string|max:100',
            'ordered_at' => 'nullable|date',
            'total_amount' => 'required|numeric|min:0',
            'payment_status' => 'required|in:paid,cod',
            'payment_method' => 'nullable|string|max:50',
            'delivery_status' => 'required|in:pending,shipped,ready_for_pickup,delivered,returning,returned,cancelled',
            'fulfillment_method' => 'required|in:delivery,pickup',
            'pickup_location' => 'nullable|string|max:255',
            'carrier' => 'nullable|string|max:100',
            'tracking_number' => 'nullable|string|max:100',
            'ready_until' => 'nullable|date',
            'delivery_window' => 'nullable|string|max:100',
            'return_deadline' => 'nullable|date',
            'notes' => 'nullable|string|max:2000',
            'items' => 'nullable|array',
            'items.*.name' => 'nullable|string|max:255',
            'items.*.brand' => 'nullable|string|max:150',
            'items.*.quantity' => 'nullable|numeric|min:0',
            'items.*.unit' => 'nullable|string|max:20',
            'items.*.price' => 'nullable|numeric|min:0',
        ]);
    }
}
