{{--
    Поле "Материал" — список материалов с образцами и поиском: в форме вещи "Моего гардероба" и в полях пина
    "Одежда". Справочник — WardrobeMaterial (общие материалы + свои материалы пользователя). Сам список —
    parts/directory_select (общий с принтами). Сервер: WardrobeMaterial::resolveFromForm().

    @param string $idName имя скрытого поля с id материала
    @param string $newName имя поля "название своего материала"
    @param int|null $selectedId выбранный материал (форма редактирования пина); в форме вещи значение ставит wardrobe.js
--}}
@include('user.wardrobe.parts.directory_select', [
    'kind' => 'material',
    'groups' => \App\Models\WardrobeMaterial::groupedFor((int)auth()->id()),
    'groupLabels' => \App\Models\WardrobeMaterial::GROUPS,
    'swatchPrefix' => 'm-',
    'texts' => [
        'empty' => 'Не указан',
        'new' => 'Свой материал…',
        'newPlaceholder' => 'Название своего материала',
        'search' => 'Поиск материала',
        'notFound' => 'Такого материала в списке нет',
    ],
    'idName' => $idName,
    'newName' => $newName,
    'selectedId' => $selectedId ?? null,
])
