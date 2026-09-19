<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ShoppingListItem extends Model
{
    protected $table = 'shopping_list_items';

    public const SOURCES = [
        'manual' => 'Добавлено вручную',
        'regular' => 'Постоянная покупка',
        'order' => 'Из заказа',
        'low_stock' => 'Заканчивается в запасах',
        'reserve' => 'Резерв ЧС',
        'promotion' => 'Из акции',
    ];

    protected $fillable = [
        'user_id',
        'product_id',
        'name',
        'quantity',
        'unit',
        'note',
        'source',
        'is_purchased',
    ];

    protected $casts = [
        'quantity' => 'float',
        'is_purchased' => 'boolean',
    ];

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function product()
    {
        return $this->belongsTo(Product::class, 'product_id');
    }

    public function getSourceLabelAttribute()
    {
        return self::SOURCES[$this->source] ?? $this->source;
    }
}
