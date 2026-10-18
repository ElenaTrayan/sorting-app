@extends('layouts.user_layout')

@section('title', 'Добавить хештег')

@section('content')
<!-- Content Header (Page header) -->
<div class="content-header">
    <div class="container-fluid">
        <div class="row mb-2">
            <div class="col-sm-6">
                <h1 class="m-0">Добавить хештег</h1>
            </div><!-- /.col -->
            <div class="col-sm-6">
                <ol class="breadcrumb float-sm-right">
                    <li class="breadcrumb-item"><a href="{{ route('cabinet.home') }}">Главная</a></li>
                    <li class="breadcrumb-item"><a href="{{ route('hashtags.index') }}">Все хештеги</a></li>
                    <li class="breadcrumb-item active">Добавить хештег</li>
                </ol>
            </div><!-- /.col -->
        </div><!-- /.row -->
    </div><!-- /.container-fluid -->
</div>
<!-- /.content-header -->

<!-- Main content -->
<section class="content">
    <div class="container-fluid">
        <div class="card card-primary hashtags-create" id="hashtags-create">
            <!-- form start -->
            <form action="{{ route('hashtags.store') }}" method="POST">
                @csrf
                <div class="card-body">
                    <div class="card-body-block">
                        <div class="form-group">
                            <label for="title">Хештег</label>
                            <input type="text" class="form-control" name="title" id="title" placeholder="Введите название хештега" data-action="{{ route('search.hashtag-by-title') }}" data-edit-url="{{ route('hashtags.edit', '__ID__') }}" autocomplete="off" required>
                            <span class="error invalid-feedback"></span>
                        </div>

                        <div class="form-group">
                            <label>Хештег-родитель</label>
                            <select class="form-control" name="parent_id" id="parent_id">
                                <option value="0">-</option>
                                @foreach($hashtags as $hashtag)
                                    <option value="{{ $hashtag->id }}">{{ $hashtag->title }}</option>
                                @endforeach
                            </select>
                        </div>

                        <div class="form-group">
                            <label for="related-hashtags-input">Связанные хэштеги</label>
                            <div class="b-hashtags-edit__related-field js-hashtag-related-picker"
                                 data-search-action="{{ route('search.hashtag') }}">
                                <div class="b-tags">
                                    <div class="b-tags__container" id="related-hashtags-container"></div>
                                    <input type="text" class="b-tags__input" id="related-hashtags-input" placeholder="Введите название хештега...">
                                </div>
                                <ul class="b-tags__dropdown" id="related-hashtags-dropdown"></ul>
                            </div>
                            <small class="form-text text-muted">Можно выбрать существующий хештег или ввести новый и нажать Enter — он будет создан. Связь двусторонняя — добавленный хештег тоже будет ссылаться на этот. Сохранится вместе с хештегом по кнопке «Добавить».</small>
                        </div>
                    </div>
                    <div class="card-body-block">

                    </div>
                </div>
                <!-- /.card-body -->
                <div class="card-footer">
                    <button type="submit" class="btn btn-primary" id="btn-submit-form">Добавить</button>
                </div>
            </form>
        </div>
    </div><!-- /.container-fluid -->
</section>
<!-- /.content -->
@endsection
