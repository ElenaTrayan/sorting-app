@extends('layouts.user_layout')

@section('title', 'Магазины')

@push('head-styles')
    @include('user.stock.parts.head_assets')
@endpush

@push('navbar-breadcrumb')
    @include('user.stock.parts.breadcrumb', ['page' => 'Магазины'])
@endpush

@section('content')
<section class="content b-stock">
    <div class="container-fluid">

        <div class="b-stock-page-header">
            <div>
                <h1 class="b-stock-page-header__title">Магазины</h1>
                <p class="b-stock-page-header__subtitle">Магазины, в которых вы обычно покупаете</p>
            </div>
            <div class="b-stock-page-header__actions">
                <button type="button" class="b-stock-btn b-stock-btn--primary" data-toggle="modal" data-target="#modal-create-store"><i class="fas fa-plus"></i> Добавить магазин</button>
            </div>
        </div>

        @if ($stores->isEmpty())
            <div class="b-stock-empty">
                <i class="fas fa-store b-stock-empty__icon"></i>
                <p>Магазины ещё не добавлены</p>
                <button type="button" class="b-stock-btn b-stock-btn--primary" data-toggle="modal" data-target="#modal-create-store">Добавить первый</button>
            </div>
        @else
            <div class="b-stock-grid">
                @foreach ($stores as $store)
                    <div class="b-stock-store-card">
                        <div class="b-stock-store-card__top">
                            <div class="b-stock-store-card__logo" @if ($store->logo) style="background-image:url({{ $store->logo }})" @endif>
                                @if (!$store->logo){{ mb_substr($store->name, 0, 1) }}@endif
                            </div>
                            <div style="flex:1;">
                                <div class="b-stock-store-card__name">{{ $store->name }}</div>
                                <div class="b-stock-store-card__type">{{ $store->type_label ?: 'Магазин' }}</div>
                            </div>
                            <div class="b-stock-more">
                                <button type="button" class="b-stock-more__btn"><i class="fas fa-ellipsis-v"></i></button>
                                <div class="b-stock-more__menu">
                                    <button type="button" data-toggle="modal" data-target="#modal-edit-store-{{ $store->id }}"><i class="fas fa-pen"></i> Редактировать</button>
                                    <button type="button" class="b-stock-more__danger" data-stock-delete-url="{{ route('stock.stores.destroy', $store) }}" data-stock-delete-title="Удалить «{{ $store->name }}»?"><i class="fas fa-trash"></i> Удалить</button>
                                </div>
                            </div>
                        </div>

                        <div class="b-stock-store-card__chips">
                            @if ($store->delivery)<span class="b-stock-chip"><i class="fas fa-truck"></i> Доставка</span>@endif
                            @if ($store->pickup)<span class="b-stock-chip"><i class="fas fa-store"></i> Самовывоз</span>@endif
                            @foreach ($store->categories_list as $cat)
                                <span class="b-stock-chip">{{ $cat }}</span>
                            @endforeach
                        </div>

                        <div class="b-stock-store-card__footer">
                            <a href="{{ route('stock.promotions.index', ['store_id' => $store->id]) }}" class="b-stock-btn b-stock-btn--outline b-stock-btn--sm"><i class="fas fa-percent"></i> Смотреть акции ({{ $store->promotions_count }})</a>
                            <label class="b-stock-switch" title="Мониторинг акций">
                                <input type="checkbox" data-stock-switch-url="{{ route('stock.stores.toggle-monitoring', $store) }}" @checked($store->monitor_promotions)>
                                <span class="b-stock-switch__slider"></span>
                            </label>
                        </div>
                    </div>
                    @include('user.stock.parts.store_form_modal', ['mode' => 'edit', 'store' => $store])
                @endforeach
            </div>
        @endif

    </div>
</section>

@include('user.stock.parts.store_form_modal', ['mode' => 'create'])
@include('user.includes.modal_delete_item')
@endsection

@push('footer-scripts')
    @include('user.stock.parts.footer_scripts')
@endpush
