<?php

namespace App\Models;

use App\Models\Concerns\WardrobeDirectory;
use Illuminate\Database\Eloquent\Model;

/**
 * Принт одежды — справочник для вещей "Моего гардероба" и пинов "Одежда": общие принты (админка →
 * Принты одежды) и свои принты пользователя. Вся механика — в WardrobeDirectory (общая с материалами).
 * Условные узоры групп — .b-print-swatch--{group} в print-select.css.
 */
class WardrobePrint extends Model
{
    use WardrobeDirectory;

    protected $table = 'wardrobe_prints';

    // колонка со ссылкой на принт у вещей и пинов
    public const FOREIGN_KEY = 'print_id';

    // порядок групп — порядок разделов в списке принтов
    public const GROUPS = [
        'plain' => 'Однотонные и фактурные',
        'stripes' => 'Полоска',
        'checks' => 'Клетка',
        'dots' => 'Горох и точки',
        'floral' => 'Цветочные',
        'animal' => 'Анималистичные',
        'geometric' => 'Геометрия',
        'ethnic' => 'Орнаменты и этника',
        'nature' => 'Природа и животные',
        'abstract' => 'Абстракция',
        'text' => 'Надписи и логотипы',
        'novelty' => 'Сюжетные',
        'other' => 'Другое',
    ];

    protected $fillable = [
        'user_id',
        'group',
        'name',
        'image',
    ];
}
