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
        @if (session('success'))
            <div class="row mb-2">
                <div class="alert alert-success col-md-12" role="alert">
                    <button type="button" class="close" style="margin-bottom: 0;" data-dismiss="alert" area-hidden="true">x</button>
                    <h4><i class="icon fa fa-check"></i>{{ session('success') }}</h4>
                </div>
            </div>
        @endif
    </div><!-- /.container-fluid -->
</div>
<!-- /.content-header -->

<!-- Main content -->
<section class="content">
    <div class="container-fluid">
        <div class="card card-primary">

            <!-- form start -->
            <form action="{{ route('posts.update', [$post->id]) }}" method="POST" enctype="multipart/form-data" id="creationform" name="creationform">
                @csrf
                <div class="card-body">
                    <div class="form-group">
                        <label for="title">Название поста</label>
                        <input type="text" class="form-control" name="title" id="title" value="{{ $post->title }}" placeholder="Введите название категории ..." required>
                    </div>
                    <div class="form-group">
                        <label for="alias">Alias поста</label>
                        <input type="text" class="form-control" name="alias" id="alias" value="{{ $post->alias }}" placeholder="Введите alias для категории ..." required>
                    </div>
                    <div class="form-group">
                        <label>Выберете категорию</label>
                        <select class="form-control" name="category_id" id="category_id">
                            @foreach($categories as $category)
                                <option value="{{ $category->id }}">{{ $category->title }}</option>
                            @endforeach
                        </select>
                    </div>

{{--                    <div class="b-upload-images">--}}
{{--                        <a class="js-b-popup-1-open b-popup-1-open" href="#">Загрузить фото или видео</a>--}}
{{--                        <div class="js-images-block images">--}}
{{--                        </div>--}}
{{--                        <input type="file" name="images[]" multiple="multiple" accept="image/x-png,image/jpeg">--}}
{{--                    </div>--}}

{{--                    <input type="file" name="images[]" multiple="multiple" accept="image/x-png,image/jpeg">--}}

                    <div class="upload-image-section">
                        <section class="js-upload-image-section" data-action="{{ route('image.upload-to-temp-directory') }}">
                            <div class="js-images images"></div>
                            <div class="title">
                                <figure></figure>
                                <p>Перетащите сюда фото или видео</p>
                            </div>
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

                    <div class="b-add-tag">
                        <div class="input-group input-group-sm add-tag">
                            <input id="search-input-2" type="text" class="form-control" data-action="{{ route('search.hashtag') }}">
                            <span class="input-group-append">
                                <button type="button" class="btn btn-info btn-flat" id="add-tag" data-action="{{ route('hashtags.store') }}">Добавить тег</button>
                            </span>
                        </div>
                        <ul id="b-search__results-2" class="b-search__results-2"></ul>
{{--                        <div id="found-hashtags" class="found-hashtags"></div>--}}

                        <ul id="b-selected-tags-2" class="b-selected-tags-2"></ul>
                    </div>


                    <div class="form-group">
                        <textarea id="editor" name="content" placeholder="Введите текст поста ...">{{ $post->content }}</textarea>
                    </div>

                </div>
                <!-- /.card-body -->
                <div class="card-footer">
                    <button id="submit-creation-form" type="submit" class="btn btn-primary">Добавить</button>
                </div>
            </form>

        </div>
    </div><!-- /.container-fluid -->
</section>
<!-- /.content -->
@endsection
