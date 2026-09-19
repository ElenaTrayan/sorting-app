@extends('layouts.admin_layout')

@section('title', 'Заказ №' . ($order->order_number ?: $order->id))

@push('head-styles')
    @include('admin.stock.parts.head_assets')
@endpush

@push('navbar-breadcrumb')
    <li class="nav-item d-flex align-items-center">
        <ol class="breadcrumb b-navbar-breadcrumb">
            <li class="breadcrumb-item"><a href="{{ route('homeAdmin') }}">Главная</a></li>
            <li class="breadcrumb-item"><a href="{{ route('stock.overview') }}">Мои запасы</a></li>
            <li class="breadcrumb-item"><a href="{{ route('stock.orders.index') }}">Заказы</a></li>
            <li class="breadcrumb-item active">№{{ $order->order_number ?: $order->id }}</li>
        </ol>
    </li>
@endpush

@section('content')
<section class="content b-stock">
    <div class="container-fluid">

        <div class="b-stock-page-header">
            <div>
                <h1 class="b-stock-page-header__title">{{ optional($order->store)->name ?? 'Заказ' }} · №{{ $order->order_number ?: $order->id }}</h1>
                <p class="b-stock-page-header__subtitle">
                    <span class="b-stock-status-pill b-stock-status-pill--{{ $order->delivery_status }}">{{ $order->delivery_status_label }}</span>
                    @if ($order->return_status)<span class="b-stock-status-pill b-stock-status-pill--returning">{{ $order->return_status_label }}</span>@endif
                </p>
            </div>
            <div class="b-stock-page-header__actions">
                <button type="button" class="b-stock-btn b-stock-btn--outline" data-toggle="modal" data-target="#modal-edit-order"><i class="fas fa-pen"></i> Редактировать</button>
                <button type="button" class="b-stock-btn b-stock-btn--outline" data-stock-delete-url="{{ route('stock.orders.destroy', $order) }}" data-stock-delete-redirect="{{ route('stock.orders.index') }}" data-stock-delete-title="Удалить заказ №{{ $order->order_number ?: $order->id }}?"><i class="fas fa-trash"></i></button>
            </div>
        </div>

        <div class="b-stock-row">
            <div class="b-stock-col" style="flex-grow:2;">

                <div class="b-stock-section">
                    <div class="b-stock-section__head"><h2 class="b-stock-section__title">Товары</h2></div>
                    @foreach ($order->items as $item)
                        <div class="b-stock-mini-item" style="margin-bottom:8px;">
                            <div class="b-stock-mini-item__photo"><i class="fas fa-box"></i></div>
                            <div class="b-stock-mini-item__body">
                                <div class="b-stock-mini-item__title">{{ $item->name }}</div>
                                <div class="b-stock-mini-item__sub">
                                    @if ($item->brand) {{ $item->brand }} · @endif
                                    {{ $item->quantity == floor($item->quantity) ? (int) $item->quantity : $item->quantity }} {{ $item->unit }}
                                    @if ($item->price) · {{ number_format($item->price, 0, ',', ' ') }} грн @endif
                                </div>
                            </div>
                            <div class="b-stock-mini-item__aside">
                                @if ($item->added_to_stock)
                                    <span class="b-stock-status b-stock-status--ok"><i class="fas fa-check"></i> В запасах</span>
                                @else
                                    <button type="button" class="b-stock-btn b-stock-btn--outline b-stock-btn--sm" data-stock-post-url="{{ route('stock.orders.items.add-to-stock', $item) }}" data-success-message="Добавлено в «Мои запасы»">В запасы</button>
                                @endif
                            </div>
                        </div>
                    @endforeach
                    @if ($order->items->isEmpty())
                        <p style="color:#9099b8;margin:0;">Товары не указаны</p>
                    @endif
                </div>

                <div class="b-stock-section">
                    <div class="b-stock-section__head"><h2 class="b-stock-section__title">Доставка и получение</h2></div>
                    <div class="b-stock-item-card__meta" style="font-size:14px;gap:10px 24px;">
                        <span><i class="fas fa-route"></i> {{ \App\Models\Order::FULFILLMENT_METHODS[$order->fulfillment_method] ?? '' }}</span>
                        @if ($order->carrier)<span><i class="fas fa-truck"></i> {{ $order->carrier }}</span>@endif
                        @if ($order->tracking_number)<span><i class="fas fa-barcode"></i> {{ $order->tracking_number }}</span>@endif
                        @if ($order->pickup_location)<span><i class="fas fa-map-marker-alt"></i> {{ $order->pickup_location }}</span>@endif
                        @if ($order->delivery_window)<span><i class="far fa-clock"></i> {{ $order->delivery_window }}</span>@endif
                        @if ($order->ready_until)<span><i class="fas fa-box"></i> Хранение до {{ $order->ready_until->format('d.m.Y') }}</span>@endif
                    </div>

                    <hr>
                    <form data-stock-ajax-form data-method="POST" action="{{ route('stock.orders.update-status', $order) }}" style="display:flex;gap:10px;align-items:flex-end;flex-wrap:wrap;">
                        <div class="b-stock-form-field" style="margin-bottom:0;">
                            <label>Изменить статус доставки</label>
                            <div class="b-stock-form-input">
                                <select name="delivery_status">
                                    @foreach (\App\Models\Order::DELIVERY_STATUSES as $value => $label)
                                        <option value="{{ $value }}" @selected($order->delivery_status === $value)>{{ $label }}</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>
                        <button type="submit" class="b-stock-btn b-stock-btn--primary">Обновить</button>
                    </form>
                </div>

                @if ($order->statusEvents->isNotEmpty())
                    <div class="b-stock-section">
                        <div class="b-stock-section__head"><h2 class="b-stock-section__title">История статусов</h2></div>
                        <ul class="b-stock-timeline">
                            @foreach ($order->statusEvents as $event)
                                <li>
                                    <div class="b-stock-timeline__status">{{ $event->status_label }}</div>
                                    <div class="b-stock-timeline__date">{{ $event->happened_at->format('d.m.Y H:i') }}</div>
                                    @if ($event->note)<div class="b-stock-timeline__note">{{ $event->note }}</div>@endif
                                </li>
                            @endforeach
                        </ul>
                    </div>
                @endif

            </div>

            <div class="b-stock-col" style="flex-grow:1;">

                <div class="b-stock-section" style="text-align:center;">
                    <span class="b-stock-badge b-stock-badge--{{ $order->payment_status === 'paid' ? 'paid' : 'cod' }}" style="margin-bottom:10px;">{{ $order->payment_status_label }}</span>
                    @if ($order->payment_method)<div style="color:#9099b8;font-size:13px;margin-bottom:6px;">{{ $order->payment_method }}</div>@endif
                    <div style="font-size:30px;font-weight:800;">{{ number_format($order->total_amount, 0, ',', ' ') }} грн</div>
                </div>

                <div class="b-stock-section">
                    <div class="b-stock-section__head"><h2 class="b-stock-section__title">Возврат</h2></div>
                    @if ($order->return_deadline)
                        <p style="margin-bottom:10px;">Срок возврата: <strong>{{ $order->return_deadline->format('d.m.Y') }}</strong>
                            @if (!is_null($order->days_until_return_deadline) && $order->days_until_return_deadline >= 0)
                                <br><span style="color:#e08a00;font-weight:700;">Осталось {{ $order->days_until_return_deadline }} дн.</span>
                            @endif
                        </p>
                    @else
                        <p style="color:#9099b8;">Срок возврата не указан</p>
                    @endif

                    <form data-stock-ajax-form data-method="POST" action="{{ route('stock.orders.register-return', $order) }}">
                        <div class="b-stock-form-field">
                            <label>Статус возврата</label>
                            <div class="b-stock-form-input">
                                <select name="return_status">
                                    @foreach (\App\Models\Order::RETURN_STATUSES as $value => $label)
                                        <option value="{{ $value }}" @selected($order->return_status === $value)>{{ $label }}</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>
                        <div class="b-stock-form-field">
                            <label>Срок возврата</label>
                            <div class="b-stock-form-input">
                                <input type="date" name="return_deadline" value="{{ optional($order->return_deadline)->format('Y-m-d') }}">
                            </div>
                        </div>
                        <button type="submit" class="b-stock-btn b-stock-btn--primary" style="width:100%;">Сохранить</button>
                    </form>
                </div>

                @if ($order->notes)
                    <div class="b-stock-section">
                        <div class="b-stock-section__head"><h2 class="b-stock-section__title">Заметка</h2></div>
                        <p style="margin:0;">{{ $order->notes }}</p>
                    </div>
                @endif

            </div>
        </div>

    </div>
</section>

@include('admin.stock.parts.order_form_modal', ['mode' => 'edit', 'order' => $order, 'stores' => \App\Models\Store::where('user_id', $order->user_id)->orderBy('name')->get()])
@include('admin.includes.modal_delete_item')
@endsection

@push('footer-scripts')
    @include('admin.stock.parts.footer_scripts')
@endpush
