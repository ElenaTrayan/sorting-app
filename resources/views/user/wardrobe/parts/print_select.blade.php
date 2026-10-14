{{--
    Поле "Принт" — список принтов с образцами и поиском: в форме вещи "Моего гардероба" и в полях пина "Одежда".
    Справочник — WardrobePrint (общие принты + свои принты пользователя). Сам список — parts/directory_select
    (общий с материалами). Сервер: WardrobePrint::resolveFromForm().

    @param string $idName имя скрытого поля с id принта
    @param string $newName имя поля "название своего принта"
    @param int|null $selectedId выбранный принт (форма редактирования пина); в форме вещи значение ставит wardrobe.js
--}}
@include('user.wardrobe.parts.directory_select', [
    'kind' => 'print',
    'groups' => \App\Models\WardrobePrint::groupedFor((int)auth()->id()),
    'groupLabels' => \App\Models\WardrobePrint::GROUPS,
    'swatchPrefix' => '',
    'texts' => [
        'empty' => 'Не указан',
        'new' => 'Свой принт…',
        'newPlaceholder' => 'Название своего принта',
        'search' => 'Поиск принта',
        'notFound' => 'Такого принта в списке нет',
    ],
    'idName' => $idName,
    'newName' => $newName,
    'selectedId' => $selectedId ?? null,
])
