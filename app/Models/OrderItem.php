<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class OrderItem extends Model
{
    protected $table = 'order_items';

    protected $fillable = [
        'order_id',
        'product_id',
        'name',
        'brand',
        'quantity',
        'unit',
        'price',
        'added_to_stock',
    ];

    protected $casts = [
        'quantity' => 'float',
        'price' => 'float',
        'added_to_stock' => 'boolean',
    ];

    public function order()
    {
        return $this->belongsTo(Order::class, 'order_id');
    }

    public function product()
    {
        return $this->belongsTo(Product::class, 'product_id');
    }
}
