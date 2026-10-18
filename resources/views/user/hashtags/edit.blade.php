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
                <form action="{{ route('hashtags.update', $hashtag->id) }}" method="POST" id="hashtags-edit">
                    @csrf
                    @method('PUT')
                    <div class="card-body">
                        <div class="form-group">
                            <label for="title">Хештег</label>
                            <input type="text" class="form-control @error('title') is-invalid @enderror" name="title" id="title" value="{{ old('title', $hashtag->title) }}"
                                   data-hashtag-id="{{ $hashtag->id }}"
                                   data-action="{{ route('search.hashtag-by-title') }}"
                                   data-edit-url="{{ route('hashtags.edit', '__ID__') }}"
                                   autocomplete="off" required>
                            <span class="error invalid-feedback @error('title') active @enderror">@error('title'){{ $message }}@enderror</span>
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
                             data-hashtag-title="{{ $hashtag->title }}"
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
                        <small class="form-text text-muted">Можно выбрать существующий хештег или ввести новый и нажать Enter — он будет создан. Связь двусторонняя — добавленный хештег тоже будет ссылаться на этот.</small>
                    </div>

                    {{-- направление связей (hashtag_relations.auto_add) — что делать на форме поста, когда выбрали
                         один из хештегов пары: добавить второй к посту сразу или только предложить.
                         Строки для новых связей добавляет admin.js (той же разметкой) --}}
                    <div class="b-hashtag-relations__wrap js-hashtag-relations-wrap" @if ($relatedHashtags->isEmpty()) hidden @endif>
                        <label class="mb-1">Направление связей</label>
                        <p class="b-hashtag-relations__hint">
                            Отмечено — при выборе хештега на форме поста связанный добавляется к посту сам,
                            не отмечено — только предлагается в подсказках.
                            Обычно общий хештег добавляют к конкретному («Самара Уивинг» → «актриса»), а не наоборот.
                        </p>
                        <ul class="b-hashtag-relations js-hashtag-relations" data-hashtag-title="{{ $hashtag->title }}">
                            @foreach ($relatedHashtags as $relatedHashtag)
                                <li class="b-hashtag-relations__item" data-id="{{ $relatedHashtag->id }}">
                                    <div class="b-hashtag-relations__title">#{{ $relatedHashtag->title }}</div>
                                    <label class="b-hashtag-relations__switch">
                                        <input type="checkbox" class="js-hashtag-relation-auto" data-direction="forward" {{ $relatedHashtag->pivot->auto_add ? 'checked' : '' }}>
                                        <span>выбрали «{{ $hashtag->title }}» → добавлять «{{ $relatedHashtag->title }}»</span>
                                    </label>
                                    <label class="b-hashtag-relations__switch">
                                        <input type="checkbox" class="js-hashtag-relation-auto" data-direction="back" {{ !empty($autoAddBack[$relatedHashtag->id]) ? 'checked' : '' }}>
                                        <span>выбрали «{{ $relatedHashtag->title }}» → добавлять «{{ $hashtag->title }}»</span>
                                    </label>
                                </li>
                            @endforeach
                        </ul>
                    </div>
                </div>
            </div>
        </div><!-- /.container-fluid -->
    </section>
    <!-- /.content -->
@endsection
