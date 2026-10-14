{{-- Превью вещи для галереи "Мой гардероб" — как превью пина в "Все посты" (posts/parts/post_preview.blade.php),
     на тех же стилях (.b-post-preview, .b-post-show__*, admin-panel.css) плюс свои .b-wardrobe-preview* (wardrobe.css).
     Открывается в Fancybox кликом по фото карточки (data-fancybox="wardrobe-gallery", wardrobe.js).

     Слева — фото: крупно одно, миниатюры переключают его (wardrobe.js); клик по крупному — полный размер с зумом
     и листанием всех фото вещи (группа wardrobe-item-photo-{id}: своя у каждой вещи — Fancybox держит в DOM
     сразу три соседних превью, с общей группой фото разных вещей смешались бы). Справа — данные вещи.

     @param \App\Models\WardrobeItem $item --}}
@php
    use App\Models\WardrobeItem;

    $photos = $item->photos->filter(fn ($photo) => $photo->image_url)->values();
    $styleLabels = WardrobeItem::labelsFor($item->styles, WardrobeItem::STYLES);
    $bodyTypeLabels = WardrobeItem::labelsFor($item->body_types, WardrobeItem::BODY_TYPES);

    $facts = array_filter([
        'Цвет' => $item->color ? view('user.wardrobe.parts.color_label', ['color' => $item->color])->render() : null,
        'Размер' => $item->size,
        'Длина' => $item->length,
        'Сезон' => implode(', ', WardrobeItem::labelsFor($item->seasons, \App\Models\WardrobeOutfit::SEASONS)),
        'Температура' => $item->temperatureLabel(),
        'Повод' => implode(', ', WardrobeItem::labelsFor($item->occasions, WardrobeItem::OCCASIONS)),
        'Принт' => $item->print_name,
        'Материал' => $item->material_name,
        'Состав' => $item->composition,
        'Состояние' => $item->isWishlist() ? null : (WardrobeItem::WEAR_LEVELS[$item->wear_level] ?? null),
        'Где лежит' => optional($item->storage)->name,
        'Цена' => $item->price ? number_format($item->price, 0, ',', ' ') : null,
        'Уход' => $item->careText(),
    ]);
@endphp
<div class="b-post-preview b-wardrobe-preview @if ($photos->isEmpty()) b-post-preview--text-only @endif">
    @if ($photos->isNotEmpty())
        <div class="b-post-preview__media b-wardrobe-preview__media">
            <div class="b-wardrobe-preview__stage">
                @foreach ($photos as $index => $photo)
                    <a href="{{ $photo->image_url }}" data-fancybox="wardrobe-item-photo-{{ $item->id }}"
                       class="b-wardrobe-preview__photo js-wardrobe-preview-photo" data-index="{{ $index }}" @if ($index > 0) hidden @endif>
                        <img src="{{ $photo->image_url }}" alt="{{ $item->name }}">
                    </a>
                @endforeach
                @if ($photos->count() > 1)
                    <span class="b-post-preview__media-badge"><i class="far fa-images"></i> {{ $photos->count() }}</span>
                @endif
            </div>
            @if ($photos->count() > 1)
                <div class="b-wardrobe-preview__thumbs">
                    @foreach ($photos as $index => $photo)
                        <button type="button" class="b-wardrobe-preview__thumb js-wardrobe-preview-thumb @if ($index === 0) is-active @endif"
                                data-index="{{ $index }}" style="background-image:url('{{ $photo->image_url }}')"
                                title="Фото {{ $index + 1 }}"></button>
                    @endforeach
                </div>
            @endif
        </div>
    @endif

    <div class="b-post-preview__body">
        <div class="b-post-show__meta">
            <span class="b-wardrobe-badge @if ($item->isWishlist()) b-wardrobe-badge--wishlist @endif">
                <i class="fas {{ $item->isWishlist() ? 'fa-shopping-bag' : 'fa-check' }}"></i> {{ WardrobeItem::STATUSES[$item->status] ?? '' }}
            </span>
            @if ($item->needs_repair)
                <span class="b-wardrobe-badge b-wardrobe-badge--repair"><i class="fas fa-tools"></i> Нужен ремонт</span>
            @endif
        </div>

        <div class="b-post-show__text">
            <h2 class="b-post-show__title">{{ $item->name }}</h2>
            <div class="b-wardrobe-preview__sub">{{ $item->categoryLabel() }}@if ($item->brand_name) · @include('user.wardrobe.parts.brand_label', ['brand' => $item->brand])@endif</div>
        </div>

        @if ($facts)
            <dl class="b-post-film-card__facts">
                @foreach ($facts as $label => $value)
                    <dt>{{ $label }}</dt>
                    {{-- "Цвет" — готовая разметка с кружком цвета (parts/color_label), остальное — текст --}}
                    <dd>@if ($label === 'Цвет'){!! $value !!}@else{{ $value }}@endif</dd>
                @endforeach
            </dl>
        @endif

        @if ($styleLabels || $bodyTypeLabels)
            <div class="b-post-show__tags">
                @foreach ($styleLabels as $label)
                    <span class="b-post-show__tag">{{ $label }}</span>
                @endforeach
                @foreach ($bodyTypeLabels as $label)
                    <span class="b-post-show__category-chip" title="Кому подходит">{{ $label }}</span>
                @endforeach
            </div>
        @endif

        {{-- с чем сочетается: обувь / аксессуары / остальное (WardrobeItem::compatibleByGroup); клик — превью той вещи
             поверх этого (своя Fancybox-группа на вещь и раздел, wardrobe.js) --}}
        @foreach ($item->compatibleByGroup() as $group => $compatItems)
            <div class="b-wardrobe-preview__compat">
                <div class="b-wardrobe-preview__compat-title">{{ WardrobeItem::COMPAT_GROUPS[$group] }}</div>
                <div class="b-wardrobe-selected">
                    @foreach ($compatItems as $compatItem)
                        <a href="{{ route('wardrobe.items.preview', $compatItem) }}" data-fancybox="wardrobe-compat-{{ $item->id }}-{{ $group }}" data-type="ajax"
                           class="b-wardrobe-selected__item @if ($compatItem->isWishlist()) is-wishlist @endif" title="{{ $compatItem->name }}">
                            <div class="b-wardrobe-selected__photo" @if ($compatItem->image_url) style="background-image:url('{{ $compatItem->image_url }}')" @endif>
                                @if (!$compatItem->image_url)<i class="fas fa-tshirt"></i>@endif
                            </div>
                            <div class="b-wardrobe-selected__name">{{ $compatItem->name }}</div>
                        </a>
                    @endforeach
                </div>
            </div>
        @endforeach

        @if ($item->needs_repair && $item->repair_note)
            <p class="b-wardrobe-preview__note"><b>Ремонт:</b> {{ $item->repair_note }}</p>
        @endif
        @if ($item->note)
            <p class="b-wardrobe-preview__note">{{ $item->note }}</p>
        @endif

        <div class="b-post-preview__actions">
            {{-- открывает общую форму вещи (wardrobe.js закрывает галерею перед этим) --}}
            <button type="button" class="b-post-show__btn js-wardrobe-item-edit"
                    data-wardrobe-item="{{ json_encode($item->toFormArray()) }}"
                    data-update-url="{{ route('wardrobe.items.update', $item) }}">
                <i class="fas fa-pencil-alt"></i> Редактировать
            </button>
            @if ($item->shop_url)
                <a href="{{ $item->shop_url }}" class="b-post-show__btn" target="_blank" rel="noopener noreferrer">
                    <i class="fas fa-shopping-bag"></i> Открыть магазин
                </a>
            @endif
            @if ($item->post_id && auth()->user()->hasService('posts'))
                <a href="{{ route('posts.show', $item->post_id) }}" class="b-post-show__btn">
                    <i class="far fa-image"></i> Открыть пост
                </a>
            @endif
        </div>
    </div>
</div>
