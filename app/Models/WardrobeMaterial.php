<?php

namespace App\Models;

use App\Models\Concerns\WardrobeDirectory;
use Illuminate\Database\Eloquent\Model;

/**
 * Материал одежды — справочник для вещей "Моего гардероба" и пинов "Одежда": общие материалы (админка →
 * Материалы одежды) и свои материалы пользователя. Вся механика — в WardrobeDirectory (общая с принтами).
 * Условные узоры групп — .b-print-swatch--m-{group} в print-select.css.
 */
class WardrobeMaterial extends Model
{
    use WardrobeDirectory;

    protected $table = 'wardrobe_materials';

    // колонка со ссылкой на материал у вещей и пинов
    public const FOREIGN_KEY = 'material_id';

    // порядок групп — порядок разделов в списке материалов
    public const GROUPS = [
        'plant' => 'Растительные волокна',
        'animal' => 'Шерсть и шёлк',
        'artificial' => 'Искусственные волокна',
        'synthetic' => 'Синтетика',
        'woven' => 'Ткани',
        'knit' => 'Трикотаж',
        'leather' => 'Кожа и мех',
        'filling' => 'Утеплители и мембраны',
        'hard' => 'Металл, камни и другое',
        'other' => 'Другое',
    ];

    protected $fillable = [
        'user_id',
        'group',
        'name',
        'image',
    ];
}
