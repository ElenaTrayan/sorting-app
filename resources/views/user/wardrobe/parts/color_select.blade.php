{{--
    Поле "Цвет" — список цветов с образцами и поиском: в форме вещи "Моего гардероба" и в полях пина "Одежда".
    Справочник — WardrobeColor (общие цвета + свои цвета пользователя). Сам список — parts/directory_select
    (общий с принтами, материалами и брендами); образец — сам цвет, у "Свой цвет…" — название и палитра.
    Сервер: WardrobeColor::resolveFromForm().

    @param string $idName имя скрытого поля с id цвета
    @param string $newName имя поля "название своего цвета"
    @param string $newColorName имя поля "оттенок своего цвета" (#rrggbb)
    @param int|null $selectedId выбранный цвет (форма редактирования пина); в форме вещи значение ставит wardrobe.js
--}}
@include('user.wardrobe.parts.directory_select', [
    'kind' => 'color',
    'groups' => \App\Models\WardrobeColor::groupedFor((int)auth()->id()),
    'groupLabels' => \App\Models\WardrobeColor::GROUPS,
    'swatchPrefix' => 'c-',
    'texts' => [
        'empty' => 'Не указан',
        'new' => 'Свой цвет…',
        'newPlaceholder' => 'Название своего цвета',
        'search' => 'Поиск цвета',
        'notFound' => 'Такого цвета в списке нет',
    ],
    'idName' => $idName,
    'newName' => $newName,
    'newColorName' => $newColorName,
    'selectedId' => $selectedId ?? null,
])
