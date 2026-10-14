{{--
    Поле "Бренд" — список брендов с логотипами и поиском: в форме вещи "Моего гардероба" и в полях пина
    "Одежда". Справочник — WardrobeBrand (общие бренды + свои бренды пользователя, по алфавиту). Сам список —
    parts/directory_select (общий с принтами и материалами), только без разделов и с логотипом вместо образца.
    Сервер: WardrobeBrand::resolveFromForm().

    @param string $idName имя скрытого поля с id бренда
    @param string $newName имя поля "название своего бренда"
    @param int|null $selectedId выбранный бренд (форма редактирования пина); в форме вещи значение ставит wardrobe.js
--}}
@include('user.wardrobe.parts.directory_select', [
    'kind' => 'brand',
    // один раздел без заголовка
    'groups' => collect(['brand' => \App\Models\WardrobeBrand::availableTo((int)auth()->id())->orderBy('name')->get()])
        ->filter->isNotEmpty(),
    'groupLabels' => ['brand' => ''],
    'swatchPrefix' => '',
    'texts' => [
        'empty' => 'Без бренда',
        'new' => 'Свой бренд…',
        'newPlaceholder' => 'Название своего бренда',
        'search' => 'Поиск бренда',
        'notFound' => 'Такого бренда в списке нет',
    ],
    'idName' => $idName,
    'newName' => $newName,
    'selectedId' => $selectedId ?? null,
])
