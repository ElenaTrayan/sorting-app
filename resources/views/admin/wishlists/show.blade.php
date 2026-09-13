@extends('layouts.admin_layout')

@section('title', $wishlist->title)

@section('content')
    <div class="content-header content-header--wishlist">
        <div class="container-fluid">
            <div class="row mb-2">
                <div class="col-sm-12">
                    <ol class="breadcrumb float-sm-right">
                        <li class="breadcrumb-item"><a href="{{ route('homeAdmin') }}">Главная</a></li>
                        <li class="breadcrumb-item"><a href="{{ route('wishlists.index') }}">Мой список подарков</a></li>
                        <li class="breadcrumb-item active">{{ $wishlist->title }}</li>
                    </ol>
                </div>
            </div>
        </div>
    </div>

    <section class="content">
        <div class="container-fluid">
            <div class="b-wishlist-page" id="wishlist-page"
                 data-wishlist-id="{{ $wishlist->id }}"
                 data-public-url="{{ $publicUrl }}"
                 data-update-action="{{ route('wishlists.update', $wishlist->id) }}"
                 data-destroy-action="{{ route('wishlists.destroy', $wishlist->id) }}"
                 data-link-preview-action="{{ route('wishlists.link-preview') }}"
                 data-items-store-action="{{ route('wishlists.items.store', $wishlist->id) }}"
                 data-item-base-url="{{ url('/admin_panel/wishlist-items') }}"
                 data-index-url="{{ route('wishlists.index') }}">

                @if (session('success'))
                    @push('footer-scripts')
                        <script>toastr.success('{{ session('success') }}');</script>
                    @endpush
                @endif

                @php
                    $occasion = collect($occasions)->firstWhere('slug', $wishlist->occasion);
                    $totalItemsCount = $wishlist->items->count();
                    $availableItemsCount = $wishlist->items->where('is_reserved', false)->where('is_fulfilled', false)->count();
                    $reservedItemsCount = $totalItemsCount - $availableItemsCount;
                    $totalPriceSum = $wishlist->items->sum('price');

                    // простая русская плюрализация "1 желание / 2 желания / 5 желаний"
                    $pluralizeWish = function ($n) {
                        $mod100 = abs($n) % 100;
                        $mod10 = $mod100 % 10;
                        if ($mod100 >= 11 && $mod100 <= 19) {
                            return 'желаний';
                        }
                        if ($mod10 === 1) {
                            return 'желание';
                        }
                        if ($mod10 >= 2 && $mod10 <= 4) {
                            return 'желания';
                        }
                        return 'желаний';
                    };
                @endphp

                <div class="b-wishlist-head">
                    <div class="b-wishlist-head__text">
                        <h2 class="b-wishlist-head__title js-wishlist-title">{{ $wishlist->title }}</h2>
                        <p class="b-wishlist-head__description js-wishlist-description">
                            {{ $wishlist->description ?: 'Дополнительная информация о вишлисте (необязательно)' }}
                        </p>
                    </div>

                    <div class="b-wishlist-head__actions">
                        <button type="button" class="btn btn-outline-secondary js-wishlist-share">
                            <i class="fas fa-share-alt mr-1"></i> Поделиться
                        </button>
                        <button type="button" class="btn btn-primary js-wishlist-add-item">
                            <i class="fas fa-plus mr-1"></i> Добавить желание
                        </button>
                        <button type="button" class="btn btn-outline-secondary js-wishlist-edit">
                            <i class="fas fa-cog mr-1"></i> Настройки вишлиста
                        </button>
                    </div>
                </div>

                <div class="b-wishlist-occasion-bar">
                    @if ($occasion || $wishlist->occasion)
                        <div class="b-wishlist-occasion-bar__badge">
                            <div class="b-wishlist-occasion-bar__icon">
                                <i class="fas {{ $occasion['icon'] ?? 'fa-gift' }}"></i>
                            </div>
                            <div class="b-wishlist-occasion-bar__title">
                                {{ $occasion['title'] ?? $wishlist->occasion }}
                                <i class="fas fa-pencil-alt b-wishlist-occasion-bar__edit js-wishlist-edit" title="Изменить"></i>
                            </div>
                        </div>
                    @else
                        <button type="button" class="b-wishlist-occasion-bar__add js-wishlist-edit">
                            <i class="fas fa-plus mr-1"></i> Указать повод
                        </button>
                    @endif

                    <div class="b-wishlist-occasion-bar__stats">
                        <span><i class="fas fa-gift mr-1"></i>{{ $totalItemsCount }} {{ $pluralizeWish($totalItemsCount) }}</span>
                        <span><i class="far fa-heart mr-1"></i>{{ number_format($totalPriceSum, 0, '.', ' ') }} грн</span>
                    </div>
                </div>

                <div class="b-wishlist-toolbar">
                    <div class="b-wishlist-toolbar__tabs">
                        <button type="button" class="b-wishlist-toolbar__tab b-wishlist-toolbar__tab--active js-wishlist-tab" data-tab="all">Все ({{ $totalItemsCount }})</button>
                        <button type="button" class="b-wishlist-toolbar__tab js-wishlist-tab" data-tab="available">Свободные ({{ $availableItemsCount }})</button>
                        <button type="button" class="b-wishlist-toolbar__tab js-wishlist-tab" data-tab="reserved">Забронированные ({{ $reservedItemsCount }})</button>
                    </div>

                    <div class="b-wishlist-toolbar__right">
                        <button type="button" class="b-wishlist-toolbar__btn js-wishlist-filter-toggle">
                            <i class="fas fa-filter mr-1"></i> Фильтр
                        </button>

                        <span class="b-wishlist-toolbar__divider"></span>

                        <div class="b-wishlist-toolbar__dropdown js-wishlist-sort-dropdown">
                            <button type="button" class="b-wishlist-toolbar__btn js-wishlist-sort-toggle">
                                <i class="fas fa-sort mr-1"></i> Сортировка <i class="fas fa-chevron-down ml-1"></i>
                            </button>
                            <div class="b-wishlist-toolbar__menu js-wishlist-sort-menu">
                                <button type="button" class="b-wishlist-toolbar__menu-item b-wishlist-toolbar__menu-item--active js-wishlist-sort-option" data-sort="date-desc">Сначала новые</button>
                                <button type="button" class="b-wishlist-toolbar__menu-item js-wishlist-sort-option" data-sort="price-asc">Цена: по возрастанию</button>
                                <button type="button" class="b-wishlist-toolbar__menu-item js-wishlist-sort-option" data-sort="price-desc">Цена: по убыванию</button>
                            </div>
                        </div>

                        <span class="b-wishlist-toolbar__count js-wishlist-count">{{ $totalItemsCount }} из {{ $totalItemsCount }}</span>
                    </div>
                </div>

                {{-- отступ вынесен на обёртку, а не на сам .b-wishlist-items — Masonry ставит карточки
                     position:absolute, и их width:% считается от padding-box контейнера, а не от
                     content-box (как у обычных блоков); если бы padding был на самом
                     .b-wishlist-items, эти два значения ширины не совпадали бы, и Masonry
                     ошибалась бы с количеством колонок (ширина карточки против фактически
                     доступного места) --}}
                <div class="b-wishlist-items-wrap">
                    <div class="b-wishlist-items js-wishlist-items">
                        @include('admin.wishlists.parts.item_cards', ['items' => $wishlist->items->sortByDesc('created_at'), 'isOwner' => true])
                    </div>
                </div>

                <div class="b-wishlist-empty-hint js-wishlist-empty-hint" style="{{ $wishlist->items->isEmpty() ? '' : 'display: none;' }}">
                    <p>Идеи, чтобы начать</p>
                    <div class="b-wishlist-empty-hint__chips">
                        <button type="button" class="b-wishlist-empty-hint__chip js-wishlist-idea-chip">Подарок на день рождения</button>
                        <button type="button" class="b-wishlist-empty-hint__chip js-wishlist-idea-chip">Стильная чашка в подарок</button>
                        <button type="button" class="b-wishlist-empty-hint__chip js-wishlist-idea-chip">Настольная игра для компании</button>
                        <button type="button" class="b-wishlist-empty-hint__chip js-wishlist-idea-chip">Сертификат на впечатления</button>
                    </div>
                </div>

            </div>
        </div>
    </section>

    @include('admin.wishlists.parts.add_item_modal', ['categories' => $categories, 'priorities' => $priorities])
    @include('admin.wishlists.parts.edit_wishlist_modal', ['wishlist' => $wishlist, 'occasions' => $occasions])
    @include('admin.wishlists.parts.item_detail_modal', ['isOwner' => true, 'priorities' => $priorities])
    @include('admin.wishlists.parts.fulfill_confirm_modal')
    @include('admin.wishlists.parts.share_wishlist_modal')
    @include('admin.wishlists.parts.filter_panel', ['categories' => $categories, 'priorities' => $priorities, 'storeDomains' => $storeDomains])

    <div class="modal fade" id="modal-delete-wishlist-item" tabindex="-1" role="dialog" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered" role="document">
            <div class="modal-content">
                <div class="modal-header"><h5 class="modal-title">Удалить желание?</h5></div>
                <div class="modal-body">Это действие нельзя отменить.</div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-danger js-wishlist-item-delete-confirm">Удалить</button>
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Отмена</button>
                </div>
            </div>
        </div>
    </div>
@endsection
