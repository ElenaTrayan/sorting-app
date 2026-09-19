@extends('layouts.admin_layout')

@section('title', 'Заказы')

@push('head-styles')
    @include('admin.stock.parts.head_assets')
@endpush

@push('navbar-breadcrumb')
    @include('admin.stock.parts.breadcrumb', ['page' => 'Заказы'])
@endpush

@section('content')
<section class="content b-stock">
    <div class="container-fluid">

        <div class="b-stock-page-header">
            <div>
                <h1 class="b-stock-page-header__title">Заказы</h1>
                <p class="b-stock-page-header__subtitle">Где что заказано, когда забрать, оплачено ли и нужен ли возврат</p>
            </div>
            <div class="b-stock-page-header__actions">
                <button type="button" class="b-stock-btn b-stock-btn--primary" data-toggle="modal" data-target="#modal-create-order"><i class="fas fa-plus"></i> Новый заказ</button>
            </div>
        </div>

        <div class="b-stock-summary">
            <a href="{{ route('stock.orders.index', ['status' => 'shipped']) }}" class="b-stock-summary__card b-stock-summary__card--info">
                <div class="b-stock-summary__icon"><i class="fas fa-truck"></i></div>
                <div><div class="b-stock-summary__value">{{ $summary['shipped'] }}</div><div class="b-stock-summary__label">В пути</div></div>
            </a>
            <a href="{{ route('stock.orders.index', ['status' => 'ready_for_pickup']) }}" class="b-stock-summary__card b-stock-summary__card--warning">
                <div class="b-stock-summary__icon"><i class="fas fa-box"></i></div>
                <div><div class="b-stock-summary__value">{{ $summary['ready_for_pickup'] }}</div><div class="b-stock-summary__label">Можно забрать</div></div>
            </a>
            <a href="{{ route('stock.orders.index', ['status' => 'delivered']) }}" class="b-stock-summary__card b-stock-summary__card--success">
                <div class="b-stock-summary__icon"><i class="fas fa-check-circle"></i></div>
                <div><div class="b-stock-summary__value">{{ $summary['delivered'] }}</div><div class="b-stock-summary__label">Получены</div></div>
            </a>
            <div class="b-stock-summary__card b-stock-summary__card--danger">
                <div class="b-stock-summary__icon"><i class="fas fa-undo"></i></div>
                <div><div class="b-stock-summary__value">{{ $summary['needs_return'] }}</div><div class="b-stock-summary__label">Нужен возврат</div></div>
            </div>
            <div class="b-stock-summary__card b-stock-summary__card--danger">
                <div class="b-stock-summary__icon"><i class="fas fa-hourglass-half"></i></div>
                <div><div class="b-stock-summary__value">{{ $summary['return_soon'] }}</div><div class="b-stock-summary__label">Истекает срок возврата</div></div>
            </div>
        </div>

        @if ($attention->isNotEmpty())
            <div class="b-stock-section">
                <div class="b-stock-section__head"><h2 class="b-stock-section__title">Требует внимания</h2></div>
                @foreach ($attention as $order)
                    <a href="{{ route('stock.orders.show', $order) }}" class="b-stock-attention-card" style="margin-bottom:10px;text-decoration:none;color:inherit;">
                        <div class="b-stock-attention-card__icon" style="background:#fff4e5;color:#e08a00;"><i class="fas fa-exclamation"></i></div>
                        <div class="b-stock-attention-card__body">
                            <div class="b-stock-attention-card__store">{{ optional($order->store)->name ?? 'Магазин не указан' }}</div>
                            <div class="b-stock-attention-card__title">Заказ №{{ $order->order_number ?: $order->id }}</div>
                            <div class="b-stock-attention-card__note">
                                @if ($order->delivery_status === 'ready_for_pickup')
                                    <span class="b-stock-status-pill b-stock-status-pill--ready_for_pickup">Можно забрать</span>
                                    @if ($order->ready_until) · хранение до {{ $order->ready_until->format('d.m.Y') }} @endif
                                @elseif ($order->return_status === 'needs_return')
                                    <span class="b-stock-status-pill b-stock-status-pill--returning">Нужно оформить возврат</span>
                                    @if ($order->return_deadline) · до {{ $order->return_deadline->format('d.m.Y') }} @endif
                                @else
                                    <span class="b-stock-status-pill b-stock-status-pill--returning">Возврат через {{ $order->days_until_return_deadline }} дн.</span>
                                @endif
                            </div>
                        </div>
                    </a>
                @endforeach
            </div>
        @endif

        <div class="b-stock-toolbar">
            <div class="b-stock-toolbar__spacer"></div>
            <button type="button" class="b-stock-btn b-stock-btn--outline" data-filter-panel-open="orders"><i class="fas fa-sliders-h"></i> Фильтры</button>
        </div>

        @if ($orders->isEmpty())
            <div class="b-stock-empty">
                <i class="fas fa-truck b-stock-empty__icon"></i>
                <p>Заказов пока нет</p>
                <button type="button" class="b-stock-btn b-stock-btn--primary" data-toggle="modal" data-target="#modal-create-order">Создать первый заказ</button>
            </div>
        @else
            @foreach ($orders as $order)
                <a href="{{ route('stock.orders.show', $order) }}" class="b-stock-order-row">
                    <div>
                        <div class="b-stock-order-row__label">Магазин</div>
                        <div class="b-stock-order-row__store">{{ optional($order->store)->name ?? '—' }}</div>
                        <div class="b-stock-order-row__number">№{{ $order->order_number ?: $order->id }} @if ($order->ordered_at) · {{ $order->ordered_at->format('d.m.Y') }} @endif</div>
                    </div>
                    <div>
                        <div class="b-stock-order-row__label">Статус</div>
                        <span class="b-stock-status-pill b-stock-status-pill--{{ $order->delivery_status }}">{{ $order->delivery_status_label }}</span>
                    </div>
                    <div>
                        <div class="b-stock-order-row__label">Оплата</div>
                        <span class="b-stock-badge b-stock-badge--{{ $order->payment_status === 'paid' ? 'paid' : 'cod' }}">{{ $order->payment_status_label }}</span>
                    </div>
                    <div>
                        <div class="b-stock-order-row__label">Получение</div>
                        <div style="font-size:13.5px;font-weight:700;">{{ \App\Models\Order::FULFILLMENT_METHODS[$order->fulfillment_method] ?? '' }}</div>
                    </div>
                    <div>
                        <div class="b-stock-order-row__label">Возврат до</div>
                        <div style="font-size:13.5px;font-weight:700;">{{ $order->return_deadline ? $order->return_deadline->format('d.m.Y') : '—' }}</div>
                    </div>
                    <div class="b-stock-order-row__amount">{{ number_format($order->total_amount, 0, ',', ' ') }} грн</div>
                </a>
            @endforeach
        @endif

    </div>
</section>

@include('admin.stock.parts.order_form_modal', ['mode' => 'create', 'stores' => $stores])
@include('admin.stock.parts.order_filters')
@include('admin.includes.modal_delete_item')
@endsection

@push('footer-scripts')
    @include('admin.stock.parts.footer_scripts')
@endpush
