<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Promotion extends Model
{
    protected $table = 'promotions';

    public const AVAILABILITY = [
        'in_stock' => 'В наличии',
        'limited' => 'Ограничено',
        'out_of_stock' => 'Нет в наличии',
    ];

    protected $fillable = [
        'user_id',
        'product_id',
        'store_id',
        'title',
        'image',
        'price',
        'old_price',
        'discount_percent',
        'availability',
        'weight_volume',
        'valid_until',
        'url',
        'source',
    ];

    protected $casts = [
        'valid_until' => 'date',
        'price' => 'float',
        'old_price' => 'float',
    ];

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function product()
    {
        return $this->belongsTo(Product::class, 'product_id');
    }

    public function store()
    {
        return $this->belongsTo(Store::class, 'store_id');
    }

    public function getAvailabilityLabelAttribute()
    {
        return self::AVAILABILITY[$this->availability] ?? $this->availability;
    }

    public function getIsForRegularPurchaseAttribute(): bool
    {
        return $this->product_id && $this->product && $this->product->regularPurchase;
    }
}
