<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Class WishlistItem
 * @package App\Models
 */
class WishlistItem extends Model
{
    protected $table = 'wishlist_items';

    protected $fillable = [
        'wishlist_id',
        'title',
        'description',
        'url',
        'price',
        'category',
        'priority',
        'image',
        'image_large',
        'is_reserved',
        'reserved_by',
        'is_fulfilled',
    ];

    protected $casts = [
        'is_reserved' => 'boolean',
        'is_fulfilled' => 'boolean',
        'price' => 'decimal:2',
    ];

    public function wishlist()
    {
        return $this->belongsTo(Wishlist::class, 'wishlist_id');
    }

    /**
     * Декодирует поле image ({name, extension, path}, как у постов) в массив.
     *
     * @return array|null
     */
    public function getImageDataAttribute(): ?array
    {
        if (empty($this->image)) {
            return null;
        }

        $decoded = json_decode($this->image, true);

        return is_array($decoded) ? $decoded : null;
    }

    /**
     * Готовый URL картинки для <img src="">, либо null, если картинки нет.
     *
     * @return string|null
     */
    public function getImageUrlAttribute(): ?string
    {
        $data = $this->image_data;

        return !empty($data['path']) ? '/storage' . $data['path'] : null;
    }

    /**
     * Декодирует поле image_large ({name, extension, path}) — увеличенная версия картинки,
     * для просмотра "поближе" по клику в попапе "Подробнее".
     *
     * @return array|null
     */
    public function getImageLargeDataAttribute(): ?array
    {
        if (empty($this->image_large)) {
            return null;
        }

        $decoded = json_decode($this->image_large, true);

        return is_array($decoded) ? $decoded : null;
    }

    /**
     * Готовый URL увеличенной картинки, либо null, если её нет (тогда используется обычная).
     *
     * @return string|null
     */
    public function getImageLargeUrlAttribute(): ?string
    {
        $data = $this->image_large_data;

        return !empty($data['path']) ? '/storage' . $data['path'] : null;
    }

    /**
     * Домен ссылки на товар (для отображения под карточкой, как источник) — например
     * "favoriteshoes.com.ua" из "https://favoriteshoes.com.ua/product/123".
     *
     * @return string|null
     */
    public function getUrlDomainAttribute(): ?string
    {
        if (empty($this->url)) {
            return null;
        }

        $host = parse_url($this->url, PHP_URL_HOST);

        return $host ? preg_replace('/^www\./', '', $host) : null;
    }
}
