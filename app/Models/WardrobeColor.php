<?php

namespace App\Models;

use App\Models\Concerns\WardrobeDirectory;
use Illuminate\Database\Eloquent\Model;

/**
 * Цвет одежды — справочник для вещей "Моего гардероба" и пинов "Одежда": общие цвета (админка → Цвета одежды)
 * и свои цвета пользователя. Механика — в WardrobeDirectory (общая с принтами и материалами), только вместо
 * картинки-образца — сам цвет: hex (#RRGGBB), у двухцветных ещё hex2; без hex — условный образец группы
 * (.b-print-swatch--c-{group} в print-select.css: радуга у "Разноцветных").
 */
class WardrobeColor extends Model
{
    use WardrobeDirectory {
        resolveFromForm as protected resolveDirectoryFromForm;
    }

    protected $table = 'wardrobe_colors';

    // колонка со ссылкой на цвет у вещей и пинов
    public const FOREIGN_KEY = 'color_id';

    // порядок групп — порядок разделов в списке цветов
    public const GROUPS = [
        'white' => 'Белые и молочные',
        'beige' => 'Бежевые и песочные',
        'brown' => 'Коричневые',
        'grey' => 'Серые',
        'black' => 'Чёрные',
        'red' => 'Красные',
        'burgundy' => 'Бордовые и винные',
        'pink' => 'Розовые',
        'orange' => 'Оранжевые и коралловые',
        'yellow' => 'Жёлтые',
        'green' => 'Зелёные',
        'teal' => 'Бирюзовые и мятные',
        'blue' => 'Голубые',
        'navy' => 'Синие',
        'purple' => 'Фиолетовые и сиреневые',
        'metallic' => 'Металлик',
        'multi' => 'Разноцветные',
        'other' => 'Другое',
    ];

    protected $fillable = [
        'user_id',
        'group',
        'name',
        'hex',
        'hex2',
    ];

    /**
     * CSS образца цвета (для style=""): заливка, у двухцветных — пополам по диагонали; null — образца нет
     *
     * @return string|null
     */
    public function getSwatchStyleAttribute(): ?string
    {
        $hex = self::normalizeHex($this->hex);
        $hex2 = self::normalizeHex($this->hex2);
        if ($hex === null) {
            return null;
        }

        return $hex2 === null
            ? 'background:' . $hex
            : 'background:linear-gradient(135deg,' . $hex . ' 50%,' . $hex2 . ' 50%)';
    }

    /**
     * "#a1b2c3", "A1B2C3" → "#A1B2C3"; всё остальное — null
     *
     * @param mixed $value
     * @return string|null
     */
    public static function normalizeHex($value): ?string
    {
        $value = ltrim(trim((string)$value), '#');

        return preg_match('/^[0-9a-fA-F]{6}$/', $value) ? '#' . strtoupper($value) : null;
    }

    /**
     * Значение из формы вещи или пина (список parts/color_select). Как у остальных справочников, плюс оттенок
     * своего цвета: "new" + название + hex из палитры. Оттенок записывается только своему цвету пользователя —
     * если название совпало с общим цветом, берётся общий как есть.
     *
     * @param mixed $value значение скрытого поля списка
     * @param string|null $newName название своего цвета
     * @param int $userId
     * @param mixed $newHex оттенок своего цвета
     * @return int|null
     */
    public static function resolveFromForm($value, ?string $newName, int $userId, $newHex = null): ?int
    {
        $id = static::resolveDirectoryFromForm($value, $newName, $userId);
        $hex = self::normalizeHex($newHex);

        if ($value === 'new' && $id !== null && $hex !== null) {
            static::whereKey($id)->where('user_id', $userId)->update(['hex' => $hex, 'hex2' => null]);
        }

        return $id;
    }
}
