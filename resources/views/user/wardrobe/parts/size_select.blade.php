{{--
    Поле "Размер" в форме вещи "Моего гардероба" — список размеров с поиском (WardrobeItem::SIZES) и "Свой размер…".
    Разметка и поведение — как у списков принтов, материалов, цветов и брендов (parts/directory_select,
    public/admin/print-select.js), только без образцов и без справочника в БД: размер хранится текстом.
    data-free: значение, которого нет в списке (размер, вписанный раньше или свой), JS показывает как "Свой размер…".
    Значение уходит скрытым полем $name: размер из списка, "new" (тогда размер — в поле $newName) или пусто.

    @param string $name имя скрытого поля с размером
    @param string $newName имя поля "свой размер"
--}}
@include('user.wardrobe.parts.select_assets')
<div class="b-print-select js-print-select" data-select="size" data-free="1">
    <input type="hidden" name="{{ $name }}" class="js-print-select-value" value="">

    <button type="button" class="b-print-select__toggle js-print-select-toggle" aria-haspopup="listbox" aria-expanded="false">
        <i class="fas fa-ruler b-print-select__icon"></i>
        <span class="b-print-select__label js-print-select-label">Не указан</span>
        <i class="fas fa-chevron-down b-print-select__chevron"></i>
    </button>

    <div class="b-print-select__dropdown js-print-select-dropdown" hidden>
        <div class="b-print-select__search">
            <i class="fas fa-search"></i>
            <input type="text" class="js-print-select-search" placeholder="Поиск размера" autocomplete="off">
        </div>
        <div class="b-print-select__list js-print-select-list" role="listbox">
            <button type="button" class="b-print-select__option js-print-select-option" data-id="" data-name="Не указан">
                <span>Не указан</span>
            </button>

            @foreach (\App\Models\WardrobeItem::SIZES as $groupLabel => $sizes)
                <div class="b-print-select__group js-print-select-group">
                    <div class="b-print-select__group-title">{{ $groupLabel }}</div>
                    <div class="b-print-select__sizes">
                        @foreach ($sizes as $size)
                            <button type="button" class="b-print-select__option b-print-select__option--size js-print-select-option"
                                    data-id="{{ $size }}" data-name="{{ $size }}" data-search="{{ mb_strtolower($size . ' ' . $groupLabel) }}">
                                <span>{{ $size }}</span>
                            </button>
                        @endforeach
                    </div>
                </div>
            @endforeach

            <div class="b-print-select__empty js-print-select-empty" hidden>Такого размера в списке нет</div>

            <button type="button" class="b-print-select__option b-print-select__option--new js-print-select-option" data-id="new" data-name="Свой размер">
                <span><i class="fas fa-plus"></i> Свой размер…</span>
            </button>
        </div>
    </div>

    <input type="text" name="{{ $newName }}" class="b-print-select__new js-print-select-new" maxlength="50"
           placeholder="Свой размер, напр. 75B или 44–46" autocomplete="off" hidden>
</div>
