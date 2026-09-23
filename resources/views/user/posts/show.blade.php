@extends('layouts.user_layout')

@section('title', 'Посмотреть пост')

@push('navbar-breadcrumb')
    <li class="nav-item d-flex align-items-center">
        <ol class="breadcrumb b-navbar-breadcrumb">
            <li class="breadcrumb-item"><a href="{{ route('cabinet.home') }}">Главная</a></li>
            <li class="breadcrumb-item"><a href="{{ route('posts.index') }}">Все посты</a></li>
            <li class="breadcrumb-item active">Просмотр поста</li>
        </ol>
    </li>
@endpush

@push('head-styles')
    {{-- Manrope — шрифт макета Claude Design, тот же, что и на "Все посты"/"База контактов"
         (самостоятельно захостен в /admin/fonts/manrope) --}}
    <link rel="stylesheet" href="/admin/fonts/manrope/manrope.css">
@endpush

@section('content')
{{-- хлебные крошки для этой страницы вынесены в верхний navbar-nav-left, см.
     resources/views/layouts/user_layout.blade.php. Дизайн страницы — по макету
     https://claude.ai/artifact/MartNNPBmEAjb11QSXznWC; из него не взяты декоративные
     блоки без реальных данных (палитра цветов, "источник", "заметка", "только вы") --}}
<!-- Content Header (Page header) -->
<div class="content-header content-header--post-show">
    <div class="container-fluid">
        <div class="b-post-show-header">
            <a href="{{ route('posts.index') }}" class="b-post-show__back">
                <i class="fas fa-arrow-left"></i> Назад к постам
            </a>

            <div class="b-post-show-header__actions">
                <a href="{{ route('posts.edit', $post->id) }}" class="b-post-show__btn">
                    <i class="fas fa-pencil-alt"></i> Редактировать
                </a>
                <button type="button" class="b-post-show__btn b-post-show__btn--danger"
                        data-action="delete"
                        data-id="{{ $post->id }}"
                        data-url="{{ route('posts.destroy', $post->id) }}"
                        data-page-id="post-show"
                        data-index-url="{{ route('posts.index') }}">
                    <i class="fas fa-trash-alt"></i> Удалить
                </button>
            </div>
        </div>
    </div><!-- /.container-fluid -->
</div>
<!-- /.content-header -->

<!-- Main content -->
<section class="content">
    <div class="container-fluid">
        <div class="b-post-show" id="post-show" data-id="{{ $post->id }}">

            @if (session('success'))
                <div class="alert alert-success" role="alert">
                    <button type="button" class="close" data-dismiss="alert" aria-hidden="true">&times;</button>
                    <i class="icon fa fa-check"></i> {{ session('success') }}
                </div>
            @endif

            @include('user.includes.modal_delete_item')

            @php
                // простая русская плюрализация "1 изображение / 2 изображения / 5 изображений"
                $pluralizeImages = function ($n) {
                    $mod100 = abs($n) % 100;
                    $mod10 = $mod100 % 10;
                    if ($mod100 >= 11 && $mod100 <= 19) {
                        return 'изображений';
                    }
                    if ($mod10 === 1) {
                        return 'изображение';
                    }
                    if ($mod10 >= 2 && $mod10 <= 4) {
                        return 'изображения';
                    }
                    return 'изображений';
                };
            @endphp

            <div class="b-post-show__card">
                <div class="b-post-show__grid">

                    <div class="b-post-show__media">
                        @if ($post->video_url)
                            <div class="b-post-show__media-frame b-post-show__media-frame--video">
                                <video src="{{ $post->video_url }}" controls preload="metadata"></video>
                            </div>
                        @elseif (count($galleryImages) > 0)
                            <div class="b-post-show__media-frame js-post-gallery" data-count="{{ count($galleryImages) }}">
                                <div class="b-post-show__media-slides">
                                    @foreach ($galleryImages as $i => $image)
                                        <a href="/storage{{ $image['full'] }}" data-fancybox="post-gallery" class="b-post-show__media-slide js-post-gallery-slide @if ($i === 0) is-active @endif">
                                            <img src="/storage{{ $image['medium'] }}" alt="">
                                        </a>
                                    @endforeach
                                </div>

                                <div class="b-post-show__media-actions">
                                    <button type="button" class="b-post-show__media-btn js-post-gallery-expand" title="Открыть на весь экран"><i class="fas fa-expand"></i></button>
                                    <a href="/storage{{ $galleryImages[0]['full'] }}" download class="b-post-show__media-btn js-post-gallery-download" title="Скачать"><i class="fas fa-download"></i></a>
                                </div>

                                @if (count($galleryImages) > 1)
                                    <button type="button" class="b-post-show__media-nav b-post-show__media-nav--prev js-post-gallery-prev" title="Предыдущее"><i class="fas fa-chevron-left"></i></button>
                                    <button type="button" class="b-post-show__media-nav b-post-show__media-nav--next js-post-gallery-next" title="Следующее"><i class="fas fa-chevron-right"></i></button>

                                    <div class="b-post-show__media-footer">
                                        <span class="b-post-show__media-counter js-post-gallery-counter">1 / {{ count($galleryImages) }}</span>
                                        <div class="b-post-show__media-thumbs">
                                            @foreach ($galleryImages as $i => $image)
                                                <button type="button" class="b-post-show__media-thumb js-post-gallery-thumb @if ($i === 0) is-active @endif" data-index="{{ $i }}" style="background-image: url('/storage{{ $image['medium'] }}')" title="Изображение {{ $i + 1 }}"></button>
                                            @endforeach
                                        </div>
                                    </div>
                                @endif
                            </div>
                        @else
                            <div class="b-post-show__media-empty">
                                <i class="fas fa-image"></i>
                                <p>Нет изображения</p>
                            </div>
                        @endif
                    </div>

                    <div class="b-post-show__details">
                        <div class="b-post-show__meta">
                            @if ($post->created_at)
                                <span class="b-post-show__meta-item"><i class="far fa-calendar-alt"></i> {{ $post->created_at->format('d.m.Y, H:i') }}</span>
                            @endif
                            @if ($post->video_url)
                                <span class="b-post-show__meta-sep"></span>
                                <span class="b-post-show__meta-item"><i class="fas fa-video"></i> Видео</span>
                            @elseif (count($galleryImages) > 0)
                                <span class="b-post-show__meta-sep"></span>
                                <span class="b-post-show__meta-item"><i class="far fa-images"></i> {{ count($galleryImages) }} {{ $pluralizeImages(count($galleryImages)) }}</span>
                            @endif
                        </div>

                        @if ($post->title || $post->content)
                            <div class="b-post-show__text">
                                @if ($post->title)
                                    <h2 class="b-post-show__title">{{ $post->title }}</h2>
                                @endif
                                @if ($post->content)
                                    <div class="b-post-show__prose">{!! $post->content !!}</div>
                                @endif
                            </div>
                        @elseif (!$post->film)
                            <div class="b-post-show__empty-text">
                                <div class="b-post-show__empty-text-icon"><i class="fas fa-align-left"></i></div>
                                <div>
                                    <div class="b-post-show__empty-text-title">Пост без описания</div>
                                    <div class="b-post-show__empty-text-sub">Текст можно добавить на странице редактирования</div>
                                </div>
                                <a href="{{ route('posts.edit', $post->id) }}" class="b-post-show__empty-text-btn">Добавить текст</a>
                            </div>
                        @endif

                        @if ($post->film)
                            <div class="b-post-show__divider"></div>
                            @include('user.posts.parts.film_card', ['film' => $post->film])
                        @endif

                        <div class="b-post-show__divider"></div>

                        <div class="b-post-show__section">
                            <div class="b-post-show__section-head">
                                <i class="fas fa-hashtag"></i>
                                <span class="b-post-show__section-label">Хештеги</span>
                                @if ($post->hashtags->isNotEmpty())
                                    <span class="b-post-show__section-count">{{ $post->hashtags->count() }}</span>
                                @endif
                            </div>
                            <div class="b-post-show__tags">
                                @foreach ($post->hashtags as $hashtag)
                                    <span class="b-post-show__tag">#{{ $hashtag->title }}</span>
                                @endforeach
                                <a href="{{ route('posts.edit', $post->id) }}" class="b-post-show__tag-add"><i class="fas fa-plus"></i> Добавить</a>
                            </div>
                        </div>

                        @if ($post->category)
                            <div class="b-post-show__divider"></div>

                            <div class="b-post-show__section">
                                <div class="b-post-show__section-head">
                                    <i class="fas fa-folder"></i>
                                    <span class="b-post-show__section-label">Категория</span>
                                </div>
                                <div class="b-post-show__tags">
                                    <span class="b-post-show__category-chip">{{ $post->category->title ?: $post->category->alias }}</span>
                                </div>
                            </div>
                        @endif

                        @if ($groupCount > 1)
                            <div class="b-post-show__divider"></div>

                            <div class="b-post-show__section">
                                <div class="b-post-show__section-head">
                                    <i class="fas fa-layer-group"></i>
                                    <span class="b-post-show__section-label">Группа</span>
                                    <span class="b-post-show__section-count">{{ $groupCount }}</span>
                                </div>
                                <div class="b-post-show__tags">
                                    <a href="{{ route('posts.groups.show', $post->group_id) }}" class="b-post-show__tag-add">
                                        <i class="fas fa-arrows-alt-v"></i> Управлять порядком
                                    </a>
                                </div>
                            </div>
                        @endif
                    </div>

                </div>
            </div>

        </div>
    </div><!-- /.container-fluid -->
</section>
<!-- /.content -->

{{-- в @push('footer-scripts'), а не просто <script> тут же в контенте — @yield('content')
     в layout рендерится ДО <script src="fancybox.umd.js"> внизу body (см.
     layouts/user_layout.blade.php), иначе Fancybox ещё не определён --}}
@push('footer-scripts')
<script>
    Fancybox.bind('[data-fancybox="post-gallery"]', {
        infinite: false
    });

    (function () {
        var gallery = document.querySelector('.js-post-gallery');
        if (!gallery) {
            return;
        }

        var slides = Array.prototype.slice.call(gallery.querySelectorAll('.js-post-gallery-slide'));
        var thumbs = Array.prototype.slice.call(gallery.querySelectorAll('.js-post-gallery-thumb'));
        var counter = gallery.querySelector('.js-post-gallery-counter');
        var downloadBtn = gallery.querySelector('.js-post-gallery-download');
        var expandBtn = gallery.querySelector('.js-post-gallery-expand');
        var prevBtn = gallery.querySelector('.js-post-gallery-prev');
        var nextBtn = gallery.querySelector('.js-post-gallery-next');
        var total = slides.length;
        var current = 0;

        function setActive(index) {
            current = (index + total) % total;
            slides.forEach(function (slide, i) {
                slide.classList.toggle('is-active', i === current);
            });
            thumbs.forEach(function (thumb, i) {
                thumb.classList.toggle('is-active', i === current);
            });
            if (counter) {
                counter.textContent = (current + 1) + ' / ' + total;
            }
            if (downloadBtn) {
                downloadBtn.setAttribute('href', slides[current].getAttribute('href'));
            }
        }

        thumbs.forEach(function (thumb, i) {
            thumb.addEventListener('click', function () {
                setActive(i);
            });
        });
        if (prevBtn) {
            prevBtn.addEventListener('click', function () {
                setActive(current - 1);
            });
        }
        if (nextBtn) {
            nextBtn.addEventListener('click', function () {
                setActive(current + 1);
            });
        }
        if (expandBtn) {
            expandBtn.addEventListener('click', function () {
                var items = slides.map(function (slide) {
                    return {src: slide.getAttribute('href'), type: 'image'};
                });
                Fancybox.show(items, {startIndex: current, infinite: false});
            });
        }
    })();
</script>
@endpush

@endsection
