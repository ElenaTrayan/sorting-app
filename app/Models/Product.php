<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Нормализованный каталог товаров — связывает StockItem, RegularPurchase,
 * Promotion и OrderItem. Разные названия одного товара сопоставляются по
 * brand+name+variant+size (+barcode, если известен) — см. Product::findOrCreateMatching().
 */
class Product extends Model
{
    protected $table = 'products';

    public const CATEGORIES = [
        'product' => 'Продукты',
        'medicine' => 'Лекарства',
        'cosmetics' => 'Косметика',
        'household' => 'Бытовая химия',
        'pet' => 'Зоотовары',
        'other' => 'Прочее',
    ];

    protected $fillable = [
        'user_id',
        'name',
        'brand',
        'variant',
        'size_value',
        'size_unit',
        'category',
        'barcode',
        'image',
    ];

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function stockItems()
    {
        return $this->hasMany(StockItem::class, 'product_id');
    }

    public function regularPurchase()
    {
        return $this->hasOne(RegularPurchase::class, 'product_id');
    }

    public function promotions()
    {
        return $this->hasMany(Promotion::class, 'product_id');
    }

    public function getCategoryLabelAttribute()
    {
        return self::CATEGORIES[$this->category] ?? $this->category;
    }

    public function getDisplayNameAttribute()
    {
        $parts = array_filter([$this->name, $this->variant]);
        return implode(' ', $parts);
    }

    public function getSizeLabelAttribute()
    {
        if (!$this->size_value || !$this->size_unit) {
            return null;
        }
        $value = rtrim(rtrim(number_format((float) $this->size_value, 2, '.', ''), '0'), '.');
        return $value . ' ' . $this->size_unit;
    }

    /**
     * Сопоставление одного и того же товара по бренду/названию/варианту/весу
     * (и штрихкоду, если он есть) — вместо создания дублей при каждом ручном вводе.
     */
    public static function findOrCreateMatching(int $userId, array $attributes): self
    {
        $query = static::where('user_id', $userId);

        if (!empty($attributes['barcode'])) {
            $byBarcode = (clone $query)->where('barcode', $attributes['barcode'])->first();
            if ($byBarcode) {
                return $byBarcode;
            }
        }

        $match = $query
            ->where('name', $attributes['name'] ?? '')
            ->where('brand', $attributes['brand'] ?? null)
            ->where('variant', $attributes['variant'] ?? null)
            ->where('size_value', $attributes['size_value'] ?? null)
            ->where('size_unit', $attributes['size_unit'] ?? null)
            ->first();

        if ($match) {
            return $match;
        }

        $attributes['user_id'] = $userId;
        $attributes['category'] = $attributes['category'] ?? 'product';

        return static::create($attributes);
    }
}
