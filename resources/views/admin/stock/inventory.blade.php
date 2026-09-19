@extends('layouts.admin_layout')

@section('title', 'Мои запасы')

@push('head-styles')
    @include('admin.stock.parts.head_assets')
@endpush

@push('navbar-breadcrumb')
    @include('admin.stock.parts.breadcrumb', ['page' => 'Мои запасы'])
@endpush

@section('content')
<section class="content b-stock">
    <div class="container-fluid">

        <div class="b-stock-page-header">
            <div>
                <h1 class="b-stock-page-header__title">Мои запасы</h1>
                <p class="b-stock-page-header__subtitle">{{ $items->count() }} {{ $items->count() == 1 ? 'товар' : 'товаров' }} в запасах</p>
            </div>
            <div class="b-stock-page-header__actions">
                <button type="button" class="b-stock-btn b-stock-btn--primary" data-toggle="modal" data-target="#modal-create-stock-item"><i class="fas fa-plus"></i> Добавить товар</button>
            </div>
        </div>

        <div class="b-stock-tabs">
            @php $tabs = ['all' => 'Все'] + $locations->pluck('name', 'slug')->all(); @endphp
            @foreach ($tabs as $slug => $label)
                <a href="{{ route('stock.inventory.index', array_merge(request()->except('location'), ['location' => $slug])) }}" class="b-stock-tabs__item @if ($activeLocation === $slug) is-active @endif">{{ $label }}</a>
            @endforeach
        </div>

        <div class="b-stock-toolbar">
            <form method="GET" class="b-stock-toolbar__search">
                <input type="hidden" name="location" value="{{ $activeLocation }}">
                <i class="fas fa-search"></i>
                <input type="text" name="search" value="{{ $filters['search'] ?? '' }}" placeholder="Поиск по названию или бренду">
            </form>
            <div class="b-stock-toolbar__spacer"></div>
            <button type="button" class="b-stock-btn b-stock-btn--outline" data-filter-panel-open="inventory"><i class="fas fa-sliders-h"></i> Фильтры</button>
        </div>

        @if ($items->isEmpty())
            <div class="b-stock-empty">
                <i class="fas fa-box-open b-stock-empty__icon"></i>
                <p>Здесь пока пусто</p>
                <button type="button" class="b-stock-btn b-stock-btn--primary" data-toggle="modal" data-target="#modal-create-stock-item">Добавить первый товар</button>
            </div>
        @else
            <div class="b-stock-grid">
                @foreach ($items as $item)
                    @include('admin.stock.parts.item_card', ['item' => $item])
                @endforeach
            </div>
        @endif

    </div>
</section>

@include('admin.stock.parts.item_form_modal', ['mode' => 'create', 'locations' => $locations, 'categories' => $categories])
@include('admin.stock.parts.inventory_filters')
@include('admin.includes.modal_delete_item')
@endsection

@push('footer-scripts')
    @include('admin.stock.parts.footer_scripts')
@endpush
