{{-- Карточка вещи из post_clothes (пин "Одежда") — на странице поста, под текстом. Стили — те же, что у карточки
     фильма (parts/film_card.blade.php, .b-post-film-card__* в admin-panel.css).

     @param \App\Models\PostClothes $clothes --}}
@php
    use App\Models\WardrobeItem;

    /** @var \App\Models\PostClothes $clothes */
    $collection = $clothes->collection_year ? 'Коллекция ' . $clothes->collection_year : null;

    $facts = array_filter([
        'Цвет' => $clothes->color ? view('user.wardrobe.parts.color_label', ['color' => $clothes->color])->render() : null,
        'Принт' => optional($clothes->print)->name,
        'Материал' => optional($clothes->material)->name,
        'Состав' => $clothes->composition,
        'Длина' => $clothes->length,
        'Сезон' => implode(', ', WardrobeItem::labelsFor($clothes->seasons, \App\Models\WardrobeOutfit::SEASONS)),
        'Температура' => $clothes->temperatureLabel(),
        'Повод' => implode(', ', WardrobeItem::labelsFor($clothes->occasions, WardrobeItem::OCCASIONS)),
        'Стиль' => implode(', ', WardrobeItem::labelsFor($clothes->styles, WardrobeItem::STYLES)),
        'Кому подходит' => implode(', ', WardrobeItem::labelsFor($clothes->body_types, WardrobeItem::BODY_TYPES)),
    ]);
@endphp
<div class="b-post-show__section b-post-film-card">
    <div class="b-post-show__section-head">
        <i class="fas fa-tshirt"></i>
        <span class="b-post-show__section-label">{{ $clothes->kind ?: 'Одежда' }}</span>
    </div>

    @if ($clothes->brand || $collection)
        <div class="b-post-film-card__badges">
            @if ($clothes->brand)
                <span class="b-post-film-card__badge">@include('user.wardrobe.parts.brand_label', ['brand' => $clothes->brand])</span>
            @endif
            @if ($collection)
                <span class="b-post-film-card__badge">{{ $collection }}</span>
            @endif
        </div>
    @endif

    @if ($facts)
        <dl class="b-post-film-card__facts">
            @foreach ($facts as $label => $value)
                <dt>{{ $label }}</dt>
                {{-- "Цвет" — готовая разметка с кружком цвета (parts/color_label), остальное — текст --}}
                <dd>@if ($label === 'Цвет'){!! $value !!}@else{{ $value }}@endif</dd>
            @endforeach
        </dl>
    @endif

    @if ($clothes->shop_url)
        <a href="{{ $clothes->shop_url }}" class="b-post-show__btn b-post-clothes-card__shop" target="_blank" rel="noopener noreferrer">
            <i class="fas fa-shopping-bag"></i> В магазин
        </a>
    @endif
</div>
