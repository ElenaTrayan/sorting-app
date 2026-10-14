@extends('layouts.user_layout')

@section('title', 'Образы')

@push('head-styles')
    @include('user.wardrobe.parts.head_assets')
@endpush

@push('navbar-breadcrumb')
    @include('user.wardrobe.parts.breadcrumb', ['page' => 'Образы'])
@endpush

@section('content')
@php
    use App\Models\WardrobeItem;
    use App\Models\WardrobeOutfit;
    $hasFilters = count(array_filter($filters, fn ($value) => $value !== null && $value !== '')) > 0;
@endphp
<section class="content b-stock b-wardrobe">
    <div class="container-fluid">

        <div class="b-stock-page-header">
            <div>
                <h1 class="b-stock-page-header__title">Образы</h1>
                <p class="b-stock-page-header__subtitle">Сочетания вещей из гардероба и из списка покупок</p>
            </div>
            <div class="b-stock-page-header__actions">
                <a href="{{ route('wardrobe.outfits.create') }}" class="b-stock-btn b-stock-btn--primary"><i class="fas fa-plus"></i> Создать образ</a>
            </div>
        </div>

        @include('user.wardrobe.parts.section_tabs', ['active' => 'outfits'])

        <div class="b-stock-tabs">
            @foreach (['all' => 'Все', 'ready' => 'Готовые', 'to_buy' => 'Нужно докупить'] as $slug => $label)
                <a href="{{ route('wardrobe.outfits.index', array_merge(request()->except('tab'), ['tab' => $slug])) }}" class="b-stock-tabs__item @if ($activeTab === $slug) is-active @endif">{{ $label }}</a>
            @endforeach
        </div>

        <form method="GET" class="b-stock-toolbar b-wardrobe-outfit-filters">
            <input type="hidden" name="tab" value="{{ $activeTab }}">
            <div class="b-stock-toolbar__search">
                <i class="fas fa-search"></i>
                <input type="text" name="search" value="{{ $filters['search'] ?? '' }}" placeholder="Поиск по названию">
            </div>
            <select name="season" class="b-wardrobe-select">
                <option value="">Любой сезон</option>
                @foreach (WardrobeOutfit::SEASONS as $slug => $label)
                    <option value="{{ $slug }}" @if (($filters['season'] ?? '') === $slug) selected @endif>{{ $label }}</option>
                @endforeach
            </select>
            <select name="body_type" class="b-wardrobe-select">
                <option value="">Кому подходит</option>
                @foreach (WardrobeItem::BODY_TYPES as $slug => $label)
                    <option value="{{ $slug }}" @if (($filters['body_type'] ?? '') === $slug) selected @endif>{{ $label }}</option>
                @endforeach
            </select>
            <label class="b-wardrobe-temp" title="Покажет образы, которым комфортна эта температура">
                <i class="fas fa-thermometer-half"></i>
                <input type="number" name="temp" min="-60" max="60" value="{{ $filters['temp'] ?? '' }}" placeholder="На погоду, °C">
            </label>
            <button type="submit" class="b-stock-btn b-stock-btn--outline">Показать</button>
            @if ($hasFilters)
                <a href="{{ route('wardrobe.outfits.index', ['tab' => $activeTab]) }}" class="b-wardrobe-reset">Сбросить</a>
            @endif
        </form>

        @if ($outfits->isEmpty())
            <div class="b-stock-empty">
                <i class="fas fa-layer-group b-stock-empty__icon"></i>
                @if ($hasFilters || $activeTab !== 'all')
                    <p>По этим условиям образов не нашлось</p>
                    <a href="{{ route('wardrobe.outfits.index') }}" class="b-stock-btn b-stock-btn--outline">Сбросить фильтры</a>
                @else
                    <p>Образов пока нет</p>
                    <a href="{{ route('wardrobe.outfits.create') }}" class="b-stock-btn b-stock-btn--primary">Собрать первый образ</a>
                @endif
            </div>
        @else
            <div class="b-wardrobe-grid b-wardrobe-grid--outfits">
                @foreach ($outfits as $outfit)
                    @php
                        $toBuy = $outfit->items->where('status', WardrobeItem::STATUS_WISHLIST)->count();
                        $collage = $outfit->items->filter(fn ($item) => $item->image_url)->take(4);
                        $seasonLabels = WardrobeItem::labelsFor($outfit->seasons, WardrobeOutfit::SEASONS);
                    @endphp
                    {{-- обложка — галерея, как у вещей: превью образа (outfits/preview.blade.php), стрелки — к соседним
                         образам (wardrobe.js); название и данные ниже — ссылка на страницу образа --}}
                    <div class="b-wardrobe-outfit-card">
                        <a href="{{ route('wardrobe.outfits.preview', $outfit) }}" data-fancybox="wardrobe-outfit-gallery" data-type="ajax"
                           class="b-wardrobe-outfit-card__cover">
                            @if ($outfit->image_url)
                                <div class="b-wardrobe-outfit-card__photo" style="background-image:url('{{ $outfit->image_url }}')"></div>
                            @elseif ($collage->isNotEmpty())
                                <div class="b-wardrobe-collage b-wardrobe-collage--{{ $collage->count() }}">
                                    @foreach ($collage as $item)
                                        <div class="b-wardrobe-collage__cell" style="background-image:url('{{ $item->image_url }}')"></div>
                                    @endforeach
                                </div>
                            @else
                                <div class="b-wardrobe-outfit-card__photo"><i class="fas fa-layer-group"></i></div>
                            @endif
                            @if ($toBuy)
                                <span class="b-wardrobe-card__badges"><span class="b-wardrobe-badge b-wardrobe-badge--wishlist"><i class="fas fa-shopping-bag"></i> Докупить: {{ $toBuy }}</span></span>
                            @endif
                        </a>
                        <a href="{{ route('wardrobe.outfits.show', $outfit) }}" class="b-wardrobe-card__body b-wardrobe-outfit-card__link">
                            <div class="b-wardrobe-card__name">{{ $outfit->name }}</div>
                            <div class="b-wardrobe-card__meta">
                                <span><i class="fas fa-tshirt"></i> {{ $outfit->items->count() }}</span>
                                @if ($seasonLabels)<span><i class="far fa-calendar"></i> {{ implode(', ', $seasonLabels) }}</span>@endif
                                @if ($outfit->temperatureLabel())<span><i class="fas fa-thermometer-half"></i> {{ $outfit->temperatureLabel() }}</span>@endif
                                @if ($outfit->wears_count)<span><i class="fas fa-map-marker-alt"></i> {{ $outfit->wears_count }}</span>@endif
                            </div>
                        </a>
                    </div>
                @endforeach
            </div>
        @endif

    </div>
</section>
@endsection

@push('footer-scripts')
    @include('user.wardrobe.parts.footer_scripts')
@endpush
