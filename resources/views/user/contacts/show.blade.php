@extends('layouts.user_layout')

@section('title', $contact->name)

@push('head-styles')
    {{-- Manrope — шрифт макета Claude Design для этой страницы (самостоятельно захостен
         в /admin/fonts/manrope, по тому же принципу, что и nunito/source-sans-pro) --}}
    <link rel="stylesheet" href="/admin/fonts/manrope/manrope.css">
@endpush

@push('navbar-breadcrumb')
    <li class="nav-item d-flex align-items-center">
        <ol class="breadcrumb b-navbar-breadcrumb">
            <li class="breadcrumb-item"><a href="{{ route('cabinet.home') }}">Главная</a></li>
            <li class="breadcrumb-item"><a href="{{ route('contacts.index') }}">База контактов</a></li>
            <li class="breadcrumb-item active">{{ $contact->name }}</li>
        </ol>
    </li>
@endpush

@php
    /**
     * $demo — подставные значения для полей, которых нет в БД (знак зодиака,
     * режим дня, страхи, нравится/раздражает, здоровье, связи, места, бывшие,
     * работа/образование). Знак зодиака внутри считается по-настоящему по дате
     * рождения, остальное — стабильный (по id контакта) фейк для вёрстки, пока
     * этих сущностей нет в базе. См. App\Models\Contact::getDemoAttribute().
     */
    $demo = $contact->demo;
@endphp

@section('content')
    {{-- H1 и хлебные крошки для этой страницы убраны из content-header по макету Claude
         Design: имя контакта показано в шапке карточки (.b-contact-head__name), а
         крошки вынесены в верхний navbar-nav-left, см. @push('navbar-breadcrumb') выше
         и resources/views/layouts/user_layout.blade.php --}}
    <section class="content content--contact-page">
        <div class="container-fluid">
            <div class="b-contact-page" id="contact-page"
                 data-contact-id="{{ $contact->id }}"
                 data-update-action="{{ route('contacts.update', $contact->id) }}"
                 data-destroy-action="{{ route('contacts.destroy', $contact->id) }}"
                 data-index-action="{{ route('contacts.index') }}"
                 data-search-hashtag-action="{{ route('search.hashtag') }}"
                 data-create-hashtag-action="{{ route('hashtags.store') }}"
                 data-photos-store-action="{{ route('contacts.photos.store', $contact->id) }}"
                 data-photo-base-url="{{ url('/cabinet/contact-photos') }}"
                 data-conversations-store-action="{{ route('contacts.conversations.store', $contact->id) }}"
                 data-conversation-base-url="{{ url('/cabinet/contact-conversations') }}"
                 data-socials-store-action="{{ route('contacts.socials.store', $contact->id) }}"
                 data-social-base-url="{{ url('/cabinet/contact-socials') }}"
                 data-quotes-store-action="{{ route('contacts.quotes.store', $contact->id) }}"
                 data-quote-base-url="{{ url('/cabinet/contact-quotes') }}"
                 data-quote-recognize-action="{{ route('contacts.quotes.recognize') }}"
                 data-facts-store-action="{{ route('contacts.facts.store', $contact->id) }}"
                 data-fact-base-url="{{ url('/cabinet/contact-facts') }}">

                {{-- ==================== ШАПКА ==================== --}}
                <div class="b-contact-head">
                    <div class="b-contact-head__main">
                        <div class="b-contact-head__photo js-contact-head-photo" @if ($contact->photo_url) style="background-image: url({{ $contact->photo_url }})" @endif>
                            @if (!$contact->photo_url)
                                <span class="b-contact-head__photo-initials">{{ $contact->initials }}</span>
                                <span class="b-contact-head__photo-caption">фото</span>
                            @endif
                        </div>
                        <div class="b-contact-head__info">
                            <div class="b-contact-head__toprow">
                                <div>
                                    <h2 class="b-contact-head__name js-contact-head-name">{{ $contact->name }} <span class="b-contact-head__handle">{{ $demo['handle'] }}</span></h2>
                                    <div class="b-contact-head__role">{{ $demo['role_line'] }}</div>
                                </div>
                                <div class="b-contact-head__actions">
                                    <button type="button" class="btn b-contact-head__edit js-contact-edit-btn" title="Редактировать">
                                        <i class="fas fa-pencil-alt mr-1"></i> Редактировать
                                    </button>
                                    <div class="b-contact-head__menu js-contact-head-menu">
                                        <button type="button" class="b-contact-head__menu-toggle js-contact-head-menu-toggle" title="Ещё">
                                            <i class="fas fa-ellipsis-h"></i>
                                        </button>
                                        <div class="b-contact-head__menu-list js-contact-head-menu-list">
                                            <button type="button" class="b-contact-head__menu-item b-contact-head__menu-item--danger js-contact-delete">
                                                <i class="fas fa-trash-alt mr-2"></i>Удалить контакт
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="b-contact-head__meta">
                                @if ($contact->birth_date)
                                    <span><i class="far fa-calendar-alt mr-1"></i>{{ $contact->birth_date->format('d.m.Y') }} ({{ $contact->birth_date->age }} лет)</span>
                                @endif
                                @if ($contact->residence)
                                    <span><i class="fas fa-map-marker-alt mr-1"></i>{{ $contact->residence }}</span>
                                @endif
                                @if ($contact->phone)
                                    <span><i class="fas fa-phone mr-1"></i>{{ $contact->phone }}</span>
                                @endif
                            </div>

                            <div class="b-contact-head__badges">
                                @if ($demo['zodiac'])
                                    <span class="b-contact-badge" title="Знак зодиака"><i class="fas fa-star mr-1"></i>{{ $demo['zodiac'] }}</span>
                                @endif
                                <span class="b-contact-badge" title="Режим"><i class="fas fa-{{ $demo['regime'] === 'Сова' ? 'moon' : 'sun' }} mr-1"></i>{{ $demo['regime'] }}</span>
                                @foreach ($demo['status_badges'] as $i => $status)
                                    <span class="b-contact-badge b-contact-badge--status-{{ $i }}">{{ $status }}</span>
                                @endforeach
                            </div>

                            @if ($contact->hashtags->isNotEmpty())
                                <div class="b-contact-head__tags">
                                    @foreach ($contact->hashtags as $hashtag)
                                        <span class="b-contact-card__tag">#{{ $hashtag->title }}</span>
                                    @endforeach
                                </div>
                            @endif
                        </div>
                    </div>

                    <div class="b-contact-social">
                        <div class="b-contact-social__header">
                            <h3>Социальные сети</h3>
                            <button type="button" class="b-contact-social__add-btn js-social-add-open" title="Добавить соцсеть"><i class="fas fa-plus"></i></button>
                        </div>
                        <ul class="b-contact-social__list js-contact-socials-list">
                            @foreach ($contact->socialLinks as $social)
                                @php [$socialIcon, $socialColor] = \App\Models\ContactSocialLink::iconFor($social->platform); @endphp
                                <li class="b-contact-social-row" data-id="{{ $social->id }}">
                                    <span class="b-contact-social-row__icon" style="background-color: {{ $socialColor }}"><i class="{{ $socialIcon }}"></i></span>
                                    <span class="b-contact-social-row__value">{{ $social->handle ?: $social->value }}</span>
                                    @if ($social->value && \Illuminate\Support\Str::startsWith(\Illuminate\Support\Str::lower($social->value), 'http'))
                                        <a href="{{ $social->value }}" target="_blank" rel="noopener" class="b-contact-social-row__link"><i class="fas fa-external-link-alt"></i></a>
                                    @endif
                                    <i class="fas fa-times js-contact-list-remove" data-base-url="{{ url('/cabinet/contact-socials') }}"></i>
                                </li>
                            @endforeach
                        </ul>
                        <div class="b-contact-social__empty js-contact-social-empty" @if ($contact->socialLinks->isNotEmpty()) style="display: none;" @endif>
                            <i class="fas fa-link"></i> Пока нет ни одной соцсети
                        </div>
                    </div>
                </div>

                {{-- ==================== ВКЛАДКИ ==================== --}}
                <div class="b-contact-tabs">
                    <button type="button" class="b-contact-tabs__item is-active js-contact-tab-btn" data-tab="overview">Обзор</button>
                    <button type="button" class="b-contact-tabs__item js-contact-tab-btn" data-tab="quotes">Высказывания</button>
                    <button type="button" class="b-contact-tabs__item js-contact-tab-btn" data-tab="facts">Факты</button>
                    <button type="button" class="b-contact-tabs__item js-contact-tab-btn" data-tab="relations">Связи</button>
                    <button type="button" class="b-contact-tabs__item js-contact-tab-btn" data-tab="places">Места</button>
                    <button type="button" class="b-contact-tabs__item js-contact-tab-btn" data-tab="career">Работа и образование</button>
                    <button type="button" class="b-contact-tabs__item js-contact-tab-btn" data-tab="gallery">Галерея</button>
                    <button type="button" class="b-contact-tabs__item js-contact-tab-btn" data-tab="history">История</button>
                    <button type="button" class="b-contact-tabs__item js-contact-tab-btn" data-tab="posts">Посты</button>
                </div>

                {{-- ---------- Обзор: сводка по всем разделам, как в макете ---------- --}}
                <div class="b-contact-tab-panel is-active js-contact-tab-panel" data-tab-panel="overview">
                    <div class="row">
                        <div class="col-md-6">
                            <div class="b-contact-section">
                                <div class="b-contact-section__header"><h3><i class="fas fa-user"></i>О себе</h3></div>
                                <div class="b-contact-about js-contact-about">
                                    @if ($contact->facts->isNotEmpty())
                                        <p>{{ $contact->facts->first()->fact }}</p>
                                    @else
                                        <p>{{ $demo['role_line'] }}. Любимое свободное время: {{ \Illuminate\Support\Str::lower($demo['free_time']) }}.</p>
                                    @endif
                                    @foreach ($contact->quotes as $quote)
                                        <p class="b-contact-about__quote">«{{ $quote->quote }}»</p>
                                    @endforeach
                                </div>
                                <button type="button" class="b-contact-about__toggle js-contact-about-toggle">
                                    <span class="js-contact-about-toggle-label">Показать полностью</span> <i class="fas fa-chevron-down"></i>
                                </button>
                            </div>

                            <div class="b-contact-section">
                                <div class="b-contact-section__header">
                                    <h3><i class="fas fa-users"></i>Связи</h3>
                                    <a href="#" class="b-contact-section__link js-contact-tab-link" data-tab="relations">Все связи <i class="fas fa-arrow-right ml-1"></i></a>
                                </div>
                                @include('user.contacts.parts.relatives_list', ['relatives' => $demo['relatives']])
                            </div>

                            <div class="b-contact-section">
                                <div class="b-contact-section__header">
                                    <h3><i class="fas fa-graduation-cap"></i>Образование</h3>
                                    <a href="#" class="b-contact-section__link js-contact-tab-link" data-tab="career">Подробнее <i class="fas fa-arrow-right ml-1"></i></a>
                                </div>
                                @include('user.contacts.parts.timeline', ['items' => array_slice($demo['education'], 0, 2)])
                            </div>

                            <div class="b-contact-section">
                                <div class="b-contact-section__header">
                                    <h3><i class="fas fa-briefcase"></i>Места работы</h3>
                                    <a href="#" class="b-contact-section__link js-contact-tab-link" data-tab="career">Подробнее <i class="fas fa-arrow-right ml-1"></i></a>
                                </div>
                                @include('user.contacts.parts.timeline', ['items' => array_slice($demo['work_history'], 0, 2)])
                            </div>
                        </div>

                        <div class="col-md-6">
                            <div class="b-contact-section">
                                <div class="b-contact-section__header"><h3><i class="fas fa-id-card"></i>Личная информация</h3></div>
                                @include('user.contacts.parts.personal_info', ['demo' => $demo])
                            </div>

                            <div class="b-contact-section">
                                <div class="b-contact-section__header"><h3><i class="fas fa-heart"></i>Предпочтения</h3></div>
                                @include('user.contacts.parts.preferences', ['demo' => $demo])
                            </div>

                            <div class="b-contact-section">
                                <div class="b-contact-section__header"><h3><i class="fas fa-notes-medical"></i>Здоровье</h3></div>
                                <ul class="b-contact-info-list">
                                    <li><span class="b-contact-info-list__label"><i class="fas fa-allergies mr-2"></i>Аллергии</span><span>{{ $demo['allergies'] }}</span></li>
                                    <li><span class="b-contact-info-list__label"><i class="fas fa-briefcase-medical mr-2"></i>Хронические заболевания</span><span>{{ $demo['chronic'] }}</span></li>
                                </ul>
                            </div>

                            <div class="b-contact-section">
                                <div class="b-contact-section__header">
                                    <h3><i class="fas fa-map-marked-alt"></i>Места</h3>
                                    <a href="#" class="b-contact-section__link js-contact-tab-link" data-tab="places">Все места <i class="fas fa-arrow-right ml-1"></i></a>
                                </div>
                                @include('user.contacts.parts.places_preview', ['demo' => $demo])
                            </div>

                            <div class="b-contact-section">
                                <div class="b-contact-section__header"><h3><i class="fas fa-history"></i>Бывшие отношения</h3></div>
                                @include('user.contacts.parts.exes_list', ['exes' => $demo['exes']])
                            </div>
                        </div>
                    </div>
                </div>

                {{-- ---------- Высказывания ---------- --}}
                <div class="b-contact-tab-panel js-contact-tab-panel" data-tab-panel="quotes">
                    @include('user.contacts.parts.quotes_tab', ['contact' => $contact])
                </div>

                {{-- ---------- Факты ---------- --}}
                <div class="b-contact-tab-panel js-contact-tab-panel" data-tab-panel="facts">
                    @include('user.contacts.parts.facts_tab', ['contact' => $contact])
                </div>

                {{-- ---------- Связи ---------- --}}
                <div class="b-contact-tab-panel js-contact-tab-panel" data-tab-panel="relations">
                    @include('user.contacts.parts.relations_tab', ['contact' => $contact])
                </div>

                {{-- ---------- Места ---------- --}}
                <div class="b-contact-tab-panel js-contact-tab-panel" data-tab-panel="places">
                    @include('user.contacts.parts.places_tab', ['contact' => $contact])
                </div>

                {{-- ---------- Работа и образование ---------- --}}
                <div class="b-contact-tab-panel js-contact-tab-panel" data-tab-panel="career">
                    <div class="row">
                        <div class="col-md-6">
                            <div class="b-contact-section">
                                <div class="b-contact-section__header"><h3><i class="fas fa-graduation-cap"></i>Образование</h3></div>
                                @include('user.contacts.parts.timeline', ['items' => $demo['education']])
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="b-contact-section">
                                <div class="b-contact-section__header"><h3><i class="fas fa-briefcase"></i>Места работы</h3></div>
                                @include('user.contacts.parts.timeline', ['items' => $demo['work_history']])
                            </div>
                        </div>
                    </div>
                </div>

                {{-- ---------- Галерея ---------- --}}
                <div class="b-contact-tab-panel js-contact-tab-panel" data-tab-panel="gallery">
                    <div class="b-contact-section">
                        <div class="b-contact-section__header">
                            <h3><i class="fas fa-images"></i>Фото (в т.ч. скрины из соцсетей)</h3>
                            <label class="btn btn-sm btn-outline-info">
                                <i class="fas fa-plus"></i>
                                <input type="file" class="js-contact-photo-input" accept="image/*" hidden>
                            </label>
                        </div>
                        <div class="b-contact-photos js-contact-photos-grid">
                            @foreach ($contact->photos as $photo)
                                <div class="b-contact-photo" data-id="{{ $photo->id }}">
                                    <a data-fancybox="contact-photos" href="{{ $photo->image_url }}" class="b-contact-photo__thumb" style="background-image: url({{ $photo->image_url }})"></a>
                                    <button type="button" class="b-contact-photo__remove js-contact-photo-remove" title="Удалить"><i class="fas fa-times"></i></button>
                                    @if ($photo->caption)
                                        <div class="b-contact-photo__caption">{{ $photo->caption }}</div>
                                    @endif
                                </div>
                            @endforeach
                        </div>
                    </div>

                    <div class="b-contact-section">
                        <div class="b-contact-section__header">
                            <h3><i class="fas fa-comment-dots"></i>Переписки</h3>
                            <label class="btn btn-sm btn-outline-info">
                                <i class="fas fa-plus"></i>
                                <input type="file" class="js-contact-conversation-input" accept="image/*" hidden>
                            </label>
                        </div>
                        <div class="b-contact-photos js-contact-conversations-grid">
                            @foreach ($contact->conversations as $conversation)
                                <div class="b-contact-photo" data-id="{{ $conversation->id }}">
                                    <a data-fancybox="contact-conversations" href="{{ $conversation->image_url }}" class="b-contact-photo__thumb" style="background-image: url({{ $conversation->image_url }})"></a>
                                    <button type="button" class="b-contact-photo__remove js-contact-conversation-remove" title="Удалить"><i class="fas fa-times"></i></button>
                                    @if ($conversation->caption)
                                        <div class="b-contact-photo__caption">{{ $conversation->caption }}</div>
                                    @endif
                                </div>
                            @endforeach
                        </div>
                        @if ($contact->conversations->isEmpty())
                            <p class="text-muted mb-0">Пока нет ни одного скрина переписки</p>
                        @endif
                    </div>
                </div>

                {{-- ---------- История ---------- --}}
                <div class="b-contact-tab-panel js-contact-tab-panel" data-tab-panel="history">
                    <div class="b-contact-section">
                        <div class="b-contact-section__header"><h3><i class="fas fa-history"></i>История изменений</h3></div>
                        <ul class="b-contact-history-list">
                            <li>
                                <div class="b-contact-history-list__icon"><i class="fas fa-plus"></i></div>
                                <div>
                                    <div>Контакт создан</div>
                                    <div class="text-muted small">{{ $contact->created_at->format('d.m.Y H:i') }} · добавил {{ optional(Auth::user())->name ?? optional(Auth::user())->email }}</div>
                                </div>
                            </li>
                            @if ($contact->updated_at && !$contact->updated_at->equalTo($contact->created_at))
                                <li>
                                    <div class="b-contact-history-list__icon"><i class="fas fa-pencil-alt"></i></div>
                                    <div>
                                        <div>Данные обновлены</div>
                                        <div class="text-muted small">{{ $contact->updated_at->format('d.m.Y H:i') }}</div>
                                    </div>
                                </li>
                            @endif
                        </ul>
                    </div>
                </div>

                {{-- ---------- Посты по хештегам ---------- --}}
                <div class="b-contact-tab-panel js-contact-tab-panel" data-tab-panel="posts">
                    <div class="b-contact-section">
                        <h3><i class="fas fa-hashtag mr-2" style="color:#7367f0"></i>Посты по этим хештегам</h3>
                        @if ($relatedPosts->isEmpty())
                            <p class="text-muted">{{ $contact->hashtags->isEmpty() ? 'У контакта пока нет хештегов — привяжите хештег, чтобы видеть здесь посты.' : 'Постов с такими хештегами пока нет.' }}</p>
                        @else
                            <div class="b-cards">
                                <div class="grid are-images-unloaded">
                                    @include('user.posts.parts.post_items', ['posts' => $relatedPosts])
                                </div>
                            </div>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </section>

    @include('user.contacts.parts.contact_form_modal', ['mode' => 'edit', 'contact' => $contact])
    @include('user.contacts.parts.social_add_modal')

@push('footer-scripts')
    <script>
        Fancybox.bind('[data-fancybox="contact-photos"]', {infinite: false});
        Fancybox.bind('[data-fancybox="contact-conversations"]', {infinite: false});
    </script>
@endpush
@endsection
