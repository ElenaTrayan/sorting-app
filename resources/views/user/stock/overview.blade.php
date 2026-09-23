@extends('layouts.user_layout')

@section('title', 'Мои запасы — Обзор')

@push('head-styles')
    @include('user.stock.parts.head_assets')
@endpush

@push('navbar-breadcrumb')
    @include('user.stock.parts.breadcrumb', ['page' => 'Обзор'])
@endpush

@section('content')
<section class="content b-stock">
    <div class="container-fluid">

        <div class="b-stock-page-header">
            <div>
                <h1 class="b-stock-page-header__title">Мои запасы</h1>
                <p class="b-stock-page-header__subtitle">Учёт продуктов, лекарств, косметики и других необходимых вещей.</p>
            </div>
            <div class="b-stock-page-header__actions">
                <a href="{{ route('stock.inventory.index') }}" class="b-stock-btn b-stock-btn--primary"><i class="fas fa-plus"></i> Добавить товар</a>
            </div>
        </div>

        <div class="b-stock-summary">
            <a href="{{ route('stock.inventory.index', ['status' => 'expired']) }}" class="b-stock-summary__card b-stock-summary__card--danger">
                <div class="b-stock-summary__icon"><i class="fas fa-exclamation-triangle"></i></div>
                <div>
                    <div class="b-stock-summary__value">{{ $expiredCount }}</div>
                    <div class="b-stock-summary__label">Просрочено</div>
                </div>
            </a>
            <a href="{{ route('stock.inventory.index', ['status' => 'soon']) }}" class="b-stock-summary__card b-stock-summary__card--warning">
                <div class="b-stock-summary__icon"><i class="fas fa-clock"></i></div>
                <div>
                    <div class="b-stock-summary__value">{{ $soonCount }}</div>
                    <div class="b-stock-summary__label">Истекает за 7 дней</div>
                </div>
            </a>
            <a href="{{ route('stock.inventory.index', ['status' => 'month']) }}" class="b-stock-summary__card b-stock-summary__card--info">
                <div class="b-stock-summary__icon"><i class="fas fa-calendar-alt"></i></div>
                <div>
                    <div class="b-stock-summary__value">{{ $monthCount }}</div>
                    <div class="b-stock-summary__label">Истекает за месяц</div>
                </div>
            </a>
            <a href="{{ route('stock.shopping-list.index') }}" class="b-stock-summary__card b-stock-summary__card--success">
                <div class="b-stock-summary__icon"><i class="fas fa-shopping-basket"></i></div>
                <div>
                    <div class="b-stock-summary__value">{{ $shoppingListCount }}</div>
                    <div class="b-stock-summary__label">В списке покупок</div>
                </div>
            </a>
        </div>

        <div class="b-stock-row">
            <div class="b-stock-col" style="flex-grow: 2;">

                <div class="b-stock-section">
                    <div class="b-stock-section__head">
                        <h2 class="b-stock-section__title">Категории запасов</h2>
                        <a href="{{ route('stock.inventory.index') }}" class="b-stock-section__link">Все запасы <i class="fas fa-arrow-right"></i></a>
                    </div>
                    <div class="b-stock-categories">
                        @foreach ($categories as $category)
                            <a href="{{ route('stock.inventory.index', ['location' => $category->slug]) }}" class="b-stock-category-card">
                                <div class="b-stock-category-card__icon"><i class="fas fa-{{ $category->icon ?: 'box' }}"></i></div>
                                <div class="b-stock-category-card__name">{{ $category->name }}</div>
                                <div class="b-stock-category-card__count">{{ $category->items_count }} {{ $category->items_count == 1 ? 'товар' : 'товаров' }}</div>
                            </a>
                        @endforeach
                    </div>
                </div>

                <div class="b-stock-section">
                    <div class="b-stock-section__head">
                        <h2 class="b-stock-section__title">Скоро закончится срок</h2>
                        <a href="{{ route('stock.inventory.index', ['status' => 'soon']) }}" class="b-stock-section__link">Все <i class="fas fa-arrow-right"></i></a>
                    </div>
                    @if ($expiringItems->isEmpty())
                        <div class="b-stock-empty" style="padding: 24px;">
                            <i class="fas fa-check-circle b-stock-empty__icon"></i>
                            <p>Ничего не истекает в ближайший месяц</p>
                        </div>
                    @else
                        <div class="b-stock-mini-list">
                            @foreach ($expiringItems as $item)
                                <div class="b-stock-mini-item">
                                    <div class="b-stock-mini-item__photo" @if ($item->image) style="background-image:url({{ $item->image }})" @endif>
                                        @if (!$item->image)<i class="fas fa-box"></i>@endif
                                    </div>
                                    <div class="b-stock-mini-item__body">
                                        <div class="b-stock-mini-item__title">{{ $item->name }}</div>
                                        <div class="b-stock-mini-item__sub">
                                            @if ($item->brand) {{ $item->brand }} · @endif
                                            {{ $item->quantity == floor($item->quantity) ? (int) $item->quantity : $item->quantity }} {{ $item->unit }}
                                            @if ($item->location) · {{ $item->location->name }} @endif
                                        </div>
                                    </div>
                                    <div class="b-stock-mini-item__aside">
                                        @include('user.stock.parts.expiry_badge', ['status' => $item->expiry_status, 'daysLeft' => $item->days_left])
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    @endif
                </div>

                <div class="b-stock-section">
                    <div class="b-stock-section__head">
                        <h2 class="b-stock-section__title">Заказы, требующие внимания</h2>
                        <a href="{{ route('stock.orders.index') }}" class="b-stock-section__link">Все заказы <i class="fas fa-arrow-right"></i></a>
                    </div>
                    @if ($ordersNeedingAttention->isEmpty())
                        <div class="b-stock-empty" style="padding: 24px;">
                            <i class="fas fa-check-circle b-stock-empty__icon"></i>
                            <p>Все заказы в порядке</p>
                        </div>
                    @else
                        @foreach ($ordersNeedingAttention as $order)
                            <a href="{{ route('stock.orders.show', $order) }}" class="b-stock-attention-card" style="margin-bottom: 10px; text-decoration:none; color:inherit;">
                                <div class="b-stock-attention-card__icon" style="background:#fff4e5;color:#e08a00;"><i class="fas fa-truck"></i></div>
                                <div class="b-stock-attention-card__body">
                                    <div class="b-stock-attention-card__store">{{ optional($order->store)->name ?? 'Магазин не указан' }}</div>
                                    <div class="b-stock-attention-card__title">Заказ №{{ $order->order_number ?: $order->id }}</div>
                                    <div class="b-stock-attention-card__note">
                                        @if ($order->delivery_status === 'ready_for_pickup')
                                            Можно забрать @if ($order->ready_until) · хранение до {{ $order->ready_until->format('d.m.Y') }} @endif
                                        @elseif ($order->return_status === 'needs_return')
                                            Нужно оформить возврат @if ($order->return_deadline) · до {{ $order->return_deadline->format('d.m.Y') }} @endif
                                        @else
                                            Скоро истекает срок возврата ({{ $order->days_until_return_deadline }} дн.)
                                        @endif
                                    </div>
                                </div>
                            </a>
                        @endforeach
                    @endif
                </div>

            </div>

            <div class="b-stock-col" style="flex-grow: 1;">

                <div class="b-stock-section">
                    <div class="b-stock-section__head">
                        <h2 class="b-stock-section__title">Список покупок</h2>
                        <a href="{{ route('stock.shopping-list.index') }}" class="b-stock-section__link">Все <i class="fas fa-arrow-right"></i></a>
                    </div>
                    @if ($shoppingList->isEmpty())
                        <div class="b-stock-empty" style="padding: 20px;">
                            <p style="margin:0;">Список покупок пуст</p>
                        </div>
                    @else
                        <div class="b-stock-mini-list">
                            @foreach ($shoppingList as $item)
                                <div class="b-stock-mini-item">
                                    <div class="b-stock-mini-item__photo"><i class="fas fa-shopping-basket"></i></div>
                                    <div class="b-stock-mini-item__body">
                                        <div class="b-stock-mini-item__title">{{ $item->name }}</div>
                                        <div class="b-stock-mini-item__sub">{{ $item->quantity == floor($item->quantity) ? (int) $item->quantity : $item->quantity }} {{ $item->unit }}</div>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    @endif
                </div>

                <div class="b-stock-section">
                    <div class="b-stock-section__head">
                        <h2 class="b-stock-section__title">Лучшие акции</h2>
                        <a href="{{ route('stock.promotions.index') }}" class="b-stock-section__link">Все <i class="fas fa-arrow-right"></i></a>
                    </div>
                    @if ($promotions->isEmpty())
                        <div class="b-stock-empty" style="padding: 20px;">
                            <p style="margin:0;">Пока нет актуальных акций на постоянные покупки</p>
                        </div>
                    @else
                        <div class="b-stock-mini-list">
                            @foreach ($promotions as $promo)
                                <div class="b-stock-mini-item">
                                    <div class="b-stock-mini-item__photo" @if ($promo->image) style="background-image:url({{ $promo->image }})" @endif>
                                        @if (!$promo->image)<i class="fas fa-percent"></i>@endif
                                    </div>
                                    <div class="b-stock-mini-item__body">
                                        <div class="b-stock-mini-item__title">{{ $promo->title }}</div>
                                        <div class="b-stock-mini-item__sub">{{ optional($promo->store)->name }} · {{ number_format($promo->price, 0, ',', ' ') }} грн</div>
                                    </div>
                                    <div class="b-stock-mini-item__aside">
                                        <span class="b-stock-status b-stock-status--soon">-{{ $promo->discount_percent }}%</span>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    @endif
                </div>

                <div class="b-stock-section">
                    <div class="b-stock-section__head">
                        <h2 class="b-stock-section__title">Мои магазины</h2>
                        <a href="{{ route('stock.stores.index') }}" class="b-stock-section__link">Все <i class="fas fa-arrow-right"></i></a>
                    </div>
                    @if ($stores->isEmpty())
                        <div class="b-stock-empty" style="padding: 20px;">
                            <p style="margin:0;">Магазины ещё не добавлены</p>
                        </div>
                    @else
                        <div class="b-stock-mini-list">
                            @foreach ($stores as $store)
                                <div class="b-stock-mini-item">
                                    <div class="b-stock-mini-item__photo" @if ($store->logo) style="background-image:url({{ $store->logo }})" @endif>
                                        @if (!$store->logo)<i class="fas fa-store"></i>@endif
                                    </div>
                                    <div class="b-stock-mini-item__body">
                                        <div class="b-stock-mini-item__title">{{ $store->name }}</div>
                                        <div class="b-stock-mini-item__sub">{{ $store->type_label }}</div>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    @endif
                </div>

                <div class="b-stock-section">
                    <div class="b-stock-section__head">
                        <h2 class="b-stock-section__title">Резерв на случай ЧС</h2>
                        <a href="{{ route('stock.reserve.index') }}" class="b-stock-section__link">Открыть <i class="fas fa-arrow-right"></i></a>
                    </div>
                    @if (is_null($reserveReadiness))
                        <div class="b-stock-empty" style="padding: 20px;">
                            <p style="margin:0;">Резерв ещё не настроен</p>
                        </div>
                    @else
                        <div class="b-stock-readiness">
                            <div class="b-stock-readiness__ring" style="--pct: {{ $reserveReadiness }};" data-pct="{{ $reserveReadiness }}"></div>
                            <div>
                                <div style="font-weight:800;font-size:15px;">Готовность резерва</div>
                                @if ($reserveNeedsRestock > 0)
                                    <div style="color:#dc3545;font-size:13px;font-weight:700;">{{ $reserveNeedsRestock }} {{ $reserveNeedsRestock == 1 ? 'позиция требует' : 'позиций требуют' }} докупки</div>
                                @else
                                    <div style="color:#1fa855;font-size:13px;font-weight:700;">Все позиции в норме</div>
                                @endif
                            </div>
                        </div>
                    @endif
                </div>

            </div>
        </div>

    </div>
</section>
@endsection

@push('footer-scripts')
    @include('user.stock.parts.footer_scripts')
@endpush
