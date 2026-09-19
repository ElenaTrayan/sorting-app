<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class OrderStatusEvent extends Model
{
    protected $table = 'order_status_events';

    protected $fillable = [
        'order_id',
        'status',
        'note',
        'happened_at',
    ];

    protected $casts = [
        'happened_at' => 'datetime',
    ];

    public function order()
    {
        return $this->belongsTo(Order::class, 'order_id');
    }

    public function getStatusLabelAttribute()
    {
        return Order::DELIVERY_STATUSES[$this->status] ?? $this->status;
    }
}
