<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * "Постоянные покупки" — товары, которые пользователь регулярно покупает.
 */
class RegularPurchase extends Model
{
    protected $table = 'regular_purchases';

    public const PREFERENCES = [
        'favorite' => 'Любимый',
        'normal' => 'Нормальный',
        'alternative' => 'Альтернатива',
        'avoid' => 'Не покупать',
    ];

    protected $fillable = [
        'user_id',
        'product_id',
        'preferred_store_id',
        'avg_price',
        'last_price',
        'last_purchased_at',
        'frequency_days',
        'preference',
    ];

    protected $casts = [
        'last_purchased_at' => 'date',
        'avg_price' => 'float',
        'last_price' => 'float',
    ];

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function product()
    {
        return $this->belongsTo(Product::class, 'product_id');
    }

    public function preferredStore()
    {
        return $this->belongsTo(Store::class, 'preferred_store_id');
    }

    public function getPreferenceLabelAttribute()
    {
        return self::PREFERENCES[$this->preference] ?? $this->preference;
    }

    /**
     * Насколько давно должны были купить снова, судя по frequency_days —
     * используется для мягкой подсказки "пора купить" на странице.
     */
    public function getIsDueAttribute(): bool
    {
        if (!$this->frequency_days || !$this->last_purchased_at) {
            return false;
        }
        return $this->last_purchased_at->copy()->addDays($this->frequency_days)->isPast();
    }
}
