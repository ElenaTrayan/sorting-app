{{-- Карточка товара из post_products (пин "Товар") — на странице поста, под текстом. Основа — стили карточки
     фильма (.b-post-film-card__* в admin-panel.css), своё — .b-post-product-card__*.
     Кнопки "В вишлист", "В список покупок", "На склад" — post-product.js → PostProductController.

     @param \App\Models\PostProduct $product
     @param \App\Models\Post $post --}}
@php
    use App\Models\PostProduct;

    /** @var PostProduct $product */
    $user = auth()->user();
    $warrantyUntil = $product->warrantyUntil();
    $expiryState = $product->expiryState();

    $facts = array_filter([
        'Категория' => $product->category,
        'Производитель' => $product->manufacturer,
        'Страна производства' => $product->country,
        'Страна бренда' => $product->brand_country,
        'Вариант' => $product->variant,
        'Вес' => $product->weight,
        'Артикул' => $product->article,
        'Магазин' => $product->store,
        'Дата покупки' => $product->purchased_at ? $product->purchased_at->format('d.m.Y') : null,
        'Промокод / скидка' => $product->promo,
        'Гарантия' => $product->warrantyLabel()
            ? $product->warrantyLabel() . ($warrantyUntil ? ' — до ' . $warrantyUntil->format('d.m.Y') . ($warrantyUntil->isPast() ? ' (закончилась)' : '') : '')
            : null,
        'Срок хранения' => $product->shelf_life,
        'Условия хранения' => $product->storage_conditions,
    ]);

    $stars = function (?float $rating) {
        $full = (int)floor((float)$rating);
        $half = $rating !== null && $rating - $full >= 0.5;
        return str_repeat('<i class="fas fa-star"></i>', $full)
            . ($half ? '<i class="fas fa-star-half-alt"></i>' : '')
            . str_repeat('<i class="far fa-star"></i>', max(0, 5 - $full - ($half ? 1 : 0)));
    };
    $hasMyOpinion = $product->my_rating || $product->my_opinion || $product->my_pros || $product->my_cons;
    $hasReviews = $product->reviews_rating !== null || $product->reviews_count || $product->reviews_pros || $product->reviews_cons || $product->reviews_url;
@endphp
<div class="b-post-show__section b-post-film-card b-post-product-card js-post-product-card"
     data-post-id="{{ $post->id }}">
    <div class="b-post-show__section-head">
        <i class="fas fa-box-open"></i>
        <span class="b-post-show__section-label">{{ $product->category ?: 'Товар' }}</span>
    </div>

    @if ($product->brand || $product->statusLabel() || $product->priceLabel())
        <div class="b-post-film-card__badges">
            @if ($product->priceLabel())
                <span class="b-post-film-card__badge b-post-product-card__price">{{ $product->priceLabel() }}</span>
            @endif
            @if ($product->brand)
                <span class="b-post-film-card__badge">{{ $product->brand }}</span>
            @endif
            @if ($product->statusLabel())
                <span class="b-post-film-card__badge b-post-product-card__status b-post-product-card__status--{{ $product->status }}">
                    <i class="fas {{ PostProduct::STATUS_ICONS[$product->status] ?? 'fa-tag' }}"></i> {{ $product->statusLabel() }}
                </span>
            @endif
        </div>
    @endif

    @if ($product->expires_at)
        <div class="b-post-product-card__expiry b-post-product-card__expiry--{{ $expiryState }}">
            <i class="fas fa-hourglass-half"></i>
            Годен до {{ $product->expires_at->format('d.m.Y') }}
            @if ($expiryState === 'expired') — срок вышел @elseif ($expiryState === 'soon') — скоро истекает @endif
        </div>
    @endif

    @if ($facts)
        <dl class="b-post-film-card__facts">
            @foreach ($facts as $label => $value)
                <dt>{{ $label }}</dt>
                {{-- условия хранения многострочные — переносы строк сохраняем --}}
                <dd>{!! nl2br(e($value)) !!}</dd>
            @endforeach
        </dl>
    @endif

    @if ($hasMyOpinion)
        <div class="b-post-product-card__block">
            <div class="b-post-product-card__block-title">
                Моё мнение
                @if ($product->my_rating)
                    <span class="b-post-product-card__stars" title="{{ $product->my_rating }} из 5">{!! $stars((float)$product->my_rating) !!}</span>
                @endif
            </div>
            @if ($product->my_opinion)
                <div class="b-post-product-card__text">{!! nl2br(e($product->my_opinion)) !!}</div>
            @endif
            @if ($product->my_pros || $product->my_cons)
                <div class="b-post-product-card__pros-cons">
                    @if ($product->my_pros)
                        <div class="b-post-product-card__pros"><strong>Понравилось</strong>{!! nl2br(e($product->my_pros)) !!}</div>
                    @endif
                    @if ($product->my_cons)
                        <div class="b-post-product-card__cons"><strong>Не понравилось</strong>{!! nl2br(e($product->my_cons)) !!}</div>
                    @endif
                </div>
            @endif
        </div>
    @endif

    @if ($hasReviews)
        <div class="b-post-product-card__block">
            <div class="b-post-product-card__block-title">
                Отзывы людей
                @if ($product->reviews_rating !== null)
                    <span class="b-post-product-card__stars" title="{{ $product->reviews_rating }} из 5">{!! $stars($product->reviews_rating) !!}</span>
                    <span class="b-post-product-card__rating">{{ rtrim(rtrim(number_format($product->reviews_rating, 1, ',', ''), '0'), ',') }}</span>
                @endif
                @if ($product->reviews_count)
                    <span class="b-post-product-card__muted">{{ number_format($product->reviews_count, 0, ',', ' ') }} {{ PostProduct::plural($product->reviews_count, 'отзыв', 'отзыва', 'отзывов') }}</span>
                @endif
            </div>
            @if ($product->reviews_pros || $product->reviews_cons)
                <div class="b-post-product-card__pros-cons">
                    @if ($product->reviews_pros)
                        <div class="b-post-product-card__pros"><strong>Хвалят</strong>{!! nl2br(e($product->reviews_pros)) !!}</div>
                    @endif
                    @if ($product->reviews_cons)
                        <div class="b-post-product-card__cons"><strong>Жалуются</strong>{!! nl2br(e($product->reviews_cons)) !!}</div>
                    @endif
                </div>
            @endif
            @if ($product->reviews_url)
                <a href="{{ $product->reviews_url }}" class="b-post-product-card__link" target="_blank" rel="noopener noreferrer">
                    Читать отзывы <i class="fas fa-external-link-alt"></i>
                </a>
            @endif
        </div>
    @endif

    <div class="b-post-product-card__actions">
        @if ($product->shop_url)
            <a href="{{ $product->shop_url }}" class="b-post-show__btn b-post-clothes-card__shop" target="_blank" rel="noopener noreferrer">
                <i class="fas fa-shopping-bag"></i> В магазин
            </a>
        @endif

        {{-- куда уже отправлен: кнопка становится ссылкой "уже там" (id записи в post_products) --}}
        @if ($user->hasService('wishlists'))
            @if ($product->wishlistItem)
                <a href="{{ route('wishlists.show', $product->wishlistItem->wishlist_id) }}" class="b-post-show__btn b-post-show__btn--done">
                    <i class="fas fa-check"></i> В вишлисте
                </a>
            @else
                <button type="button" class="b-post-show__btn js-product-to-wishlist" data-action="{{ route('posts.product.to-wishlist', $post->id) }}">
                    <i class="fas fa-gift"></i> В вишлист
                </button>
            @endif
        @endif

        @if ($user->hasService('stock'))
            @if ($product->shoppingListItem)
                <a href="{{ route('stock.shopping-list.index') }}" class="b-post-show__btn b-post-show__btn--done">
                    <i class="fas fa-check"></i> В списке покупок
                </a>
            @else
                <button type="button" class="b-post-show__btn js-product-to-shopping-list" data-action="{{ route('posts.product.to-shopping-list', $post->id) }}">
                    <i class="fas fa-cart-plus"></i> В список покупок
                </button>
            @endif

            @if ($product->stockItem)
                <a href="{{ route('stock.inventory.index') }}" class="b-post-show__btn b-post-show__btn--done">
                    <i class="fas fa-check"></i> На складе
                </a>
            @else
                <button type="button" class="b-post-show__btn js-product-to-stock" data-action="{{ route('posts.product.to-stock', $post->id) }}">
                    <i class="fas fa-box"></i> На склад
                </button>
            @endif
        @endif
    </div>
</div>
