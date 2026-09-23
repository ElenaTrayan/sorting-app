@extends('layouts.user_layout')

@section('title', 'База контактов')

@push('head-styles')
    {{-- Manrope — шрифт макета Claude Design для этой страницы (самостоятельно захостен
         в /admin/fonts/manrope, по тому же принципу, что и nunito/source-sans-pro) --}}
    <link rel="stylesheet" href="/admin/fonts/manrope/manrope.css">
@endpush

@push('navbar-breadcrumb')
    <li class="nav-item d-flex align-items-center">
        <ol class="breadcrumb b-navbar-breadcrumb">
            <li class="breadcrumb-item"><a href="{{ route('cabinet.home') }}">Главная</a></li>
            <li class="breadcrumb-item active">База контактов</li>
        </ol>
    </li>
@endpush

@section('content')
    @php
        // русская плюрализация ("1 контакт" / "2 контакта" / "5 контактов") —
        // в проекте нигде не завязывались на trans_choice(), у Laravel по
        // умолчанию нет правил множественного числа для локали "ru"
        $ruPlural = function ($n, $one, $few, $many) {
            $n = abs((int) $n) % 100;
            $n1 = $n % 10;
            if ($n > 10 && $n < 20) {
                return $many;
            }
            if ($n1 > 1 && $n1 < 5) {
                return $few;
            }
            if ($n1 === 1) {
                return $one;
            }
            return $many;
        };
    @endphp

    {{-- хлебные крошки для этой страницы вынесены в верхний navbar-nav-left, см.
         resources/views/layouts/user_layout.blade.php (по макету Claude Design) --}}
    <div class="content-header content-header--contacts">
        <div class="container-fluid">
            <div class="row mb-2 align-items-center">
                <div class="col-sm-6">
                    <h1 class="m-0">База контактов</h1>
                    @if ($contacts->isNotEmpty())
                        <div class="b-contacts-toolbar__count js-contacts-count" data-total="{{ $contacts->count() }}" data-word="{{ $ruPlural($contacts->count(), 'контакт', 'контакта', 'контактов') }}">{{ $contacts->count() }} из {{ $contacts->count() }} {{ $ruPlural($contacts->count(), 'контакт', 'контакта', 'контактов') }}</div>
                    @endif
                </div>
                <div class="col-sm-6 d-flex justify-content-sm-end">
                    <button type="button" class="b-contacts-toolbar__create js-contact-create-btn">
                        <i class="fas fa-plus"></i> Создать контакт
                    </button>
                </div>
            </div>
        </div>
    </div>

    <section class="content">
        <div class="container-fluid">
            <div class="contacts-index" id="contacts-index" data-store-action="{{ route('contacts.store') }}" data-search-hashtag-action="{{ route('search.hashtag') }}" data-create-hashtag-action="{{ route('hashtags.store') }}">

                @if (session('success'))
                    @push('footer-scripts')
                        <script>toastr.success('{{ session('success') }}');</script>
                    @endpush
                @elseif(session('errors'))
                    @push('footer-scripts')
                        <script>toastr.error('{{ session('errors') }}');</script>
                    @endpush
                @endif

                @if ($contacts->isEmpty())
                    <div class="b-contacts-empty">
                        <i class="fas fa-address-book b-contacts-empty__icon"></i>
                        <p>Пока нет ни одного контакта</p>
                        <button type="button" class="btn btn-info js-contact-create-btn">Создать первый контакт</button>
                    </div>
                @else
                    <div class="b-contacts-filterbar">
                        <label class="b-contacts-filterbar__search">
                            <i class="fas fa-search"></i>
                            <input type="text" class="js-contacts-search-input" placeholder="Поиск по имени или хештегу">
                        </label>
                        <button type="button" class="b-contacts-filterbar__btn js-contacts-filter-btn">
                            <i class="fas fa-sliders-h"></i> Фильтры
                        </button>
                        <button type="button" class="b-contacts-filterbar__btn js-contacts-sort-btn" data-order="asc">
                            <i class="fas fa-sort-alpha-down"></i> <span class="js-contacts-sort-label">А–Я</span>
                        </button>
                    </div>

                    <div class="b-contacts-grid js-contacts-grid">
                        @foreach ($contacts as $contact)
                            @php $shownTags = $contact->hashtags->take(3); $restTags = $contact->hashtags->slice(3); @endphp
                            <a href="{{ route('contacts.show', $contact->id) }}" class="b-contact-card" data-hashtag-ids="{{ $contact->hashtags->pluck('id')->implode(',') }}" data-name="{{ $contact->name }}" title="{{ $contact->name }}{{ $contact->hashtags->isNotEmpty() ? ' — ' . $contact->hashtags->pluck('title')->implode(', ') : '' }}">
                                <div class="b-contact-card__top">
                                    <div class="b-contact-card__photo" @if ($contact->photo_url) style="background-image: url({{ $contact->photo_url }})" @endif>
                                        @if (!$contact->photo_url)
                                            <span class="b-contact-card__initials">{{ $contact->initials }}</span>
                                        @endif
                                        <span class="b-contact-card__status-dot" title="Активна"></span>
                                    </div>
                                    <div class="b-contact-card__id">
                                        <div class="b-contact-card__name">{{ $contact->name }}</div>
                                        <div class="b-contact-card__handle">{{ $contact->demo['handle'] }}</div>
                                    </div>
                                </div>

                                <div class="b-contact-card__meta">
                                    <span><i class="fas fa-map-marker-alt"></i> {{ $contact->residence ?: '—' }}</span>
                                    <span class="b-contact-card__meta-sep">|</span>
                                    <span><i class="far fa-calendar-alt"></i> {{ $contact->birth_date ? $contact->birth_date->age . ' ' . $ruPlural($contact->birth_date->age, 'год', 'года', 'лет') : '—' }}</span>
                                </div>

                                <div class="b-contact-card__tags">
                                    @foreach ($shownTags as $i => $hashtag)
                                        <span class="b-contact-card__tag @if ($i === 0) b-contact-card__tag--primary @endif">#{{ $hashtag->title }}</span>
                                    @endforeach
                                    @if ($restTags->isNotEmpty())
                                        <span class="b-contact-card__tag b-contact-card__tag--more" title="{{ $restTags->pluck('title')->implode(', ') }}">+{{ $restTags->count() }}</span>
                                    @endif
                                </div>
                            </a>
                        @endforeach
                    </div>

                    <div class="b-contacts-empty b-contacts-empty--search js-contacts-empty-search" style="display: none;">
                        <i class="fas fa-search b-contacts-empty__icon"></i>
                        <p>Контакты не найдены</p>
                    </div>

                    @include('user.contacts.parts.filters_panel')
                @endif

            </div>
        </div>
    </section>

    @include('user.contacts.parts.contact_form_modal', ['mode' => 'create'])
@endsection
