<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Резерв на случай ЧС: минимум vs фактически имеющееся количество.
 */
class ReserveItem extends Model
{
    protected $table = 'reserve_items';

    public const CATEGORIES = [
        'water' => 'Вода',
        'food' => 'Продукты',
        'medicine' => 'Медицина',
        'hygiene' => 'Гигиена',
        'household' => 'Бытовые товары',
        'power' => 'Электричество/свет',
        'other' => 'Другое',
    ];

    protected $fillable = [
        'user_id',
        'name',
        'category',
        'unit',
        'minimum_qty',
        'current_qty',
        'is_critical',
    ];

    protected $casts = [
        'minimum_qty' => 'float',
        'current_qty' => 'float',
        'is_critical' => 'boolean',
    ];

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function getCategoryLabelAttribute()
    {
        return self::CATEGORIES[$this->category] ?? $this->category;
    }

    public function getReadinessPercentAttribute(): int
    {
        if ($this->minimum_qty <= 0) {
            return 100;
        }
        return (int) min(100, round(($this->current_qty / $this->minimum_qty) * 100));
    }

    public function getNeedsRestockAttribute(): bool
    {
        return $this->current_qty < $this->minimum_qty;
    }

    public function getMissingQtyAttribute(): float
    {
        return max(0, $this->minimum_qty - $this->current_qty);
    }
}
