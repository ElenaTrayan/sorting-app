<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

/**
 * Место хранения / категория раздела "Мои запасы" (Холодильник, Морозилка, Кладовка,
 * Аптечка, Таблетница, Косметика, Уборка, Зоотовары, Другое).
 */
class StockLocation extends Model
{
    protected $table = 'stock_locations';

    protected $fillable = [
        'user_id',
        'name',
        'slug',
        'icon',
        'sort_order',
    ];

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function items()
    {
        return $this->hasMany(StockItem::class, 'location_id');
    }

    // Дефолтный набор мест хранения — заводится один раз для пользователя,
    // если у него ещё нет ни одного (см. Admin\Stock\InventoryController).
    public static function defaults()
    {
        return [
            ['name' => 'Холодильник', 'slug' => 'fridge', 'icon' => 'snowflake'],
            ['name' => 'Морозилка', 'slug' => 'freezer', 'icon' => 'icicles'],
            ['name' => 'Кладовка', 'slug' => 'pantry', 'icon' => 'box'],
            ['name' => 'Аптечка', 'slug' => 'medicine-cabinet', 'icon' => 'briefcase-medical'],
            ['name' => 'Таблетница', 'slug' => 'pillbox', 'icon' => 'pills'],
            ['name' => 'Косметика', 'slug' => 'cosmetics', 'icon' => 'pump-soap'],
            ['name' => 'Уборка', 'slug' => 'cleaning', 'icon' => 'spray-can'],
            ['name' => 'Зоотовары', 'slug' => 'pets', 'icon' => 'paw'],
            ['name' => 'Другое', 'slug' => 'other', 'icon' => 'box-open'],
        ];
    }

    public static function ensureDefaultsFor(int $userId)
    {
        if (static::where('user_id', $userId)->exists()) {
            return;
        }

        foreach (static::defaults() as $i => $location) {
            static::create($location + ['user_id' => $userId, 'sort_order' => $i]);
        }
    }

    public static function slugify(string $name): string
    {
        return Str::slug($name);
    }
}
