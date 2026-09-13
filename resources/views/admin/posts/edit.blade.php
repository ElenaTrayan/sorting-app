@extends('layouts.admin_layout')

@section('title', 'Редактировать пост')

@section('content')
<!-- Content Header (Page header) -->
<div class="content-header">
    <div class="container-fluid">
        <div class="row mb-2">
            <div class="col-sm-6">
                <h1 class="m-0">Редактировать пост</h1>
            </div><!-- /.col -->
            <div class="col-sm-6">
                <ol class="breadcrumb float-sm-right">
                    <li class="breadcrumb-item"><a href="{{ route('homeAdmin') }}">Главная</a></li>
                    <li class="breadcrumb-item"><a href="{{ route('posts.index') }}">Все посты</a></li>
                    <li class="breadcrumb-item active">Редактировать пост</li>
                </ol>
            </div><!-- /.col -->
        </div><!-- /.row -->
    </div><!-- /.container-fluid -->
</div>
<!-- /.content-header -->

<!-- Main content -->
<section class="content">
    <div class="container-fluid">
        <div class="card card-primary" data-id="{{ $post->id }}">

            <!-- form start -->
            <form action="{{ route('posts.update', [$post->id]) }}" method="POST" enctype="multipart/form-data" id="editform" name="editform">
                @csrf
                @method('PUT')
                <div class="card-body">
                    <div class="card-body-block">
                        <div class="form-group">
                            <label for="title">Название поста</label>
                            <input type="text" class="form-control" name="title" id="title" value="{{ $post->title }}" placeholder="Введите название поста">
                        </div>
                        <div class="form-group">
                            <label for="alias">Alias поста</label>
                            <input type="text" class="form-control" name="alias" id="alias" value="{{ $post->alias }}" placeholder="Введите alias для поста" required>
                        </div>
                        <div class="form-group">
                            <label for="category_id">Выберете категорию</label>
                            <select class="form-control" name="category_id" id="category_id">
                                <option value="" {{ empty($post->category_id) ? 'selected' : '' }}>-</option>
                                @foreach($categories as $category)

                                    <option style="font-weight: 600;" value="{{ $category->id }}" {{ $post->category_id === $category->id ? 'selected' : '' }}>{{ $category->title }}</option>
                                    @if (!empty($category->children))
                                        @foreach($category->children as $childrenCategory)
                                            <option value="{{ $childrenCategory->id }}" {{ $post->category_id === $childrenCategory->id ? 'selected' : '' }}> - {{ $childrenCategory->title }}</option>
                                        @endforeach
                                    @endif

                                @endforeach
                            </select>
                        </div>



                        <div class="b-add-tag">
                            <div class="input-group input-group-sm add-tag">
                                <input id="search-input-2" type="text" class="form-control" data-action="{{ route('search.hashtag') }}">
                                <span class="input-group-append">
                                    <button type="button" class="btn btn-info btn-flat" id="add-tag" data-action="{{ route('hashtags.store') }}">Добавить тег</button>
                                </span>
                            </div>
                            <ul id="b-search__results-2" class="b-search__results-2"></ul>
    {{--                        <div id="found-hashtags" class="found-hashtags"></div>--}}

                            <ul id="b-selected-tags-2" class="b-selected-tags-2">
    {{--                            <li>--}}
                                @if (!empty($post->hashtags))
                                    @foreach($post->hashtags as $hashtag)
                                        <span class="tag" data-id="{{ $hashtag->id }}" data-name="{{ $hashtag->title }}">#{{ $hashtag->title }}<span class="icon font-icon fas close"></span></span>
                                    @endforeach
                                @endif
    {{--                            </li>--}}
                            </ul>
                        </div>

                    </div>

                    <div class="card-body-block">

                        <div class="b-post-images" id="post-images"
                             data-post-id="{{ $post->id }}"
                             data-add-action="{{ route('posts.images.add', $post->id) }}"
                             data-rename-action="{{ route('posts.images.rename', $post->id) }}"
                             data-delete-action="{{ route('posts.images.delete') }}"
                             data-confirm-replace-action="{{ route('posts.images.confirm-replace-duplicate', $post->id) }}"
                             data-confirm-add-action="{{ route('posts.images.confirm-add-duplicate', $post->id) }}">
                            <label>Изображения поста</label>

                            <div class="b-post-images__list js-post-images__list">
                                @foreach ($postImages as $name => $image)
                                    <div class="b-post-images__item" data-name="{{ $name }}" data-extension="{{ $image['extension'] }}" data-path="{{ $image['path'] }}">
                                        <a class="b-post-images__thumb" data-fancybox="post-images" data-src="/storage{{ $image['path'] }}" style="background-image: url(/storage{{ $image['path'] }})"></a>
                                        <div class="b-post-images__controls">
                                            <div class="b-post-images__rename">
                                                <input type="text" class="form-control form-control-sm js-post-images__name-input" value="{{ $name }}">
                                                <button type="button" class="btn btn-sm btn-outline-info js-post-images__rename" title="Переименовать">
                                                    <i class="fas fa-check"></i>
                                                </button>
                                            </div>
                                            <div class="b-post-images__buttons">
                                                <button type="button" class="btn btn-sm btn-outline-danger js-post-images__delete" title="Удалить">
                                                    <i class="fas fa-trash"></i>
                                                </button>
                                            </div>
                                        </div>
                                    </div>
                                @endforeach
                            </div>

                            <label class="btn btn-outline-info js-post-images__add-label">
                                <i class="fas fa-plus mr-1"></i> Добавить изображение
                                <input type="file" class="js-post-images__add-input" accept="image/*" hidden>
                            </label>

                            <div class="js-post-images__error text-danger" style="display: none;"></div>
                        </div>

                        <div class="b-post-images__video" id="post-video"
                             data-add-action="{{ route('posts.video.add', $post->id) }}"
                             data-delete-action="{{ route('posts.video.delete', $post->id) }}">
                            <label>Видео поста</label>

                            <div class="js-post-video__player">
                                @if ($post->video_url)
                                    <video src="{{ $post->video_url }}" controls preload="metadata"></video>
                                @endif
                            </div>

                            <label class="btn btn-outline-info js-post-video__add-label">
                                <i class="fas fa-video mr-1"></i>
                                <span class="js-post-video__add-label-text">{{ $post->video_url ? 'Заменить видео' : 'Добавить видео' }}</span>
                                <input type="file" class="js-post-video__add-input" accept="video/*" hidden>
                            </label>
                            <button type="button" class="btn btn-outline-danger js-post-video__delete" style="{{ $post->video_url ? '' : 'display: none;' }}">
                                <i class="fas fa-trash mr-1"></i> Удалить видео
                            </button>

                            <div class="js-post-video__error text-danger" style="display: none;"></div>
                        </div>

                        <div class="modal fade" id="modal-duplicate-image-choice" tabindex="-1" role="dialog" aria-hidden="true">
                            <div class="modal-dialog modal-dialog-centered modal-lg" role="document">
                                <div class="modal-content">
                                    <div class="modal-header">
                                        <h5 class="modal-title">Похожее изображение уже есть в другом посте</h5>
                                    </div>
                                    <div class="modal-body">
                                        <p class="js-duplicate-choice__message"></p>
                                        <div class="b-duplicate-choice__compare">
                                            <div class="b-duplicate-choice__option">
                                                <div class="b-duplicate-choice__label">Новое</div>
                                                <img class="js-duplicate-choice__new-thumb b-duplicate-choice__thumb" src="" alt="">
                                                <div class="js-duplicate-choice__new-size text-muted"></div>
                                            </div>
                                            <div class="b-duplicate-choice__option">
                                                <div class="b-duplicate-choice__label">Уже есть в посте #<span class="js-duplicate-choice__post-id"></span></div>
                                                <img class="js-duplicate-choice__old-thumb b-duplicate-choice__thumb" src="" alt="">
                                                <div class="js-duplicate-choice__old-size text-muted"></div>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="modal-footer">
                                        <button type="button" class="btn btn-primary js-duplicate-choice__confirm-replace">Заменить: перенести сюда, удалить из поста #<span class="js-duplicate-choice__post-id-2"></span></button>
                                        <button type="button" class="btn btn-primary js-duplicate-choice__confirm-add">Это разные картинки — всё равно добавить</button>
                                        <button type="button" class="btn btn-secondary" data-dismiss="modal">Отмена, не добавлять</button>
                                    </div>
                                </div>
                            </div>
                        </div>

                    </div>

                </div>
                <!-- /.card-body -->
                <div class="card-footer">
                    <div class="form-group">
                        <label for="content">...</label>
                        <textarea id="editor" name="content" placeholder="Введите текст поста ...">{{ $post->content }}</textarea>
                    </div>

                    <button id="submit-edit-form" type="submit" class="btn btn-primary">Сохранить изменения</button>
                    <a href="{{ route('posts.index') }}" class="btn btn-primary">Отменить</a>
                </div>
            </form>

        </div>
    </div><!-- /.container-fluid -->
</section>
<!-- /.content -->

@push('footer-scripts')
    <script>
        // просмотр изображений поста в полном размере (в том числе добавленных динамически) —
        // Fancybox подключается в самом низу layout'а, поэтому инициализация идёт через
        // стек footer-scripts, а не инлайн-скриптом прямо в теле страницы
        Fancybox.bind('[data-fancybox="post-images"]', {
            infinite: false
        });
    </script>
@endpush

@endsection
