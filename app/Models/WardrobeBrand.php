<?php

namespace App\Models;

use App\Components\WardrobeImage;
use Illuminate\Database\Eloquent\Model;

/**
 * Бренд одежды ("Мой гардероб"). user_id = null — общий бренд, виден всем (список ведёт админ:
 * админка → Бренды одежды); user_id задан — свой бренд пользователя, появляется, когда он вписывает
 * в форму вещи название, которого нет в общем списке (resolveId()).
 */
class WardrobeBrand extends Model
{
    protected $table = 'wardrobe_brands';

    protected $fillable = [
        'user_id',
        'name',
    ];

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function items()
    {
        return $this->hasMany(WardrobeItem::class, 'brand_id');
    }

    public function isShared(): bool
    {
        return $this->user_id === null;
    }

    // логотип: загружает админ (админка → Бренды одежды), лежит в wardrobe/brands/
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
     * Бренды, которые видит пользователь: общие и его собственные
     */
    public function scopeAvailableTo($query, int $userId)
    {
        return $query->where(function ($q) use ($userId) {
            $q->whereNull('user_id')->orWhere('user_id', $userId);
        });
    }

    /**
     * id бренда по названию из формы вещи: общий, если такой есть, иначе свой бренд пользователя
     * (создаётся при первом упоминании). Сравнение без учёта регистра — как в сопоставлении БД.
     *
     * @param string|null $name
     * @param int $userId
     * @return int|null null — бренд не указан
     */
    public static function resolveId(?string $name, int $userId): ?int
    {
        $name = trim((string)$name);
        if ($name === '') {
            return null;
        }

        $brand = static::availableTo($userId)
            ->where('name', $name)
            ->orderByRaw('user_id IS NULL DESC') // общий важнее своего
            ->first();

        if (!$brand) {
            $brand = static::create(['user_id' => $userId, 'name' => $name]);
        }

        return $brand->id;
    }

    /**
     * Значение из формы вещи или пина (список parts/brand_select): id из списка — общий или свой бренд;
     * "new" — свой бренд с названием $newName; пусто — без бренда
     *
     * @param mixed $value значение скрытого поля списка
     * @param string|null $newName название своего бренда
     * @param int $userId
     * @return int|null
     */
    public static function resolveFromForm($value, ?string $newName, int $userId): ?int
    {
        if ($value === 'new') {
            return static::resolveId($newName, $userId);
        }
        if (!is_numeric($value) || (int)$value <= 0) {
            return null;
        }

        return static::availableTo($userId)->whereKey((int)$value)->value('id');
    }

    /**
     * Сделать бренд общим. Свои бренды пользователей с тем же названием становятся не нужны —
     * их вещи и пины переходят на общий, а сами они удаляются.
     */
    public function makeShared(): void
    {
        $this->user_id = null;
        $this->save();

        $duplicates = static::where('name', $this->name)->where('id', '!=', $this->id)->get();
        if ($duplicates->isNotEmpty()) {
            $duplicateIds = $duplicates->pluck('id');
            WardrobeItem::whereIn('brand_id', $duplicateIds)->update(['brand_id' => $this->id]);
            PostClothes::whereIn('brand_id', $duplicateIds)->update(['brand_id' => $this->id]);
            foreach ($duplicates as $duplicate) {
                WardrobeImage::delete($duplicate->image_data);
                $duplicate->delete();
            }
        }
    }
}
