<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Фото вещи "Моего гардероба". Первое по sort_order — обложка (копия в wardrobe_items.image,
 * см. WardrobeItem::syncCover). Файл — в wardrobe/{user_id}/, как у остальных картинок раздела (WardrobeImage).
 */
class WardrobeItemPhoto extends Model
{
    protected $table = 'wardrobe_item_photos';

    protected $fillable = [
        'item_id',
        'image',
        'sort_order',
    ];

    protected $casts = [
        'sort_order' => 'integer',
    ];

    public function item()
    {
        return $this->belongsTo(WardrobeItem::class, 'item_id');
    }

    public function getImageDataAttribute(): ?array
    {
        $decoded = json_decode((string)$this->attributes['image'], true);

        return is_array($decoded) ? $decoded : null;
    }

    public function getImageUrlAttribute(): ?string
    {
        $data = $this->image_data;

        return !empty($data['path']) ? '/storage' . $data['path'] : null;
    }
}
