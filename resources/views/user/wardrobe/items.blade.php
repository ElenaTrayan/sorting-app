@extends('layouts.user_layout')

@section('title', 'Мой гардероб')

@push('head-styles')
    @include('user.wardrobe.parts.head_assets')
@endpush

@push('navbar-breadcrumb')
    @include('user.wardrobe.parts.breadcrumb', ['page' => 'Одежда'])
@endpush

@section('content')
@php
    $total = $statusCounts->sum();
    $activeFilters = count(array_filter(\Illuminate\Support\Arr::except($filters, ['search'])));
@endphp
<section class="content b-stock b-wardrobe">
    <div class="container-fluid">

        <div class="b-stock-page-header">
            <div>
                <h1 class="b-stock-page-header__title">Мой гардероб</h1>
                <p class="b-stock-page-header__subtitle">
                    В гардеробе: {{ $statusCounts['owned'] ?? 0 }} · нужно купить: {{ $statusCounts['wishlist'] ?? 0 }}
                </p>
            </div>
            <div class="b-stock-page-header__actions">
                <button type="button" class="b-stock-btn b-stock-btn--primary js-wardrobe-item-create"><i class="fas fa-plus"></i> Добавить вещь</button>
            </div>
        </div>

        @include('user.wardrobe.parts.section_tabs', ['active' => 'items'])

        <div class="b-stock-tabs">
            @foreach (['all' => 'Все'] + \App\Models\WardrobeItem::STATUSES as $slug => $label)
                <a href="{{ route('wardrobe.items.index', array_merge(request()->except('status'), ['status' => $slug])) }}" class="b-stock-tabs__item @if ($activeStatus === $slug) is-active @endif">
                    {{ $label }} <span class="b-wardrobe-count">{{ $slug === 'all' ? $total : ($statusCounts[$slug] ?? 0) }}</span>
                </a>
            @endforeach
        </div>

        <div class="b-stock-toolbar">
            <form method="GET" class="b-stock-toolbar__search">
                @foreach (request()->except(['search']) as $param => $value)
                    <input type="hidden" name="{{ $param }}" value="{{ $value }}">
                @endforeach
                <i class="fas fa-search"></i>
                <input type="text" name="search" value="{{ $filters['search'] ?? '' }}" placeholder="Поиск по названию, бренду, цвету или материалу" autocomplete="off">
            </form>
            <div class="b-stock-toolbar__spacer"></div>
            <button type="button" class="b-stock-btn b-stock-btn--outline" data-filter-panel-open="wardrobe-items">
                <i class="fas fa-sliders-h"></i> Фильтры @if ($activeFilters)<span class="b-wardrobe-count b-wardrobe-count--accent">{{ $activeFilters }}</span>@endif
            </button>
        </div>

        @if ($items->isEmpty())
            <div class="b-stock-empty">
                <i class="fas fa-tshirt b-stock-empty__icon"></i>
                @if ($total === 0)
                    <p>В гардеробе пока пусто</p>
                    <button type="button" class="b-stock-btn b-stock-btn--primary js-wardrobe-item-create">Добавить первую вещь</button>
                @else
                    <p>По этим условиям ничего не нашлось</p>
                    <a href="{{ route('wardrobe.items.index') }}" class="b-stock-btn b-stock-btn--outline">Сбросить фильтры</a>
                @endif
            </div>
        @else
            <div class="b-wardrobe-grid">
                @foreach ($items as $item)
                    @include('user.wardrobe.parts.item_card', ['item' => $item])
                @endforeach
            </div>
        @endif

    </div>
</section>

@include('user.wardrobe.parts.item_form_modal')
@include('user.wardrobe.parts.item_filters')
@include('user.includes.modal_delete_item')
@endsection

@push('footer-scripts')
    @include('user.wardrobe.parts.footer_scripts')
@endpush
