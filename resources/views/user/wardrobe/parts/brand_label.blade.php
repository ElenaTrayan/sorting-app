{{-- Бренд одежды: логотип (если админ его загрузил) и название. Стили — .b-brand-logo (admin-panel.css).

     @param \App\Models\WardrobeBrand|null $brand --}}
@if ($brand)@if ($brand->image_url)<img src="{{ $brand->image_url }}" class="b-brand-logo" alt="" loading="lazy">@endif{{ $brand->name }}@endif
