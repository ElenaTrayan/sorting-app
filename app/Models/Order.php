<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;

class Order extends Model
{
    protected $table = 'orders';

    public const DELIVERY_STATUSES = [
        'pending' => 'Ожидается доставка',
        'shipped' => 'В пути',
        'ready_for_pickup' => 'Можно забрать',
        'delivered' => 'Получен',
        'returning' => 'Оформляется возврат',
        'returned' => 'Возвращён',
        'cancelled' => 'Отменён',
    ];

    public const PAYMENT_STATUSES = [
        'paid' => 'Оплачено',
        'cod' => 'Наложенный платёж',
    ];

    public const FULFILLMENT_METHODS = [
        'delivery' => 'Доставка',
        'pickup' => 'Самовывоз',
    ];

    public const RETURN_STATUSES = [
        'needs_return' => 'Нужно оформить возврат',
        'in_progress' => 'Возврат в процессе',
        'returned' => 'Возвращено',
    ];

    protected $fillable = [
        'user_id',
        'store_id',
        'order_number',
        'ordered_at',
        'total_amount',
        'payment_status',
        'payment_method',
        'delivery_status',
        'fulfillment_method',
        'pickup_location',
        'carrier',
        'tracking_number',
        'ready_until',
        'delivery_window',
        'delivered_at',
        'return_deadline',
        'return_status',
        'notes',
    ];

    protected $casts = [
        'ordered_at' => 'date',
        'ready_until' => 'date',
        'delivered_at' => 'date',
        'return_deadline' => 'date',
        'total_amount' => 'float',
    ];

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function store()
    {
        return $this->belongsTo(Store::class, 'store_id');
    }

    public function items()
    {
        return $this->hasMany(OrderItem::class, 'order_id');
    }

    public function statusEvents()
    {
        return $this->hasMany(OrderStatusEvent::class, 'order_id')->orderBy('happened_at');
    }

    public function historyEntry()
    {
        return $this->hasOne(PurchaseHistoryEntry::class, 'order_id');
    }

    public function getDeliveryStatusLabelAttribute()
    {
        return self::DELIVERY_STATUSES[$this->delivery_status] ?? $this->delivery_status;
    }

    public function getPaymentStatusLabelAttribute()
    {
        return self::PAYMENT_STATUSES[$this->payment_status] ?? $this->payment_status;
    }

    public function getReturnStatusLabelAttribute()
    {
        return $this->return_status ? (self::RETURN_STATUSES[$this->return_status] ?? $this->return_status) : null;
    }

    public function getDaysUntilReturnDeadlineAttribute(): ?int
    {
        if (!$this->return_deadline) {
            return null;
        }
        return (int) Carbon::today()->diffInDays($this->return_deadline, false);
    }

    public function getDaysUntilReadyDeadlineAttribute(): ?int
    {
        if (!$this->ready_until) {
            return null;
        }
        return (int) Carbon::today()->diffInDays($this->ready_until, false);
    }

    /**
     * Заказ "требует внимания" на Overview/списке заказов: можно забрать,
     * нужно оформить возврат, либо скоро истекает срок возврата/хранения.
     */
    public function getNeedsAttentionAttribute(): bool
    {
        if ($this->delivery_status === 'ready_for_pickup') {
            return true;
        }
        if ($this->return_status === 'needs_return') {
            return true;
        }
        if ($this->return_deadline && !in_array($this->return_status, ['returned'])
            && Carbon::today()->diffInDays($this->return_deadline, false) <= 3
            && Carbon::today()->diffInDays($this->return_deadline, false) >= 0) {
            return true;
        }
        return false;
    }

    public function itemsCount(): int
    {
        return (int) $this->items()->sum('quantity');
    }
}
