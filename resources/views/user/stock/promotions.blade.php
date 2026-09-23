@extends('layouts.user_layout')

@section('title', 'Акции')

@push('head-styles')
    @include('user.stock.parts.head_assets')
@endpush

@push('navbar-breadcrumb')
    @include('user.stock.parts.breadcrumb', ['page' => 'Акции'])
@endpush

@section('content')
<section class="content b-stock">
    <div class="container-fluid">

        <div class="b-stock-page-header">
            <div>
                <h1 class="b-stock-page-header__title">Акции</h1>
                <p class="b-stock-page-header__subtitle">Актуальные предложения в ваших магазинах — в первую очередь на постоянные покупки</p>
            </div>
            <div class="b-stock-page-header__actions">
                <button type="button" class="b-stock-btn b-stock-btn--outline" data-toggle="modal" data-target="#modal-create-promotion"><i class="fas fa-plus"></i> Добавить вручную</button>
                <button type="button" class="b-stock-btn b-stock-btn--outline" data-stock-post-url="{{ route('stock.promotions.sync') }}" data-success-message="Акции обновлены"><i class="fas fa-sync"></i> Обновить</button>
            </div>
        </div>

        <div class="b-stock-toolbar">
            <div class="b-stock-toolbar__spacer"></div>
            <button type="button" class="b-stock-btn b-stock-btn--outline" data-filter-panel-open="promotions"><i class="fas fa-sliders-h"></i> Фильтры</button>
        </div>

        @if ($promotions->isEmpty())
            <div class="b-stock-empty">
                <i class="fas fa-percent b-stock-empty__icon"></i>
                <p>Актуальных акций пока нет</p>
                <button type="button" class="b-stock-btn b-stock-btn--primary" data-stock-post-url="{{ route('stock.promotions.sync') }}">Проверить акции</button>
            </div>
        @else
            <div class="b-stock-grid">
                @foreach ($promotions as $promo)
                    <div class="b-stock-promo-card">
                        <div class="b-stock-promo-card__image" @if ($promo->image) style="background-image:url({{ $promo->image }})" @endif>
                            @if (!$promo->image)<i class="fas fa-percent"></i>@endif
                            @if ($promo->discount_percent)<span class="b-stock-promo-card__discount">-{{ $promo->discount_percent }}%</span>@endif
                            @if ($promo->is_for_regular_purchase)<span class="b-stock-promo-card__discount" style="left:auto;right:10px;background:#4b3bf5;"><i class="fas fa-star"></i></span>@endif
                        </div>
                        <div class="b-stock-promo-card__body">
                            <div class="b-stock-promo-card__store">{{ optional($promo->store)->name }}</div>
                            <div class="b-stock-promo-card__title">{{ $promo->title }} @if ($promo->weight_volume) · {{ $promo->weight_volume }} @endif</div>
                            <div class="b-stock-promo-card__prices">
                                <span class="b-stock-promo-card__price">{{ number_format($promo->price, 0, ',', ' ') }} грн</span>
                                @if ($promo->old_price)<span class="b-stock-promo-card__old-price">{{ number_format($promo->old_price, 0, ',', ' ') }} грн</span>@endif
                            </div>
                            <div style="font-size:12px;color:#9099b8;">
                                {{ $promo->availability_label }}
                                @if ($promo->valid_until) · до {{ $promo->valid_until->format('d.m.Y') }} @endif
                            </div>
                            <div class="b-stock-promo-card__footer">
                                @if ($promo->url)<a href="{{ $promo->url }}" target="_blank" rel="noopener" class="b-stock-btn b-stock-btn--outline b-stock-btn--sm">В магазин</a>@endif
                                <button type="button" class="b-stock-btn b-stock-btn--primary b-stock-btn--sm" data-stock-post-url="{{ route('stock.promotions.add-to-shopping-list', $promo) }}" data-success-message="Добавлено в список покупок" data-no-reload>В покупки</button>
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>
        @endif

    </div>
</section>

@include('user.stock.parts.promotion_form_modal')
@include('user.stock.parts.promotion_filters')
@endsection

@push('footer-scripts')
    @include('user.stock.parts.footer_scripts')
@endpush
