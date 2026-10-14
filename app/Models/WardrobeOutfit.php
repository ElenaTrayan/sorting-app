<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Образ из вещей гардероба. Составлен из WardrobeItem (items()), среди которых могут быть и вещи
 * "нужно купить" — тогда образ считается не готовым (isComplete()). Куда надевали — wears().
 */
class WardrobeOutfit extends Model
{
    protected $table = 'wardrobe_outfits';

    public const SEASONS = [
        'winter' => 'Зима',
        'spring' => 'Весна',
        'summer' => 'Лето',
        'autumn' => 'Осень',
    ];

    protected $fillable = [
        'user_id',
        'name',
        'image',
        'seasons',
        'temp_min',
        'temp_max',
        'body_types',
        'note',
    ];

    protected $casts = [
        'seasons' => 'array',
        'body_types' => 'array',
        'temp_min' => 'integer',
        'temp_max' => 'integer',
    ];

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function items()
    {
        return $this->belongsToMany(WardrobeItem::class, 'wardrobe_outfit_item', 'outfit_id', 'item_id')
            ->withPivot('sort_order')
            ->orderBy('wardrobe_outfit_item.sort_order');
    }

    public function wears()
    {
        return $this->hasMany(WardrobeOutfitWear::class, 'outfit_id')->orderByDesc('worn_at')->orderByDesc('id');
    }

    public function getImageDataAttribute(): ?array
    {
        if (empty($this->attributes['image'])) {
            return null;
        }

        $decoded = json_decode($this->attributes['image'], true);

        return is_array($decoded) ? $decoded : null;
    }

    public function getImageUrlAttribute(): ?string
    {
        $data = $this->image_data;

        return !empty($data['path']) ? '/storage' . $data['path'] : null;
    }

    /**
     * Все вещи образа уже есть в гардеробе (нечего докупать)
     */
    public function isComplete(): bool
    {
        return $this->items->where('status', WardrobeItem::STATUS_WISHLIST)->isEmpty();
    }

    /**
     * "+5…+15 °C", "от +20 °C", "до −5 °C"; null — температура не указана
     */
    public function temperatureLabel(): ?string
    {
        return self::formatTemperature($this->temp_min, $this->temp_max);
    }

    // то же для вещей гардероба и пинов "Одежда" — у них такая же пара полей "от / до"
    public static function formatTemperature(?int $min, ?int $max): ?string
    {
        $format = function (int $value) {
            return ($value > 0 ? '+' : ($value < 0 ? '−' : '')) . abs($value);
        };

        if ($min !== null && $max !== null) {
            return $format($min) . '…' . $format($max) . ' °C';
        }
        if ($min !== null) {
            return 'от ' . $format($min) . ' °C';
        }
        if ($max !== null) {
            return 'до ' . $format($max) . ' °C';
        }

        return null;
    }
}
