{{-- Карточка вещи в "Мой гардероб". $item — WardrobeItem. Клик по карточке открывает общую форму
     (parts/item_form_modal.blade.php), данные вещи — в data-wardrobe-item (wardrobe.js). --}}
@php
    $styleLabels = \App\Models\WardrobeItem::labelsFor($item->styles, \App\Models\WardrobeItem::STYLES);
    $bodyTypeLabels = \App\Models\WardrobeItem::labelsFor($item->body_types, \App\Models\WardrobeItem::BODY_TYPES);
    $photoUrls = $item->photos->pluck('image_url')->filter()->values();
@endphp
<div class="b-wardrobe-card @if ($item->isWishlist()) b-wardrobe-card--wishlist @endif">
    {{-- клик по фото — галерея, как в "Все посты": превью вещи со всеми фото (parts/item_preview.blade.php),
         стрелки — к соседним вещам; форма вещи — "Редактировать" в меню "..." или в превью (wardrobe.js) --}}
    <a href="{{ route('wardrobe.items.preview', $item) }}" data-fancybox="wardrobe-gallery" data-type="ajax"
       class="b-wardrobe-card__photo"
       @if ($item->image_url) style="background-image:url('{{ $item->image_url }}')" @endif>
        @if (!$item->image_url)<i class="fas fa-tshirt"></i>@endif

        <span class="b-wardrobe-card__badges">
            @if ($item->isWishlist())<span class="b-wardrobe-badge b-wardrobe-badge--wishlist"><i class="fas fa-shopping-bag"></i> Купить</span>@endif
            @if ($item->needs_repair)<span class="b-wardrobe-badge b-wardrobe-badge--repair" title="{{ $item->repair_note }}"><i class="fas fa-tools"></i> Ремонт</span>@endif
        </span>
        @if ($photoUrls->count() > 1)
            <span class="b-wardrobe-card__photos" title="Фото вещи: {{ $photoUrls->count() }}"><i class="far fa-images"></i> {{ $photoUrls->count() }}</span>
        @endif
    </a>

    <div class="b-wardrobe-card__body">
        <div class="b-wardrobe-card__head">
            <div style="flex:1;min-width:0;">
                <div class="b-wardrobe-card__name">{{ $item->name }}</div>
                <div class="b-wardrobe-card__sub">{{ $item->categoryLabel() }}@if ($item->brand_name) · @include('user.wardrobe.parts.brand_label', ['brand' => $item->brand])@endif</div>
            </div>
            <div class="b-stock-more">
                <button type="button" class="b-stock-more__btn"><i class="fas fa-ellipsis-v"></i></button>
                <div class="b-stock-more__menu">
                    <button type="button" class="js-wardrobe-item-edit" data-wardrobe-item="{{ json_encode($item->toFormArray()) }}" data-update-url="{{ route('wardrobe.items.update', $item) }}"><i class="fas fa-pen"></i> Редактировать</button>
                    @if ($item->isWishlist())
                        <button type="button" data-stock-post-url="{{ route('wardrobe.items.bought', $item) }}" data-success-message="Вещь перенесена в гардероб"><i class="fas fa-check"></i> Купила</button>
                        @if ($item->shop_url)
                            <a href="{{ $item->shop_url }}" target="_blank" rel="noopener"><i class="fas fa-external-link-alt"></i> Открыть магазин</a>
                        @endif
                    @endif
                    @if ($item->post_id && auth()->user()->hasService('posts'))
                        <a href="{{ route('posts.show', $item->post_id) }}"><i class="far fa-image"></i> Открыть пост</a>
                    @endif
                    <button type="button" class="b-stock-more__danger" data-stock-delete-url="{{ route('wardrobe.items.destroy', $item) }}" data-stock-delete-title="Удалить «{{ $item->name }}»?" data-stock-delete-text="Вещь исчезнет и из образов, в которые входит."><i class="fas fa-trash"></i> Удалить</button>
                </div>
            </div>
        </div>

        <div class="b-wardrobe-card__meta">
            @if ($item->color)<span>@include('user.wardrobe.parts.color_label', ['color' => $item->color])</span>@endif
            @if ($item->size)<span><i class="fas fa-ruler"></i> {{ $item->size }}</span>@endif
            @if ($item->material_name)<span><i class="fas fa-layer-group"></i> {{ $item->material_name }}</span>@endif
            @if ($item->storage)<span><i class="fas fa-box-open"></i> {{ $item->storage->name }}</span>@endif
            @if ($item->isWishlist() && $item->price)<span><i class="fas fa-tag"></i> {{ number_format($item->price, 0, ',', ' ') }}</span>@endif
            @if (!$item->isWishlist() && $item->wear_level)<span><i class="fas fa-heartbeat"></i> {{ \App\Models\WardrobeItem::WEAR_LEVELS[$item->wear_level] ?? '' }}</span>@endif
        </div>

        @if ($styleLabels || $bodyTypeLabels)
            <div class="b-wardrobe-card__chips">
                @foreach ($styleLabels as $label)<span class="b-stock-chip">{{ $label }}</span>@endforeach
                @foreach ($bodyTypeLabels as $label)<span class="b-stock-chip b-wardrobe-chip--type">{{ $label }}</span>@endforeach
            </div>
        @endif
    </div>
</div>
