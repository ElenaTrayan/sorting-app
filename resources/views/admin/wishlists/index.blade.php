@extends('layouts.admin_layout')

@section('title', 'Мой список подарков')

@push('head-styles')
    {{-- Manrope — шрифт макета Claude Design, тот же, что и на "База контактов"
         (самостоятельно захостен в /admin/fonts/manrope) --}}
    <link rel="stylesheet" href="/admin/fonts/manrope/manrope.css">
@endpush

@section('content')
    <div class="content-header content-header--wishlists">
        <div class="container-fluid">
            <div class="b-wishlists-header">
                <div>
                    <h1 class="m-0">Мой список подарков</h1>
                    <div class="b-wishlists-header__subtitle">Создавайте вишлисты и сохраняйте идеи подарков</div>
                </div>
                <div class="b-wishlists-header__right">
                    <ol class="breadcrumb b-wishlists-header__breadcrumb">
                        <li class="breadcrumb-item"><a href="{{ route('homeAdmin') }}">Главная</a></li>
                        <li class="breadcrumb-item active">Мой список подарков</li>
                    </ol>
                    <button type="button" class="b-wishlists-toolbar__create js-wishlist-create-btn">
                        <i class="fas fa-plus"></i> Создать вишлист
                    </button>
                </div>
            </div>
        </div>
    </div>

    <section class="content">
        <div class="container-fluid">
            <div class="wishlists-index" id="wishlists-index" data-store-action="{{ route('wishlists.store') }}">

                @if (session('success'))
                    @push('footer-scripts')
                        <script>toastr.success('{{ session('success') }}');</script>
                    @endpush
                @elseif(session('errors'))
                    @push('footer-scripts')
                        <script>toastr.error('{{ session('errors') }}');</script>
                    @endpush
                @endif

                @if ($wishlists->isEmpty())
                    <div class="b-wishlists-empty">
                        <i class="fas fa-gift b-wishlists-empty__icon"></i>
                        <p>Пока нет ни одного вишлиста</p>
                        <button type="button" class="btn btn-info js-wishlist-create-btn">Создать первый вишлист</button>
                    </div>
                @else
                    <div class="b-wishlists-filterbar">
                        <label class="b-wishlists-filterbar__search">
                            <i class="fas fa-search"></i>
                            <input type="text" class="js-wishlists-search-input" placeholder="Поиск по вишлистам...">
                        </label>
                        <div class="b-wishlists-sort js-wishlists-sort">
                            <button type="button" class="b-wishlists-sort__trigger js-wishlists-sort-trigger">
                                <i class="fas fa-sort-amount-down"></i>
                                <span class="js-wishlists-sort-label">Сначала новые</span>
                                <i class="fas fa-chevron-down b-wishlists-sort__chevron"></i>
                            </button>
                            <ul class="b-wishlists-sort__list js-wishlists-sort-list">
                                <li class="b-wishlists-sort__option is-active js-wishlists-sort-option" data-order="new">Сначала новые</li>
                                <li class="b-wishlists-sort__option js-wishlists-sort-option" data-order="old">Сначала старые</li>
                                <li class="b-wishlists-sort__option js-wishlists-sort-option" data-order="az">А–Я</li>
                                <li class="b-wishlists-sort__option js-wishlists-sort-option" data-order="za">Я–А</li>
                            </ul>
                        </div>
                    </div>

                    <div class="b-wishlists-grid js-wishlists-grid">
                        @foreach ($wishlists as $wishlist)
                            @php
                                $occasion = collect($occasions)->firstWhere('slug', $wishlist->occasion);
                            @endphp
                            <a href="{{ route('wishlists.show', $wishlist->id) }}" class="b-wishlist-card js-wishlist-card" data-title="{{ $wishlist->title }}" data-created="{{ $wishlist->created_at->timestamp }}" data-destroy-action="{{ route('wishlists.destroy', $wishlist->id) }}">
                                <div class="b-wishlist-card__top">🎁</div>

                                <div class="b-wishlist-card__menu js-wishlist-card__menu">
                                    <button type="button" class="b-wishlist-card__menu-toggle js-wishlist-card__menu-toggle" aria-label="Меню вишлиста">
                                        <i class="fas fa-ellipsis-h"></i>
                                    </button>
                                    <ul class="b-wishlist-card__menu-list">
                                        <li class="b-wishlist-card__menu-item js-wishlist-card__delete"><i class="fas fa-trash-alt"></i> Удалить</li>
                                    </ul>
                                </div>

                                <div class="b-wishlist-card__body">
                                    <div class="b-wishlist-card__title">{{ $wishlist->title }}</div>
                                    @if ($occasion)
                                        <div class="b-wishlist-card__occasion"><i class="far fa-calendar-alt"></i> {{ $occasion['title'] }}</div>
                                    @elseif ($wishlist->occasion)
                                        <div class="b-wishlist-card__occasion"><i class="far fa-calendar-alt"></i> {{ $wishlist->occasion }}</div>
                                    @endif
                                    <div class="b-wishlist-card__meta">{{ $wishlist->items_count }} {{ __('желаний') }}</div>
                                </div>
                            </a>
                        @endforeach
                    </div>

                    <div class="b-wishlists-empty b-wishlists-empty--search js-wishlists-empty-search" style="display: none;">
                        <i class="fas fa-search b-wishlists-empty__icon"></i>
                        <p>Вишлисты не найдены</p>
                    </div>
                @endif

            </div>
        </div>
    </section>

    <div class="modal fade" id="modal-create-wishlist" tabindex="-1" role="dialog" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Создать вишлист</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
                </div>
                <div class="modal-body">
                    <div class="form-group">
                        <label for="wishlist-create-title">Название</label>
                        <input type="text" id="wishlist-create-title" class="form-control" placeholder="Мой вишлист">
                    </div>

                    <label>Повод (необязательно)</label>
                    <div class="b-occasion-picker js-occasion-picker">
                        @foreach ($occasions as $occasion)
                            <button type="button" class="b-occasion-picker__item js-occasion-picker__item" data-slug="{{ $occasion['slug'] }}">
                                <i class="fas {{ $occasion['icon'] }} mr-1"></i>{{ $occasion['title'] }}
                            </button>
                        @endforeach
                        <button type="button" class="b-occasion-picker__item js-occasion-picker__more">
                            <i class="fas fa-ellipsis-h mr-1"></i>Больше
                        </button>
                    </div>
                    <input type="text" id="wishlist-create-occasion-custom" class="form-control mt-2" placeholder="Свой повод" style="display: none;">
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-primary js-wishlist-create-confirm">Создать</button>
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Отмена</button>
                </div>
            </div>
        </div>
    </div>
@endsection
