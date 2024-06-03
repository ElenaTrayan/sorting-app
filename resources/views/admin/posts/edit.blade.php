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
                    <li class="breadcrumb-item"><a href="#">Home</a></li>
                    <li class="breadcrumb-item active">Dashboard v1</li>
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
                                        <span class="tag" data-id="{{ $hashtag->id }}" data-name="{{ $hashtag->title }}">#{{ $hashtag->title }}<span class="icon font-icon fas close"></span></span>
                                    @endforeach
                                @endif
    {{--                            </li>--}}
                            </ul>
                        </div>

                        @if (!empty($originalImage))
                            @if (isset($originalImage['path']))
                                <div class="form-group">
                                    <label for="image-name">Изменить имя файла</label>
                                    <input type="text" class="form-control" name="image-name" id="image-name" value="{{ $originalImage['name'] ?? '' }}" placeholder="Введите имя для файла" required>
                                    <p>{{ $originalImage['name'] ?? '' . '.' . $originalImage['extension'] ?? '' }}</p>
                                    <p>{{ $mediumImage['name'] ?? '' }}</p>
                                    <p>{{ $smallImage['name'] ?? '' }}</p>
                                </div>
                            @else
                                @foreach($originalImage as $image)
                                    <div class="form-group">
                                        <label for="image-name">Изменить имя файла</label>
                                        <input type="text" class="form-control" name="image-name" id="image-name"
                                               value="{{ $image['name'] ?? '' }}"
                                               placeholder="Введите имя для файла" required>
                                        <p>{{ $image['name'] ?? '' . '.' . $image['extension'] ?? '' }}</p>
{{--                                        <p>{{ $mediumImage['name'] ?? '' }}</p>--}}
{{--                                        <p>{{ $smallImage['name'] ?? '' }}</p>--}}
                                    </div>
                                @endforeach
                            @endif
                        @endif

                    </div>

                    <div class="card-body-block">

                        <div class="upload-image-section">
                            <section class="js-upload-image-section" data-action="{{ route('image.upload-to-temp-directory') }}">
                                <div class="js-images images">
                                    @if (!empty($originalImage))
                                        @foreach($originalImage as $image)
                                            <div class="image saved"
                                                 data-name="{{ $image['name'] }}"
                                                 data-extension="{{ $image['extension'] }}"
                                                 data-path="{{ $image['path'] }}"
                                            >
                                                <span style="background-image: url(/storage/{{ $image['path'] }})"></span>
                                                <i class="js-delete-image saved fas fa-times-circle" data-action="/admin_panel/delete-post-file"></i>
                                            </div>
                                        @endforeach
                                    @endif
                                </div>
                                @if (empty($originalImage))
                                    <div class="title">
                                        <figure></figure>
                                        <p>Перетащите сюда фото или видео</p>
                                    </div>
                                @endif
                                <input type="file" name="files[]" multiple="multiple" accept="image/x-png,image/jpeg">
                            </section>
                            <div class="progress">
                                <div class="progress-bar"></div>
                                <div class="progress-value">0 %</div>
                            </div>
                            <div class="js-error-block error-block alert alert-warning alert-dismissible">
                                <button type="button" class="js-close-error-block close">×</button>
                                <i class="icon fas fa-exclamation-triangle"></i><p></p>
                            </div>
                        </div>

                        <div class="post-update-image">
{{--                            <div class="mb-3">--}}
{{--                                <label for="imagefile">Default file input</label>--}}
{{--                                <input type="file" id="imagefile" name="files[]" class="form-control" accept="image/png, image/jpeg">--}}
{{--                            </div>--}}

                            <div class="form-group">
                                <label for="exampleInputFile">File input</label>
                                <div class="input-group">
                                    <div class="custom-file">
                                        <input type="file" class="custom-file-input" id="exampleInputFile" data-action="{{ route('image.upload-to-temp-directory') }}" multiple="multiple">
                                        <label class="custom-file-label" for="exampleInputFile"></label>
                                    </div>
                                    <div class="input-group-append">
                                        <button type="button" class="btn btn-block btn-default" id="">Upload</button>
                                    </div>
                                </div>
                            </div>

                            @if (!empty($originalImage['path']))
                                <a class="post-image" id="js-post-image" data-fancybox="gallery" data-src="/storage{{ $originalImage['path'] }}">
                                    <img src="/storage{{ $mediumImage['path'] ?? $originalImage['path'] }}" />
                                </a>
                            @endif
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
@endsection
