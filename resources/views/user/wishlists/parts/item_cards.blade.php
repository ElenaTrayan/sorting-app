@foreach ($items as $item)
    <div class="b-wishlist-item{{ $item->is_reserved ? ' b-wishlist-item--reserved' : '' }}{{ $item->is_fulfilled ? ' b-wishlist-item--fulfilled' : '' }}"
         data-id="{{ $item->id }}"
         data-category="{{ $item->category }}"
         data-priority="{{ $item->priority }}"
         data-store="{{ $item->url_domain }}"
         data-reserved="{{ $item->is_reserved ? '1' : '0' }}"
         data-fulfilled="{{ $item->is_fulfilled ? '1' : '0' }}"
         data-title="{{ $item->title }}"
         data-description="{{ $item->description }}"
         data-price="{{ $item->price !== null ? $item->price : '' }}"
         data-url="{{ $item->url }}"
         data-image-url="{{ $item->image_url }}"
         data-image-large-url="{{ $item->image_large_url ?: $item->image_url }}"
         data-created-at="{{ $item->created_at ? $item->created_at->toIso8601String() : '' }}">

        <div class="b-wishlist-item__thumb">
            @if ($item->image_url)
                {{-- обычный img, а не background-image — высота плитки подстраивается под
                     реальные пропорции фото товара (см. b-wishlist-items — раскладка Masonry) --}}
                <img src="{{ $item->image_url }}" alt="{{ $item->title }}" loading="lazy">
            @else
                <div class="b-wishlist-item__thumb-placeholder"><i class="fas fa-gift"></i></div>
            @endif

            <div class="b-wishlist-item__hover-top">
                @if ($isOwner)
                    <button type="button" class="b-wishlist-item__hover-btn js-wishlist-item-more" title="Ещё">
                        <i class="fas fa-ellipsis-h"></i>
                    </button>
                @endif
                <button type="button" class="b-wishlist-item__hover-btn js-wishlist-item-share" title="Поделиться">
                    <i class="fas fa-share-alt"></i>
                </button>
                <button type="button" class="b-wishlist-item__hover-btn b-wishlist-item__hover-btn--accent js-wishlist-item-detail" title="Подробнее">
                    <i class="fas fa-shopping-bag"></i>
                </button>
            </div>

            {{-- "исполнено" (ставит владелец) и "забронировано" (ставит гость) — разные статусы,
                 но одновременно показывать оба бейджа тесно, поэтому если желание уже исполнено,
                 это более сильный статус и показываем только его --}}
            @if ($item->is_fulfilled)
                <div class="b-wishlist-item__reserved-badge b-wishlist-item__reserved-badge--fulfilled">
                    <i class="fas fa-check-circle mr-1"></i>Выполнено
                </div>
            @elseif ($item->is_reserved)
                <div class="b-wishlist-item__reserved-badge">
                    <i class="fas fa-check-circle mr-1"></i>
                    {{ $item->reserved_by ? 'Забронировал(а) ' . $item->reserved_by : 'Забронировано' }}
                </div>
            @endif

            <div class="b-wishlist-item__hover-bottom">
                @if ($isOwner)
                    <button type="button" class="b-wishlist-item__fulfill-pill js-wishlist-item-fulfill">
                        {{ $item->is_fulfilled ? 'Отменить' : 'Выполнить' }}
                    </button>
                @else
                    <button type="button" class="b-wishlist-item__fulfill-pill js-wishlist-item-reserve">
                        {{ $item->is_reserved ? 'Отменить бронь' : 'Забронировать' }}
                    </button>
                @endif
            </div>
        </div>

        @if ($isOwner)
            {{-- вынесено за пределы .b-wishlist-item__thumb (у него overflow:hidden под скруглённые
                 углы картинки) — иначе выпадающее меню обрезалось бы по границе картинки --}}
            <div class="b-wishlist-item__more-menu js-wishlist-item-more-menu">
                <button type="button" class="b-wishlist-item__more-menu-item js-wishlist-item-edit-trigger">
                    <i class="fas fa-pencil-alt"></i> Редактировать
                </button>
                <button type="button" class="b-wishlist-item__more-menu-item js-wishlist-item-duplicate">
                    <i class="fas fa-copy"></i> Создать копию
                </button>
                <button type="button" class="b-wishlist-item__more-menu-item js-wishlist-item-share-menu">
                    <i class="fas fa-share-alt"></i> Поделиться
                </button>
                <div class="b-wishlist-item__more-menu-divider"></div>
                <button type="button" class="b-wishlist-item__more-menu-item b-wishlist-item__more-menu-item--danger js-wishlist-item-delete">
                    <i class="fas fa-trash"></i> Удалить
                </button>
            </div>
        @endif

        <div class="b-wishlist-item__body">
            <div class="b-wishlist-item__title">{{ $item->title }}</div>
            <div class="b-wishlist-item__meta">
                @if ($item->url_domain)
                    <span class="b-wishlist-item__domain">{{ $item->url_domain }}</span>
                @endif
                @if ($item->price !== null)
                    <span class="b-wishlist-item__price">{{ number_format((float) $item->price, 0, '.', ' ') }} грн</span>
                @endif
            </div>
        </div>
    </div>
@endforeach
