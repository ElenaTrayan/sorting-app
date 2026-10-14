<?php

namespace App\Http\Controllers\Admin;

use App\Models\WardrobeMaterial;

/**
 * Админка → Материалы одежды: общий справочник материалов и материалы, добавленные пользователями.
 * Вся механика — в WardrobeDirectoryController (общая с принтами).
 */
class WardrobeMaterialController extends WardrobeDirectoryController
{
    protected function modelClass(): string
    {
        return WardrobeMaterial::class;
    }

    protected function directory(): array
    {
        return [
            'route' => 'admin.wardrobe-materials',
            'folder' => 'materials',
            'title' => 'Материалы одежды',
            'noun' => 'материал',
            'Noun' => 'Материал',
            'count' => 'Материалов',
            'field' => 'Материал',
            'sample' => 'фото ткани или фактуры',
            'swatchPrefix' => 'm-',
        ];
    }
}
