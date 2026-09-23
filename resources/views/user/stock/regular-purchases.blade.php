@extends('layouts.user_layout')

@section('title', 'Постоянные покупки')

@push('head-styles')
    @include('user.stock.parts.head_assets')
@endpush

@push('navbar-breadcrumb')
    @include('user.stock.parts.breadcrumb', ['page' => 'Постоянные покупки'])
@endpush

@section('content')
<section class="content b-stock">
    <div class="container-fluid">

        <div class="b-stock-page-header">
            <div>
                <h1 class="b-stock-page-header__title">Постоянные покупки</h1>
                <p class="b-stock-page-header__subtitle">Товары, которые вы покупаете регулярно — с брендами, магазинами и ценами</p>
            </div>
            <div class="b-stock-page-header__actions">
                <button type="button" class="b-stock-btn b-stock-btn--primary" data-toggle="modal" data-target="#modal-create-regular"><i class="fas fa-plus"></i> Добавить</button>
            </div>
        </div>

        <div class="b-stock-tabs">
            @foreach (['' => 'Все'] + \App\Models\RegularPurchase::PREFERENCES as $value => $label)
                <a href="{{ route('stock.regular-purchases.index', $value ? ['preference' => $value] : []) }}" class="b-stock-tabs__item @if (($filters['preference'] ?? '') === $value) is-active @endif">{{ $label }}</a>
            @endforeach
        </div>

        @if ($regularPurchases->isEmpty())
            <div class="b-stock-empty">
                <i class="fas fa-repeat b-stock-empty__icon"></i>
                <p>Постоянные покупки ещё не добавлены</p>
                <button type="button" class="b-stock-btn b-stock-btn--primary" data-toggle="modal" data-target="#modal-create-regular">Добавить первую</button>
            </div>
        @else
            <div class="b-stock-grid">
                @foreach ($regularPurchases as $rp)
                    @continue(!$rp->product)
                    <div class="b-stock-item-card">
                        <div class="b-stock-item-card__top">
                            <div class="b-stock-item-card__photo"><i class="fas fa-box"></i></div>
                            <div style="flex:1;min-width:0;">
                                <div class="b-stock-item-card__name">{{ $rp->product->name }}</div>
                                <div class="b-stock-item-card__brand">
                                    {{ $rp->product->brand }}
                                    @if ($rp->product->variant) · {{ $rp->product->variant }} @endif
                                    @if ($rp->product->size_label) · {{ $rp->product->size_label }} @endif
                                </div>
                            </div>
                            <div class="b-stock-more">
                                <button type="button" class="b-stock-more__btn"><i class="fas fa-ellipsis-v"></i></button>
                                <div class="b-stock-more__menu">
                                    <button type="button" data-toggle="modal" data-target="#modal-edit-regular-{{ $rp->id }}"><i class="fas fa-pen"></i> Редактировать</button>
                                    <button type="button" class="b-stock-more__danger" data-stock-delete-url="{{ route('stock.regular-purchases.destroy', $rp) }}" data-stock-delete-title="Удалить «{{ $rp->product->name }}» из постоянных покупок?"><i class="fas fa-trash"></i> Удалить</button>
                                </div>
                            </div>
                        </div>

                        <div class="b-stock-item-card__meta">
                            @if ($rp->preferredStore)<span><i class="fas fa-store"></i> {{ $rp->preferredStore->name }}</span>@endif
                            @if ($rp->avg_price)<span><i class="fas fa-hryvnia-sign"></i> ср. {{ number_format($rp->avg_price, 0, ',', ' ') }} грн</span>@endif
                            @if ($rp->last_purchased_at)<span><i class="far fa-calendar-alt"></i> {{ $rp->last_purchased_at->format('d.m.Y') }}</span>@endif
                        </div>

                        <div class="b-stock-item-card__footer">
                            <span class="b-stock-badge b-stock-badge--{{ $rp->preference }}">{{ $rp->preference_label }}</span>
                            <button type="button" class="b-stock-btn b-stock-btn--outline b-stock-btn--sm js-search-offer-btn"
                                data-search-offers-url="{{ route('stock.regular-purchases.search-offers', $rp) }}"
                                data-product-name="{{ $rp->product->display_name }}">
                                <i class="fas fa-search-dollar"></i> Искать лучшее предложение
                            </button>
                        </div>
                    </div>
                    @include('user.stock.parts.regular_purchase_form_modal', ['mode' => 'edit', 'regularPurchase' => $rp, 'stores' => $stores, 'categories' => $categories])
                @endforeach
            </div>
        @endif

    </div>
</section>

@include('user.stock.parts.regular_purchase_form_modal', ['mode' => 'create', 'stores' => $stores, 'categories' => $categories])
@include('user.stock.parts.best_offer_modal')
@include('user.includes.modal_delete_item')
@endsection

@push('footer-scripts')
    @include('user.stock.parts.footer_scripts')
@endpush
