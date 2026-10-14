<?php

namespace App\Http\Controllers\Admin;

use App\Models\WardrobePrint;

/**
 * Админка → Принты одежды: общий справочник принтов и принты, добавленные пользователями.
 * Вся механика — в WardrobeDirectoryController (общая с материалами).
 */
class WardrobePrintController extends WardrobeDirectoryController
{
    protected function modelClass(): string
    {
        return WardrobePrint::class;
    }

    protected function directory(): array
    {
        return [
            'route' => 'admin.wardrobe-prints',
            'folder' => 'prints',
            'title' => 'Принты одежды',
            'noun' => 'принт',
            'Noun' => 'Принт',
            'count' => 'Принтов',
            'field' => 'Принт',
            'sample' => 'картинка узора',
            'swatchPrefix' => '',
        ];
    }
}
