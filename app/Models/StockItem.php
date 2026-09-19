<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;

/**
 * Товар в запасах пользователя ("Мои запасы"): продукты, аптечка, косметика,
 * бытовая химия, зоотовары и т.д. Статус срока годности вычисляется на лету
 * (см. getExpiryStatusAttribute()) — красный/оранжевый/зелёный, как в макете.
 */
class StockItem extends Model
{
    protected $table = 'stock_items';

    public const SOON_DAYS = 7; // "истекает в течение 7 дней" — оранжевый
    public const MONTH_DAYS = 30;

    protected $fillable = [
        'user_id',
        'product_id',
        'location_id',
        'store_id',
        'order_item_id',
        'name',
        'brand',
        'category',
        'quantity',
        'unit',
        'purchased_at',
        'expires_at',
        'price',
        'note',
        'image',
    ];

    protected $casts = [
        'purchased_at' => 'date',
        'expires_at' => 'date',
        'quantity' => 'float',
        'price' => 'float',
    ];

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function product()
    {
        return $this->belongsTo(Product::class, 'product_id');
    }

    public function location()
    {
        return $this->belongsTo(StockLocation::class, 'location_id');
    }

    public function store()
    {
        return $this->belongsTo(Store::class, 'store_id');
    }

    /**
     * expired / soon (<=7 дней) / month (<=30 дней) / ok / none (срок не задан)
     */
    public function getExpiryStatusAttribute(): string
    {
        if (!$this->expires_at) {
            return 'none';
        }

        $daysLeft = Carbon::today()->diffInDays($this->expires_at, false);

        if ($daysLeft < 0) {
            return 'expired';
        }
        if ($daysLeft <= self::SOON_DAYS) {
            return 'soon';
        }
        if ($daysLeft <= self::MONTH_DAYS) {
            return 'month';
        }
        return 'ok';
    }

    public function getDaysLeftAttribute(): ?int
    {
        if (!$this->expires_at) {
            return null;
        }
        return (int) Carbon::today()->diffInDays($this->expires_at, false);
    }

    public function scopeExpired($query)
    {
        return $query->whereNotNull('expires_at')->where('expires_at', '<', Carbon::today());
    }

    public function scopeExpiringWithinDays($query, int $days)
    {
        return $query->whereNotNull('expires_at')
            ->whereBetween('expires_at', [Carbon::today(), Carbon::today()->addDays($days)]);
    }
}
