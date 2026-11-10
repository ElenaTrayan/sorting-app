{{--
    Простой список с поиском в форме вещи "Моего гардероба" — "Длина" (WardrobeItem::LENGTHS) и "Место хранения"
    (места пользователя). Разметка и поведение — как у размеров (parts/size_select) и остальных списков
    (parts/directory_select, public/admin/print-select.js): без образцов и без разделов.
    Значение уходит скрытым полем $name: значение из списка, "new" (тогда текст — в поле $newName) или пусто.

    @param string $kind что за список (data-select — по нему значение ставит wardrobe.js)
    @param string $name имя скрытого поля со значением
    @param string|null $newName имя поля "своё значение"; null — списка без "Своё…" (только значения из списка)
    @param string $icon иконка Font Awesome слева
    @param array $options значение => название
    @param array $texts подписи: empty, search, notFound, new, newPlaceholder (последние две — только с $newName)
    @param bool $free значение-текст не из списка показывать как "своё" (длина хранится текстом, место — id)
    @param int $maxlength длина поля "своё значение"
--}}
@include('user.wardrobe.parts.select_assets')
<div class="b-print-select js-print-select" data-select="{{ $kind }}" @if (!empty($free)) data-free="1" @endif>
    <input type="hidden" name="{{ $name }}" class="js-print-select-value" value="">

    <button type="button" class="b-print-select__toggle js-print-select-toggle" aria-haspopup="listbox" aria-expanded="false">
        <i class="fas {{ $icon }} b-print-select__icon"></i>
        <span class="b-print-select__label js-print-select-label">{{ $texts['empty'] }}</span>
        <i class="fas fa-chevron-down b-print-select__chevron"></i>
    </button>

    <div class="b-print-select__dropdown js-print-select-dropdown" hidden>
        <div class="b-print-select__search">
            <i class="fas fa-search"></i>
            <input type="text" class="js-print-select-search" placeholder="{{ $texts['search'] }}" autocomplete="off">
        </div>
        <div class="b-print-select__list js-print-select-list" role="listbox">
            <button type="button" class="b-print-select__option js-print-select-option" data-id="" data-name="{{ $texts['empty'] }}">
                <span>{{ $texts['empty'] }}</span>
            </button>

            @foreach ($options as $value => $label)
                <button type="button" class="b-print-select__option js-print-select-option"
                        data-id="{{ $value }}" data-name="{{ $label }}" data-search="{{ mb_strtolower($label) }}">
                    <span>{{ $label }}</span>
                </button>
            @endforeach

            <div class="b-print-select__empty js-print-select-empty" hidden>{{ $texts['notFound'] }}</div>

            @if (!empty($newName))
                <button type="button" class="b-print-select__option b-print-select__option--new js-print-select-option" data-id="new" data-name="{{ \Illuminate\Support\Str::replaceLast('…', '', $texts['new']) }}">
                    <span><i class="fas fa-plus"></i> {{ $texts['new'] }}</span>
                </button>
            @endif
        </div>
    </div>

    @if (!empty($newName))
        <input type="text" name="{{ $newName }}" class="b-print-select__new js-print-select-new" maxlength="{{ $maxlength }}"
               placeholder="{{ $texts['newPlaceholder'] }}" autocomplete="off" hidden>
    @else
        {{-- без "Своё…" (группа стирки): поле без имени — print-select.js оно нужно, на сервер не уходит --}}
        <input type="text" class="js-print-select-new" hidden disabled>
    @endif
</div>
