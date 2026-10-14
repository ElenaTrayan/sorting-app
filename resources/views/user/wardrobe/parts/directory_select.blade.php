{{--
    Список значений справочника с образцами и поиском — общий для принтов (parts/print_select), материалов
    (parts/material_select) и брендов (parts/brand_select — логотип вместо образца, без разделов):
    и в форме вещи "Моего гардероба", и в полях пина "Одежда".
    Разделы — группы справочника. Образец — картинка значения, а пока её не загрузили — условный узор группы
    (.b-print-swatch--{swatchPrefix}{group}, print-select.css).
    Значение уходит скрытым полем $idName: id из справочника, "new" (тогда название — в поле $newName) или пусто.
    Поведение — public/admin/print-select.js (window.printSelect).

    @param string $kind что за справочник: print | material | brand (data-select — по нему форму находит wardrobe.js)
    @param \Illuminate\Support\Collection $groups ключ группы => значения (модели с id, name, image_url, isShared())
    @param array $groupLabels ключ группы => название раздела
    @param string $swatchPrefix приставка класса узора группы ('' у принтов, 'm-' у материалов)
    @param array $texts подписи: empty, new, newPlaceholder, search, notFound
    @param string $idName имя скрытого поля с id
    @param string $newName имя поля "название своего значения"
    @param int|null $selectedId выбранное значение (форма редактирования пина); в форме вещи его ставит wardrobe.js
    @param string|null $newColorName только у цветов (parts/color_select): имя поля "оттенок своего цвета" (палитра);
           у значений цветов образец — не картинка, а сам цвет ($entry->swatch_style → data-style)
--}}
@php
    $selectedId = $selectedId ?? null;
    $newColorName = $newColorName ?? null;
@endphp
@include('user.wardrobe.parts.select_assets')
<div class="b-print-select js-print-select" data-select="{{ $kind }}">
    <input type="hidden" name="{{ $idName }}" class="js-print-select-value" value="{{ $selectedId }}">

    <button type="button" class="b-print-select__toggle js-print-select-toggle" aria-haspopup="listbox" aria-expanded="false">
        <span class="b-print-swatch b-print-swatch--none js-print-select-swatch"></span>
        <span class="b-print-select__label js-print-select-label">{{ $texts['empty'] }}</span>
        <i class="fas fa-chevron-down b-print-select__chevron"></i>
    </button>

    <div class="b-print-select__dropdown js-print-select-dropdown" hidden>
        <div class="b-print-select__search">
            <i class="fas fa-search"></i>
            <input type="text" class="js-print-select-search" placeholder="{{ $texts['search'] }}" autocomplete="off">
        </div>
        <div class="b-print-select__list js-print-select-list" role="listbox">
            <button type="button" class="b-print-select__option js-print-select-option" data-id="" data-name="{{ $texts['empty'] }}" data-swatch="none">
                <span class="b-print-swatch b-print-swatch--none"></span>
                <span>{{ $texts['empty'] }}</span>
            </button>

            @foreach ($groups as $group => $entries)
                <div class="b-print-select__group js-print-select-group">
                    {{-- у брендов раздел один и без названия --}}
                    @if ($groupLabels[$group] !== '')
                        <div class="b-print-select__group-title">{{ $groupLabels[$group] }}</div>
                    @endif
                    @foreach ($entries as $entry)
                        <button type="button" class="b-print-select__option js-print-select-option"
                                data-id="{{ $entry->id }}" data-name="{{ $entry->name }}" data-swatch="{{ $swatchPrefix . $group }}"
                                data-search="{{ mb_strtolower($entry->name . ' ' . $groupLabels[$group]) }}"
                                @if ($entry->image_url) data-image="{{ $entry->image_url }}" @endif
                                @if ($entry->swatch_style) data-style="{{ $entry->swatch_style }}" @endif>
                            {{-- картинку образца ставит JS при первом открытии списка — чтобы страница не тянула сотни картинок сразу --}}
                            <span class="b-print-swatch b-print-swatch--{{ $swatchPrefix . $group }}" @if ($entry->swatch_style) style="{{ $entry->swatch_style }}" @endif></span>
                            <span>{{ $entry->name }}</span>
                            @if (!$entry->isShared())<span class="b-print-select__own">свой</span>@endif
                        </button>
                    @endforeach
                </div>
            @endforeach

            <div class="b-print-select__empty js-print-select-empty" hidden>{{ $texts['notFound'] }}</div>

            <button type="button" class="b-print-select__option b-print-select__option--new js-print-select-option" data-id="new" data-name="{{ \Illuminate\Support\Str::replaceLast('…', '', $texts['new']) }}" data-swatch="none">
                <span class="b-print-swatch b-print-swatch--none"><i class="fas fa-plus"></i></span>
                <span>{{ $texts['new'] }}</span>
            </button>
        </div>
    </div>

    <input type="text" name="{{ $newName }}" class="b-print-select__new js-print-select-new" maxlength="150"
           placeholder="{{ $texts['newPlaceholder'] }}" autocomplete="off" hidden>
    @if ($newColorName)
        {{-- disabled, пока не выбрано "Свой цвет…": иначе палитра отправляла бы свой оттенок с каждой формой --}}
        <label class="b-print-select__new-color js-print-select-new-color" hidden>
            <input type="color" name="{{ $newColorName }}" value="#c9cfe0" disabled>
            <span>Оттенок своего цвета</span>
        </label>
    @endif
</div>
