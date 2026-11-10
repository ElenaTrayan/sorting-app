{{--
    Поле "Тип вещи" в форме вещи "Моего гардероба" — типы по разделам-категориям (WardrobeCategory::kindsBySlug,
    ведёт админ) с поиском и "Свой тип…". Разметка и поведение — как у размеров (parts/size_select,
    public/admin/print-select.js), без образцов: тип хранится текстом.
    data-category у типа — его категория: при выборе типа wardrobe.js ставит её в поле "Категория".
    Значение уходит скрытым полем kind: тип из списка, "new" (тогда тип — в поле kind_new) или пусто.
--}}
@include('user.wardrobe.parts.select_assets')
<div class="b-print-select js-print-select" data-select="kind" data-free="1">
    <input type="hidden" name="kind" class="js-print-select-value" value="">

    <button type="button" class="b-print-select__toggle js-print-select-toggle" aria-haspopup="listbox" aria-expanded="false">
        <i class="fas fa-tshirt b-print-select__icon"></i>
        <span class="b-print-select__label js-print-select-label">Не указан</span>
        <i class="fas fa-chevron-down b-print-select__chevron"></i>
    </button>

    <div class="b-print-select__dropdown js-print-select-dropdown" hidden>
        <div class="b-print-select__search">
            <i class="fas fa-search"></i>
            <input type="text" class="js-print-select-search" placeholder="Поиск: худи, тренч, кеды…" autocomplete="off">
        </div>
        <div class="b-print-select__list js-print-select-list" role="listbox">
            <button type="button" class="b-print-select__option js-print-select-option" data-id="" data-name="Не указан">
                <span>Не указан</span>
            </button>

            @foreach (\App\Models\WardrobeCategory::kindsBySlug() as $category => $kinds)
                @php $categoryLabel = \App\Models\WardrobeCategory::label($category); @endphp
                <div class="b-print-select__group js-print-select-group">
                    <div class="b-print-select__group-title">{{ $categoryLabel }}</div>
                    @foreach ($kinds as $kind)
                        <button type="button" class="b-print-select__option js-print-select-option"
                                data-id="{{ $kind }}" data-name="{{ $kind }}" data-category="{{ $category }}"
                                data-search="{{ mb_strtolower($kind . ' ' . $categoryLabel) }}">
                            <span>{{ $kind }}</span>
                        </button>
                    @endforeach
                </div>
            @endforeach

            <div class="b-print-select__empty js-print-select-empty" hidden>Такого типа в списке нет</div>

            <button type="button" class="b-print-select__option b-print-select__option--new js-print-select-option" data-id="new" data-name="Свой тип">
                <span><i class="fas fa-plus"></i> Свой тип…</span>
            </button>
        </div>
    </div>

    <input type="text" name="kind_new" class="b-print-select__new js-print-select-new" maxlength="50"
           placeholder="Свой тип, напр. кейп или кимоно" autocomplete="off" hidden>
</div>
