<?php

namespace App\Http\Controllers\Admin;

use App\Models\WardrobeColor;

/**
 * Админка → Цвета одежды: общий справочник цветов и цвета, добавленные пользователями.
 * Вся механика — в WardrobeDirectoryController (общая с принтами и материалами); вместо картинки-образца
 * у цвета задаётся сам цвет (hex, у двухцветных — hex2).
 */
class WardrobeColorController extends WardrobeDirectoryController
{
    protected function modelClass(): string
    {
        return WardrobeColor::class;
    }

    protected function directory(): array
    {
        return [
            'route' => 'admin.wardrobe-colors',
            'folder' => 'colors',
            'title' => 'Цвета одежды',
            'noun' => 'цвет',
            'Noun' => 'Цвет',
            'count' => 'Цветов',
            'field' => 'Цвет',
            'sample' => '',
            'swatchPrefix' => 'c-',
            'hex' => true,
        ];
    }
}
