<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Место хранения одежды ("Мой гардероб"): тремпель, ящик, коробка или конкретная вещь вроде
 * "ИКЕА органайзер BAXNA 204.743.66". Список у каждого пользователя свой и полностью редактируется.
 */
class WardrobeStorage extends Model
{
    protected $table = 'wardrobe_storages';

    protected $fillable = [
        'user_id',
        'name',
        'image',
        'note',
        'sort_order',
    ];

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function items()
    {
        return $this->hasMany(WardrobeItem::class, 'storage_id');
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

    // Стартовый набор — заводится один раз, если у пользователя ещё нет ни одного места
    // (как StockLocation::ensureDefaultsFor в "Моих запасах")
    public static function defaults(): array
    {
        return ['Тремпель', 'Полка', 'Ящик', 'Коробка'];
    }

    public static function ensureDefaultsFor(int $userId): void
    {
        if (static::where('user_id', $userId)->exists()) {
            return;
        }

        foreach (static::defaults() as $i => $name) {
            static::create(['user_id' => $userId, 'name' => $name, 'sort_order' => $i]);
        }
    }

    /**
     * id места по названию из формы вещи ("+ Новое место…"): существующее с таким названием
     * или новое в конце списка
     *
     * @param string|null $name
     * @param int $userId
     * @return int|null null — название пустое
     */
    public static function resolveId(?string $name, int $userId): ?int
    {
        $name = trim((string)$name);
        if ($name === '') {
            return null;
        }

        $storage = static::where('user_id', $userId)->where('name', $name)->first();
        if (!$storage) {
            $storage = static::create([
                'user_id' => $userId,
                'name' => $name,
                'sort_order' => (int)static::where('user_id', $userId)->max('sort_order') + 1,
            ]);
        }

        return $storage->id;
    }
}
