<?php

namespace App\Models\Concerns;

use App\Components\WardrobeImage;
use App\Models\PostClothes;
use App\Models\User;
use App\Models\WardrobeItem;
use Illuminate\Support\Collection;

/**
 * Общее для справочников "Моего гардероба" с группами и образцами — принтов (WardrobePrint) и материалов
 * (WardrobeMaterial). Запись с user_id = null — общая, видна всем (список ведёт админ); с user_id — своя
 * запись пользователя, появляется, когда он в списке выбирает "Свой…" (resolveFromForm()).
 * Образец (image) — маленькая квадратная картинка; без неё показывается условный узор группы (print-select.css).
 *
 * Модель задаёт: GROUPS (ключ группы => название, порядок — порядок разделов в списке) и
 * FOREIGN_KEY (колонка со ссылкой на справочник у вещей гардероба и у пинов "Одежда").
 */
trait WardrobeDirectory
{
    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function items()
    {
        return $this->hasMany(WardrobeItem::class, static::FOREIGN_KEY);
    }

    public function postClothes()
    {
        return $this->hasMany(PostClothes::class, static::FOREIGN_KEY);
    }

    public function isShared(): bool
    {
        return $this->user_id === null;
    }

    public function groupLabel(): string
    {
        return static::GROUPS[$this->group] ?? static::GROUPS['other'];
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
     * Записи, которые видит пользователь: общие и его собственные
     */
    public function scopeAvailableTo($query, int $userId)
    {
        return $query->where(function ($q) use ($userId) {
            $q->whereNull('user_id')->orWhere('user_id', $userId);
        });
    }

    /**
     * Записи пользователя для списка в форме (parts/directory_select.blade.php): ключ группы => записи,
     * группы — в порядке GROUPS, внутри — как заведены (общее название группы стоит первым)
     *
     * @param int $userId
     * @return Collection
     */
    public static function groupedFor(int $userId): Collection
    {
        $entries = static::availableTo($userId)->orderBy('id')->get()->groupBy(function ($entry) {
            return isset(static::GROUPS[$entry->group]) ? $entry->group : 'other';
        });

        return collect(array_keys(static::GROUPS))
            ->filter(function ($group) use ($entries) {
                return isset($entries[$group]);
            })
            ->mapWithKeys(function ($group) use ($entries) {
                return [$group => $entries[$group]];
            });
    }

    /**
     * id записи по названию ("Свой…"): общая, если такая есть, иначе своя запись пользователя (создаётся
     * при первом упоминании, в группе "Другое"). Сравнение без учёта регистра — как в сопоставлении БД.
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

        $entry = static::availableTo($userId)
            ->where('name', $name)
            ->orderByRaw('user_id IS NULL DESC') // общая важнее своей
            ->first();

        if (!$entry) {
            $entry = static::create(['user_id' => $userId, 'group' => 'other', 'name' => $name]);
        }

        return $entry->id;
    }

    /**
     * Значение из формы вещи или пина (список parts/directory_select): id из списка — общая или своя запись;
     * "new" — своя запись с названием $newName; пусто — не указано
     *
     * @param mixed $value значение скрытого поля списка
     * @param string|null $newName название своей записи
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
     * Сделать запись общей. Свои записи пользователей с тем же названием становятся не нужны —
     * их вещи и пины переходят на общую, а сами они удаляются вместе со своими образцами.
     */
    public function makeShared(): void
    {
        $this->user_id = null;
        $this->save();

        $duplicates = static::where('name', $this->name)->where('id', '!=', $this->id)->get();
        if ($duplicates->isNotEmpty()) {
            $duplicateIds = $duplicates->pluck('id');
            WardrobeItem::whereIn(static::FOREIGN_KEY, $duplicateIds)->update([static::FOREIGN_KEY => $this->id]);
            PostClothes::whereIn(static::FOREIGN_KEY, $duplicateIds)->update([static::FOREIGN_KEY => $this->id]);
            foreach ($duplicates as $duplicate) {
                WardrobeImage::delete($duplicate->image_data);
                $duplicate->delete();
            }
        }
    }
}
