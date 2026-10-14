<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Запись "куда надевала" у образа: место/повод, дата (если помнится) и заметка.
 */
class WardrobeOutfitWear extends Model
{
    protected $table = 'wardrobe_outfit_wears';

    protected $fillable = [
        'outfit_id',
        'worn_at',
        'place',
        'note',
    ];

    protected $casts = [
        'worn_at' => 'date',
    ];

    public function outfit()
    {
        return $this->belongsTo(WardrobeOutfit::class, 'outfit_id');
    }
}
