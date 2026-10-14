@extends('layouts.user_layout')

@section('title', 'Корзина')

{{-- Корзина постов: "Удалить" у поста переносит его сюда (App\Components\PostTrash), отсюда пост можно
     вернуть или удалить навсегда; через $keepDays дней он удаляется сам (команда posts:purge-trash).
     Поведение — /admin/posts-trash.js, стили — .b-posts-trash* в admin-panel.css. --}}

@push('navbar-breadcrumb')
    <li class="nav-item d-flex align-items-center">
        <ol class="breadcrumb b-navbar-breadcrumb">
            <li class="breadcrumb-item"><a href="{{ route('cabinet.home') }}">Главная</a></li>
            <li class="breadcrumb-item"><a href="{{ route('posts.index') }}">Все посты</a></li>
            <li class="breadcrumb-item active">Корзина</li>
        </ol>
    </li>
@endpush

@push('head-styles')
    <link rel="stylesheet" href="/admin/fonts/manrope/manrope.css">
@endpush

@section('content')
<div class="content-header content-header--post-show">
    <div class="container-fluid">
        <div class="b-post-show-header">
            <a href="{{ route('posts.index') }}" class="b-post-show__back">
                <i class="fas fa-arrow-left"></i> Назад к постам
            </a>

            @if ($posts->total() > 0)
                <div class="b-post-show-header__actions">
                    <button type="button" class="b-post-show__btn js-posts-trash-restore-all" data-url="{{ route('posts.trash.restore-all') }}">
                        <i class="fas fa-undo"></i> Вернуть всё
                    </button>
                    <button type="button" class="b-post-show__btn b-post-show__btn--danger js-posts-trash-empty">
                        <i class="fas fa-trash-alt"></i> Очистить корзину
                    </button>
                </div>
            @endif
        </div>
    </div>
</div>

<section class="content">
    <div class="container-fluid">
        <div class="b-posts-trash" id="posts-trash" data-empty-action="{{ route('posts.trash.empty') }}">

            <div class="b-post-group__intro">
                <h1 class="b-post-group__title">Корзина</h1>
                <p class="b-post-group__hint">
                    Удалённые посты лежат здесь {{ $keepDays }} дней, потом удаляются навсегда вместе с файлами.
                    До этого пост можно вернуть — со всеми картинками, текстом и хештегами.
                </p>
            </div>

            @if ($posts->total() === 0)
                <div class="b-posts-trash__empty">
                    <i class="far fa-trash-alt"></i>
                    <p>В корзине пусто</p>
                </div>
            @else
                <div class="b-posts-trash__grid js-posts-trash-list">
                    @foreach ($posts as $post)
                        @php
                            $trashText = trim(html_entity_decode(strip_tags((string) $post->content), ENT_QUOTES | ENT_HTML5, 'UTF-8'));
                            // несколько постов одной группы в корзине — одна плитка-стопка (PostController::trash()):
                            // вернуть и удалить навсегда можно только всю стопку сразу
                            $isStack = $post->group_trashed > 1;
                        @endphp
                        <div class="b-posts-trash__item @if ($isStack) b-posts-trash__item--stack @endif js-posts-trash-item">
                            <div class="b-posts-trash__media">
                                @if ($post->video_url)
                                    <video src="{{ $post->video_url }}" preload="metadata" muted></video>
                                    <span class="b-film-materials__play"><i class="fas fa-play"></i></span>
                                @elseif ($post->cover_image)
                                    <img src="/storage{{ $post->cover_image }}" alt="" loading="lazy">
                                @else
                                    <span class="b-posts-trash__text">{{ \Illuminate\Support\Str::limit($post->title ?: $trashText ?: 'Без названия', 160) }}</span>
                                @endif
                                @if ($isStack)
                                    <span class="b-posts-trash__badge b-posts-trash__badge--count" title="Постов этой группы в корзине: {{ $post->group_trashed }}"><i class="fas fa-layer-group"></i> {{ $post->group_trashed }}</span>
                                @elseif ($post->group_id)
                                    <span class="b-posts-trash__badge" title="Пост из группы — при восстановлении вернётся в неё"><i class="fas fa-layer-group"></i></span>
                                @endif
                            </div>
                            <div class="b-posts-trash__body">
                                @if ($post->title && ($post->cover_image || $post->video_url))
                                    <div class="b-posts-trash__title">{{ $post->title }}</div>
                                @endif
                                <div class="b-posts-trash__meta">
                                    {{ $isStack ? 'Удалены' : 'Удалён' }} {{ $post->deleted_at->format('d.m.Y') }} ·
                                    <span class="@if ($post->days_left <= 3) b-posts-trash__soon @endif">
                                        {{ $post->days_left > 0 ? 'ещё ' . $post->days_left . ' дн.' : 'удалится сегодня' }}
                                    </span>
                                </div>
                                <div class="b-posts-trash__actions">
                                    @if ($isStack)
                                        <button type="button" class="b-posts-trash__btn b-posts-trash__btn--accent js-posts-trash-restore" data-url="{{ route('posts.trash.restore-group', $post->group_id) }}"
                                                title="Вернуть все посты этой группы, которые лежат в корзине">
                                            <i class="fas fa-undo"></i> Вернуть группу
                                        </button>
                                        <button type="button" class="b-posts-trash__btn b-posts-trash__btn--danger js-posts-trash-purge" data-url="{{ route('posts.trash.purge-group', $post->group_id) }}"
                                                data-count="{{ $post->group_trashed }}" title="Удалить группу навсегда">
                                            <i class="fas fa-times"></i>
                                        </button>
                                    @else
                                        <button type="button" class="b-posts-trash__btn b-posts-trash__btn--accent js-posts-trash-restore" data-url="{{ route('posts.trash.restore', $post->id) }}">
                                            <i class="fas fa-undo"></i> Вернуть
                                        </button>
                                        <button type="button" class="b-posts-trash__btn b-posts-trash__btn--danger js-posts-trash-purge" data-url="{{ route('posts.trash.purge', $post->id) }}" title="Удалить навсегда">
                                            <i class="fas fa-times"></i>
                                        </button>
                                    @endif
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>

                @if ($posts->hasPages())
                    <div class="b-posts-trash__pages">
                        @if ($posts->onFirstPage())
                            <span class="b-post-show__btn is-disabled"><i class="fas fa-chevron-left"></i> Назад</span>
                        @else
                            <a href="{{ $posts->previousPageUrl() }}" class="b-post-show__btn"><i class="fas fa-chevron-left"></i> Назад</a>
                        @endif
                        <span class="b-posts-trash__page">Страница {{ $posts->currentPage() }} из {{ $posts->lastPage() }}</span>
                        @if ($posts->hasMorePages())
                            <a href="{{ $posts->nextPageUrl() }}" class="b-post-show__btn">Вперёд <i class="fas fa-chevron-right"></i></a>
                        @else
                            <span class="b-post-show__btn is-disabled">Вперёд <i class="fas fa-chevron-right"></i></span>
                        @endif
                    </div>
                @endif
            @endif
        </div>
    </div>
</section>

{{-- подтверждение безвозвратного удаления: одного поста или всей корзины (текст подставляет posts-trash.js) --}}
<div class="modal fade" id="modal-posts-trash-confirm" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title js-posts-trash-confirm-title"></h5>
            </div>
            <div class="modal-body js-posts-trash-confirm-text"></div>
            <div class="modal-footer">
                <button type="button" class="btn btn-danger js-posts-trash-confirm">Удалить навсегда</button>
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Отмена</button>
            </div>
        </div>
    </div>
</div>

@push('footer-scripts')
    <script src="/admin/posts-trash.js?v={{ filemtime(public_path('admin/posts-trash.js')) }}"></script>
@endpush

@endsection
