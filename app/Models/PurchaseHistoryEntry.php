<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Запись истории покупок. Создаётся автоматически при получении заказа
 * (см. Admin\Stock\OrderController::markDelivered()) либо вручную для оффлайн-покупок.
 */
class PurchaseHistoryEntry extends Model
{
    protected $table = 'purchase_history_entries';

    protected $fillable = [
        'user_id',
        'order_id',
        'store_id',
        'items_summary',
        'total_amount',
        'payment_method',
        'category',
        'purchased_at',
        'had_discount',
    ];

    protected $casts = [
        'purchased_at' => 'date',
        'total_amount' => 'float',
        'had_discount' => 'boolean',
    ];

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function order()
    {
        return $this->belongsTo(Order::class, 'order_id');
    }

    public function store()
    {
        return $this->belongsTo(Store::class, 'store_id');
    }

    public function getItemsAttribute(): array
    {
        return $this->items_summary ? json_decode($this->items_summary, true) : [];
    }

    public static function fromOrder(Order $order): self
    {
        $items = $order->items->map(function (OrderItem $item) {
            return [
                'name' => $item->name,
                'brand' => $item->brand,
                'quantity' => $item->quantity,
                'unit' => $item->unit,
                'price' => $item->price,
            ];
        })->all();

        return static::updateOrCreate(
            ['order_id' => $order->id],
            [
                'user_id' => $order->user_id,
                'store_id' => $order->store_id,
                'items_summary' => json_encode($items),
                'total_amount' => $order->total_amount,
                'payment_method' => $order->payment_method,
                'category' => optional($order->store)->type,
                'purchased_at' => $order->delivered_at ?? now(),
                'had_discount' => false,
            ]
        );
    }
}
