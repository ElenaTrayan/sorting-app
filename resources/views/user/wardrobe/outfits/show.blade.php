@extends('layouts.user_layout')

@section('title', $outfit->name)

@push('head-styles')
    @include('user.wardrobe.parts.head_assets')
@endpush

@push('navbar-breadcrumb')
    @include('user.wardrobe.parts.breadcrumb', [
        'parent' => ['Образы', route('wardrobe.outfits.index')],
        'page' => $outfit->name,
    ])
@endpush

@section('content')
@php
    use App\Models\WardrobeItem;
    use App\Models\WardrobeOutfit;
    $toBuy = $outfit->items->where('status', WardrobeItem::STATUS_WISHLIST);
    $toBuySum = $toBuy->sum('price');
    $seasonLabels = WardrobeItem::labelsFor($outfit->seasons, WardrobeOutfit::SEASONS);
    $bodyTypeLabels = WardrobeItem::labelsFor($outfit->body_types, WardrobeItem::BODY_TYPES);
@endphp
<section class="content b-stock b-wardrobe">
    <div class="container-fluid">

        <div class="b-stock-page-header">
            <div>
                <h1 class="b-stock-page-header__title">{{ $outfit->name }}</h1>
                <p class="b-stock-page-header__subtitle">
                    @if ($toBuy->isEmpty())
                        Образ готов — все вещи есть в гардеробе
                    @else
                        Нужно докупить: {{ $toBuy->count() }}@if ($toBuySum) · примерно {{ number_format($toBuySum, 0, ',', ' ') }}@endif
                    @endif
                </p>
            </div>
            <div class="b-stock-page-header__actions">
                <a href="{{ route('wardrobe.outfits.edit', $outfit) }}" class="b-stock-btn b-stock-btn--outline"><i class="fas fa-pen"></i> Редактировать</a>
                <button type="button" class="b-stock-btn b-stock-btn--outline"
                        data-stock-delete-url="{{ route('wardrobe.outfits.destroy', $outfit) }}"
                        data-stock-delete-redirect="{{ route('wardrobe.outfits.index') }}"
                        data-stock-delete-title="Удалить образ «{{ $outfit->name }}»?"
                        data-stock-delete-text="Сами вещи останутся в гардеробе."><i class="fas fa-trash"></i> Удалить</button>
            </div>
        </div>

        <div class="b-stock-row">
            <div class="b-stock-col b-wardrobe-outfit-main">
                <div class="b-stock-section">
                    {{-- фото образа и фото вещей — одна галерея с зумом и листанием (wardrobe.js, группа wardrobe-outfit-photo-*) --}}
                    @if ($outfit->image_url)
                        <a href="{{ $outfit->image_url }}" data-fancybox="wardrobe-outfit-photo-{{ $outfit->id }}" data-caption="{{ $outfit->name }}" class="b-wardrobe-outfit-image">
                            <img src="{{ $outfit->image_url }}" alt="{{ $outfit->name }}">
                        </a>
                    @endif

                    <div class="b-stock-section__head">
                        <h2 class="b-stock-section__title">Из чего составлен</h2>
                    </div>
                    <div class="b-wardrobe-tiles b-wardrobe-tiles--static">
                        @foreach ($outfit->items as $item)
                            <div class="b-wardrobe-tile">
                                @if ($item->image_url)
                                    <a href="{{ $item->image_url }}" data-fancybox="wardrobe-outfit-photo-{{ $outfit->id }}" data-caption="{{ $item->name }}"
                                       class="b-wardrobe-tile__photo b-wardrobe-tile__photo--zoom" style="background-image:url('{{ $item->image_url }}')">
                                        @if ($item->isWishlist())<span class="b-wardrobe-badge b-wardrobe-badge--wishlist">Купить</span>@endif
                                        @if ($item->needs_repair)<span class="b-wardrobe-badge b-wardrobe-badge--repair" title="{{ $item->repair_note }}"><i class="fas fa-tools"></i></span>@endif
                                    </a>
                                @else
                                    <span class="b-wardrobe-tile__photo">
                                        <i class="fas fa-tshirt"></i>
                                        @if ($item->isWishlist())<span class="b-wardrobe-badge b-wardrobe-badge--wishlist">Купить</span>@endif
                                        @if ($item->needs_repair)<span class="b-wardrobe-badge b-wardrobe-badge--repair" title="{{ $item->repair_note }}"><i class="fas fa-tools"></i></span>@endif
                                    </span>
                                @endif
                                <span class="b-wardrobe-tile__name">{{ $item->name }}</span>
                                <span class="b-wardrobe-tile__sub">
                                    {{ $item->categoryLabel() }}
                                    @if ($item->isWishlist() && $item->price) · {{ number_format($item->price, 0, ',', ' ') }}@endif
                                </span>
                                @if ($item->isWishlist() && $item->shop_url)
                                    <a href="{{ $item->shop_url }}" target="_blank" rel="noopener" class="b-wardrobe-tile__link">В магазин <i class="fas fa-external-link-alt"></i></a>
                                @endif
                            </div>
                        @endforeach
                    </div>
                </div>
            </div>

            <div class="b-stock-col">
                <div class="b-stock-section">
                    <div class="b-stock-section__head">
                        <h2 class="b-stock-section__title">Об образе</h2>
                    </div>
                    <dl class="b-wardrobe-facts">
                        <dt><i class="far fa-calendar"></i> Сезон</dt>
                        <dd>{{ $seasonLabels ? implode(', ', $seasonLabels) : '—' }}</dd>
                        <dt><i class="fas fa-thermometer-half"></i> Комфортная температура</dt>
                        <dd>{{ $outfit->temperatureLabel() ?? '—' }}</dd>
                        <dt><i class="fas fa-user"></i> Кому подходит</dt>
                        <dd>{{ $bodyTypeLabels ? implode(', ', $bodyTypeLabels) : '—' }}</dd>
                        @if ($outfit->note)
                            <dt><i class="fas fa-sticky-note"></i> Заметка</dt>
                            <dd>{!! nl2br(e($outfit->note)) !!}</dd>
                        @endif
                    </dl>
                </div>

                <div class="b-stock-section">
                    <div class="b-stock-section__head">
                        <h2 class="b-stock-section__title">Куда надевала</h2>
                    </div>

                    <form data-stock-ajax-form data-method="POST" action="{{ route('wardrobe.outfits.wears.store', $outfit) }}" class="b-wardrobe-wear-form">
                        <div class="b-stock-form-row">
                            <div class="b-stock-form-field">
                                <div class="b-stock-form-input">
                                    <i class="fas fa-map-marker-alt"></i>
                                    <input type="text" name="place" required maxlength="255" placeholder="Напр. День рождения Оли, театр">
                                </div>
                            </div>
                            <div class="b-stock-form-field b-wardrobe-wear-form__date">
                                <div class="b-stock-form-input">
                                    <input type="date" name="worn_at" value="{{ now()->format('Y-m-d') }}">
                                </div>
                            </div>
                        </div>
                        <div class="b-stock-form-row">
                            <div class="b-stock-form-field">
                                <div class="b-stock-form-input">
                                    <i class="fas fa-sticky-note"></i>
                                    <input type="text" name="note" maxlength="2000" placeholder="Заметка: было удобно, жарко, комплименты…">
                                </div>
                            </div>
                            <button type="submit" class="b-stock-btn b-stock-btn--primary b-wardrobe-wear-form__submit"><i class="fas fa-plus"></i> Добавить</button>
                        </div>
                    </form>

                    @if ($outfit->wears->isEmpty())
                        <div class="b-wardrobe-hint">Записей пока нет</div>
                    @else
                        <div class="b-stock-mini-list">
                            @foreach ($outfit->wears as $wear)
                                <div class="b-stock-mini-item">
                                    <div class="b-stock-mini-item__body">
                                        <div class="b-stock-mini-item__title">{{ $wear->place }}</div>
                                        <div class="b-stock-mini-item__sub">
                                            @if ($wear->worn_at){{ $wear->worn_at->format('d.m.Y') }}@endif
                                            @if ($wear->worn_at && $wear->note) · @endif
                                            {{ $wear->note }}
                                        </div>
                                    </div>
                                    <button type="button" class="b-stock-more__btn" title="Удалить запись"
                                            data-stock-delete-url="{{ route('wardrobe.outfits.wears.destroy', $wear) }}"
                                            data-stock-delete-title="Удалить запись «{{ $wear->place }}»?"><i class="fas fa-trash"></i></button>
                                </div>
                            @endforeach
                        </div>
                    @endif
                </div>
            </div>
        </div>

    </div>
</section>

@include('user.includes.modal_delete_item')
@endsection

@push('footer-scripts')
    @include('user.wardrobe.parts.footer_scripts')
@endpush
