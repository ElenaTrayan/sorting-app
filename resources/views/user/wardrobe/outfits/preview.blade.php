{{-- Превью образа для галереи "Образы" — как превью вещи (parts/item_preview.blade.php) и пина в "Все посты",
     на тех же стилях (.b-post-preview, .b-post-show__*, .b-wardrobe-preview*). Открывается в Fancybox кликом
     по обложке карточки (data-fancybox="wardrobe-outfit-gallery", wardrobe.js).

     Слева — фото: сначала фото самого образа (если загружено), потом обложки его вещей; миниатюры
     переключают крупное (тот же обработчик, что у вещи), клик по крупному — полный размер с зумом и листанием
     (группа wardrobe-outfit-photo-{id} — своя у каждого образа). Справа — данные образа и из чего он составлен.

     @param \App\Models\WardrobeOutfit $outfit --}}
@php
    use App\Models\WardrobeItem;
    use App\Models\WardrobeOutfit;

    $photos = collect();
    if ($outfit->image_url) {
        $photos->push(['url' => $outfit->image_url, 'title' => $outfit->name]);
    }
    foreach ($outfit->items as $item) {
        if ($item->image_url) {
            $photos->push(['url' => $item->image_url, 'title' => $item->name]);
        }
    }

    $toBuy = $outfit->items->where('status', WardrobeItem::STATUS_WISHLIST)->count();
    $seasonLabels = WardrobeItem::labelsFor($outfit->seasons, WardrobeOutfit::SEASONS);
    $bodyTypeLabels = WardrobeItem::labelsFor($outfit->body_types, WardrobeItem::BODY_TYPES);
    $lastWear = $outfit->wears->first();

    $facts = array_filter([
        'Сезон' => implode(', ', $seasonLabels),
        'Температура' => $outfit->temperatureLabel(),
        'Кому подходит' => implode(', ', $bodyTypeLabels),
        'Надевала' => $outfit->wears->isEmpty() ? null
            : $outfit->wears->count() . ' раз(а), последний — ' . $lastWear->place
                . ($lastWear->worn_at ? ', ' . $lastWear->worn_at->format('d.m.Y') : ''),
    ]);
@endphp
<div class="b-post-preview b-wardrobe-preview @if ($photos->isEmpty()) b-post-preview--text-only @endif">
    @if ($photos->isNotEmpty())
        <div class="b-post-preview__media b-wardrobe-preview__media">
            <div class="b-wardrobe-preview__stage">
                @foreach ($photos as $index => $photo)
                    <a href="{{ $photo['url'] }}" data-fancybox="wardrobe-outfit-photo-{{ $outfit->id }}" data-caption="{{ $photo['title'] }}"
                       class="b-wardrobe-preview__photo js-wardrobe-preview-photo" data-index="{{ $index }}" @if ($index > 0) hidden @endif>
                        <img src="{{ $photo['url'] }}" alt="{{ $photo['title'] }}">
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
                                data-index="{{ $index }}" style="background-image:url('{{ $photo['url'] }}')"
                                title="{{ $photo['title'] }}"></button>
                    @endforeach
                </div>
            @endif
        </div>
    @endif

    <div class="b-post-preview__body">
        <div class="b-post-show__meta">
            @if ($toBuy)
                <span class="b-wardrobe-badge b-wardrobe-badge--wishlist"><i class="fas fa-shopping-bag"></i> Докупить: {{ $toBuy }}</span>
            @else
                <span class="b-wardrobe-badge"><i class="fas fa-check"></i> Всё есть в гардеробе</span>
            @endif
        </div>

        <div class="b-post-show__text">
            <h2 class="b-post-show__title">{{ $outfit->name }}</h2>
            <div class="b-wardrobe-preview__sub">Образ из {{ $outfit->items->count() }} {{ trans_choice('вещи|вещей|вещей', $outfit->items->count()) }}</div>
        </div>

        @if ($facts)
            <dl class="b-post-film-card__facts">
                @foreach ($facts as $label => $value)
                    <dt>{{ $label }}</dt>
                    <dd>{{ $value }}</dd>
                @endforeach
            </dl>
        @endif

        @if ($outfit->items->isNotEmpty())
            <div class="b-wardrobe-preview__items">
                @foreach ($outfit->items as $item)
                    <div class="b-wardrobe-preview__item">
                        <span class="b-wardrobe-preview__item-photo" @if ($item->image_url) style="background-image:url('{{ $item->image_url }}')" @endif>
                            @if (!$item->image_url)<i class="fas fa-tshirt"></i>@endif
                        </span>
                        <span class="b-wardrobe-preview__item-body">
                            <span class="b-wardrobe-preview__item-name">{{ $item->name }}</span>
                            <span class="b-wardrobe-preview__item-sub">{{ $item->categoryLabel() }}@if ($item->brand_name) · @include('user.wardrobe.parts.brand_label', ['brand' => $item->brand])@endif</span>
                        </span>
                        @if ($item->isWishlist())
                            @if ($item->shop_url)
                                <a href="{{ $item->shop_url }}" target="_blank" rel="noopener noreferrer" class="b-wardrobe-badge b-wardrobe-badge--wishlist" title="Открыть магазин">Купить <i class="fas fa-external-link-alt"></i></a>
                            @else
                                <span class="b-wardrobe-badge b-wardrobe-badge--wishlist">Купить</span>
                            @endif
                        @endif
                    </div>
                @endforeach
            </div>
        @endif

        @if ($outfit->note)
            <p class="b-wardrobe-preview__note">{{ $outfit->note }}</p>
        @endif

        <div class="b-post-preview__actions">
            <a href="{{ route('wardrobe.outfits.edit', $outfit) }}" class="b-post-show__btn">
                <i class="fas fa-pencil-alt"></i> Редактировать
            </a>
            <a href="{{ route('wardrobe.outfits.show', $outfit) }}" class="b-posts-toolbar__create">
                Открыть образ <i class="fas fa-arrow-right"></i>
            </a>
        </div>
    </div>
</div>
