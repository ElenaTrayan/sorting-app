@extends('layouts.user_layout')

@section('title', 'Группа постов')

@push('navbar-breadcrumb')
    <li class="nav-item d-flex align-items-center">
        <ol class="breadcrumb b-navbar-breadcrumb">
            <li class="breadcrumb-item"><a href="{{ route('cabinet.home') }}">Главная</a></li>
            <li class="breadcrumb-item"><a href="{{ route('posts.index') }}">Все посты</a></li>
            <li class="breadcrumb-item active">Группа постов</li>
        </ol>
    </li>
@endpush

@push('head-styles')
    {{-- Manrope — шрифт макета Claude Design, тот же, что и на остальных переверстанных страницах
         (самостоятельно захостен в /admin/fonts/manrope) --}}
    <link rel="stylesheet" href="/admin/fonts/manrope/manrope.css">
@endpush

@section('content')
{{-- хлебные крошки для этой страницы вынесены в верхний navbar-nav-left, см.
     resources/views/layouts/user_layout.blade.php --}}
<div class="content-header content-header--post-show">
    <div class="container-fluid">
        <div class="b-post-show-header">
            <a href="{{ route('posts.index') }}" class="b-post-show__back">
                <i class="fas fa-arrow-left"></i> Назад к постам
            </a>
        </div>
    </div>
</div>

<section class="content">
    <div class="container-fluid">
        <div class="b-post-group" id="post-group" data-reorder-action="{{ route('posts.groups.reorder', $groupId) }}">

            <div class="b-post-group__intro">
                <h1 class="b-post-group__title">Порядок постов в группе</h1>
                <p class="b-post-group__hint">Перетаскивайте карточки за ручку слева, чтобы изменить порядок — сохраняется автоматически.</p>
            </div>

            <ul class="b-post-group__list js-post-group-list">
                @foreach ($posts as $post)
                    <li class="b-post-group__item" data-id="{{ $post->id }}">
                        <span class="b-post-group__handle" title="Перетащить"><i class="fas fa-grip-vertical"></i></span>
                        <div class="b-post-group__position js-post-group-position">{{ $loop->iteration }}</div>
                        <a href="{{ route('posts.show', $post->id) }}" class="b-post-group__thumb" @if ($post->cover_image) style="background-image: url('/storage{{ $post->cover_image }}')" @endif>
                            @if (!$post->cover_image)
                                <i class="fas fa-image"></i>
                            @endif
                        </a>
                        <div class="b-post-group__body">
                            <a href="{{ route('posts.show', $post->id) }}" class="b-post-group__link">{{ $post->title ?: 'Без названия' }}</a>
                            <div class="b-post-group__date"><i class="far fa-calendar-alt"></i> {{ $post->created_at->format('d.m.Y, H:i') }}</div>
                        </div>
                    </li>
                @endforeach
            </ul>
        </div>
    </div>
</section>

@push('footer-scripts')
<script>
    (function () {
        var group = document.getElementById('post-group');
        var list = document.querySelector('.js-post-group-list');
        if (!group || !list || typeof $ === 'undefined' || !$.fn.sortable) {
            return;
        }

        var reorderUrl = group.getAttribute('data-reorder-action');

        $(list).sortable({
            handle: '.b-post-group__handle',
            axis: 'y',
            tolerance: 'pointer',
            placeholder: 'ui-sortable-placeholder',
            forcePlaceholderSize: true,
            update: function () {
                var order = $(list).children().map(function () {
                    return $(this).data('id');
                }).get();

                $(list).find('.js-post-group-position').each(function (index) {
                    $(this).text(index + 1);
                });

                $.ajax({
                    type: 'post',
                    url: reorderUrl,
                    headers: {'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')},
                    data: {order: order},
                    success: function (response) {
                        if (response && response.status) {
                            toastr.success(response.message || 'Порядок сохранён');
                        } else {
                            toastr.error((response && response.message) || 'Не удалось сохранить порядок');
                        }
                    },
                    error: function () {
                        toastr.error('Не удалось сохранить порядок');
                    }
                });
            }
        });
    })();
</script>
@endpush

@endsection
