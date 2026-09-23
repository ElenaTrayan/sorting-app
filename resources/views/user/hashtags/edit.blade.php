@extends('layouts.user_layout')

@section('title', 'Редактирование хештега')

@section('content')
    <!-- Content Header (Page header) -->
    <div class="content-header">
        <div class="container-fluid">
            <div class="row mb-2">
                <div class="col-sm-6">
                    <h1 class="m-0">Редактирование хештега: {{ $hashtag->title }}</h1>
                </div><!-- /.col -->
                <div class="col-sm-6">
                    <ol class="breadcrumb float-sm-right">
                        <li class="breadcrumb-item"><a href="{{ route('cabinet.home') }}">Главная</a></li>
                        <li class="breadcrumb-item"><a href="{{ route('hashtags.index') }}">Все хештеги</a></li>
                        <li class="breadcrumb-item active">Редактирование хештега</li>
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
                <form action="{{ route('hashtags.update', $hashtag->id) }}" method="POST">
                    @csrf
                    @method('PUT')
                    <div class="card-body">
                        <div class="form-group">
                            <label for="title">Хештег</label>
                            <input type="text" class="form-control" name="title" id="title" value="{{ $hashtag->title }}" required>
                        </div>
                        <div class="form-group">
                            <label for="parent_id">Хештег-родитель</label>
                            <select class="form-control" name="parent_id" id="parent_id">
                                <option value="0">-</option>
                                @foreach($hashtags as $hashtagElement)
                                    @if ($hashtagElement->id !== $hashtag->id)
                                        <option style="font-weight: 600;" value="{{ $hashtagElement->id }}" {{ $hashtag->parent_id === $hashtagElement->id ? 'selected' : '' }}>{{ $hashtagElement->title }}</option>
                                        @if (!empty($hashtagElement->children))
                                            @foreach($hashtagElement->children as $childrenHashtag)
                                                <option value="{{ $childrenHashtag->id }}"> - {{ $childrenHashtag->title }}</option>
                                            @endforeach
                                        @endif
                                    @endif
                                @endforeach
                            </select>
                        </div>
                    </div>
                    <!-- /.card-body -->
                    <div class="card-footer">
                        <button type="submit" class="btn btn-primary">Сохранить изменения</button>
                    </div>
                </form>

                <div class="card-body b-hashtags-edit__related">
                    <div class="form-group">
                        <label for="related-hashtags-input">Связанные хэштеги</label>
                        <div class="b-hashtags-edit__related-field js-hashtag-related-field"
                             data-hashtag-id="{{ $hashtag->id }}"
                             data-search-action="{{ route('search.hashtag') }}"
                             data-attach-action="{{ route('hashtags.related.attach', $hashtag->id) }}">
                            <div class="b-tags">
                                <div class="b-tags__container" id="related-hashtags-container">
                                    @foreach ($relatedHashtags as $relatedHashtag)
                                        <div class="b-tags__item" data-id="{{ $relatedHashtag->id }}" data-name="{{ $relatedHashtag->title }}">
                                            {{ $relatedHashtag->title }}
                                            <span class="close">&times;</span>
                                        </div>
                                    @endforeach
                                </div>
                                <input type="text" class="b-tags__input" id="related-hashtags-input" placeholder="Введите название хештега...">
                            </div>
                            <ul class="b-tags__dropdown" id="related-hashtags-dropdown"></ul>
                        </div>
                        <small class="form-text text-muted">Связь двусторонняя — добавленный хештег тоже будет ссылаться на этот.</small>
                    </div>
                </div>
            </div>
        </div><!-- /.container-fluid -->
    </section>
    <!-- /.content -->
@endsection
