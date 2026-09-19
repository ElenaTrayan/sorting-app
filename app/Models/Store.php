<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Store extends Model
{
    protected $table = 'stores';

    public const TYPES = [
        'supermarket' => 'Супермаркет',
        'pharmacy' => 'Аптека',
        'marketplace' => 'Маркетплейс',
        'other' => 'Другое',
    ];

    protected $fillable = [
        'user_id',
        'name',
        'logo',
        'type',
        'categories',
        'categories_list',
        'delivery',
        'pickup',
        'website',
        'monitor_promotions',
    ];

    protected $casts = [
        'delivery' => 'boolean',
        'pickup' => 'boolean',
        'monitor_promotions' => 'boolean',
    ];

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function getCategoriesListAttribute()
    {
        return $this->categories ? json_decode($this->categories, true) : [];
    }

    public function setCategoriesListAttribute($value)
    {
        $this->attributes['categories'] = json_encode(array_values(array_filter((array) $value)));
    }

    public function promotions()
    {
        return $this->hasMany(Promotion::class, 'store_id');
    }

    public function orders()
    {
        return $this->hasMany(Order::class, 'store_id');
    }

    public function regularPurchases()
    {
        return $this->hasMany(RegularPurchase::class, 'preferred_store_id');
    }

    public function getTypeLabelAttribute()
    {
        return self::TYPES[$this->type] ?? $this->type;
    }
}
