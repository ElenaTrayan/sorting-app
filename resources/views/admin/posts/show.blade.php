@extends('layouts.admin_layout')

@section('title', 'Посмотреть пост')

@section('content')
<!-- Content Header (Page header) -->
<div class="content-header">
    <div class="container-fluid">
        <div class="row mb-2">
            <div class="col-sm-6">
                <h1 class="m-0">Посмотреть пост</h1>
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
        <div class="card card-primary" data-id="{{ $post->id }}">

            <div class="bf-breadcrumb-container">
                <ul class="bf-breadcrumb-items">
                    <li class="bf-breadcrumb-item bf-breadcrumb-begin">
                        <a href=""><span>Главная</span></a>
                    </li>
                    <li class="bf-breadcrumb-item">
                        <a href=""><span>Разное</span></a>
                    </li>
                    <li class="bf-breadcrumb-item bf-breadcrumb-end">
                        <span>Топ-3 бесплатных редакторов форматированного текста для веб-приложений</span>
                    </li>
                </ul>
            </div>

            <div class="card-body" style="display: flex;">

                @if (!empty($post->content) && !empty($originalImage))
                    <div style="width: 50%;margin: 0 15px 0 0;">
                        <div class="title">
                            <h3 class="my-3">{{ $post->title }}</h3>
                            <span class="edit"><i class="nav-icon fas fa-edit"></i></span>
                            <button type="button" aria-label="Copy code to clipboard" class="copyButton_wuS7 clean-btn">
                                Copy
                            </button>
                        </div>

                        {!! $post->content !!}
                    </div>

                    <div style="width: 50%;">
                        @if (isset($originalImage['path']))
                            <a class="post-image" data-fancybox="gallery" data-src="/storage{{ $originalImage['path'] ?? '' }}">
                                <img src="/storage{{ $mediumImage['path'] ?? $originalImage['path'] ?? '' }}" style="width: 100%;"/>
                            </a>
                        @else
                            @foreach($originalImage as $key => $image)
                                <a class="post-image" data-fancybox="gallery" data-src="/storage{{ $image['path'] ?? '' }}">
                                    <img src="/storage{{ $mediumImage[$key]['path'] ?? $image['path'] ?? '' }}" style="width: 100%;"/>
                                </a>
                            @endforeach
                        @endif
                    </div>

                @elseif(!empty($post->content))
                    <div style="width: 100%; margin: 0 15px 0 0;">
                        <div class="title">
                            <h3 class="my-3">{{ $post->title }}</h3>
                            <span class="edit"><i class="nav-icon fas fa-edit"></i></span>
                            <button type="button" aria-label="Copy code to clipboard" class="copyButton_wuS7 clean-btn">
                                Copy
                            </button>
                        </div>

                        {!! $post->content !!}
                    </div>
                @elseif(!empty($originalImage))
                    <div style="max-width: 800px;">
                        @if (isset($originalImage['path']))
                            <a class="post-image" data-fancybox="gallery" data-src="/storage{{ $originalImage['path'] ?? '' }}">
                                <img src="/storage{{ $mediumImage['path'] ?? $originalImage['path'] ?? '' }}" style="width: auto;"/>
                            </a>
                        @else
                            @foreach($originalImage as $key => $image)
                                <a class="post-image" data-fancybox="gallery" data-src="/storage{{ $image['path'] ?? '' }}">
                                    <img src="/storage{{ $mediumImage[$key]['path'] ?? $image['path'] ?? '' }}" style="width: auto;"/>
                                </a>
                            @endforeach
                        @endif
                    </div>
                @endif

{{--                    <div class="form-group">--}}
{{--                        <label>Выберете категорию</label>--}}
{{--                        <select class="form-control" name="category_id" id="category_id">--}}
{{--                            @foreach($categories as $category)--}}
{{--                                <option value="{{ $category->id }}">{{ $category->title }}</option>--}}
{{--                            @endforeach--}}
{{--                        </select>--}}
{{--                    </div>--}}

{{--                    <div class="b-add-tag">--}}
{{--                        <ul id="b-selected-tags-2" class="b-selected-tags-2 view-show">--}}
{{--                            @foreach($post->hashtags as $hashtag)--}}
{{--                                <li><span class="tag" data-id="{{ $hashtag->id }}" data-name="{{ $hashtag->title }}">#{{ $hashtag->title }}<span class="icon font-icon fas close hide"></span></span></li>--}}
{{--                            @endforeach--}}
{{--                        </ul>--}}
{{--                    </div>--}}
{{--                --}}
{{--                    <div class="form-group">--}}
{{--                        <p></p>--}}
{{--                    </div>--}}

                </div>
                <!-- /.card-body -->
                <div class="card-footer">
                    <button id="submit-creation-form" type="submit" class="btn btn-primary">Добавить</button>
                </div>


        </div>
    </div><!-- /.container-fluid -->
</section>
<!-- /.content -->

<script>
    // Customization example
    Fancybox.bind('[data-fancybox="gallery"]', {
        infinite: false
    });
</script>

@endsection
