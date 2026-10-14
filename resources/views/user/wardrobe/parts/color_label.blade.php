{{-- Цвет одежды: кружок самого цвета (если он задан) и название. Стили — .b-color-dot (admin-panel.css).

     @param \App\Models\WardrobeColor|null $color --}}
@if ($color)@if ($color->swatch_style)<span class="b-color-dot" style="{{ $color->swatch_style }}"></span>@elseif ($color->group === 'multi')<span class="b-color-dot b-color-dot--multi"></span>@endif{{ $color->name }}@endif
