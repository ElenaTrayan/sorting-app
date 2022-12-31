@extends('layouts.admin_layout')

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
                        <div class="form-group">
                            <label for="short_description">Cвязанные хэштеги</label>
                            <div>
                                @if (!empty($associated_hashtags))
                                    @foreach($associated_hashtags as $associated_hashtag)
                                        <span class="tag" data-id="{{ $associated_hashtag->id }}" data-name="">{{ $associated_hashtag->title }}<span class="icon font-icon fas close"></span></span>
                                    @endforeach
                                @endif
                            </div>
                            <input type="text" class="form-control" name="associated_hashtags" id="associated_hashtags" value="" placeholder="">
                        </div>
                    </div>
                    <!-- /.card-body -->
                    <div class="card-footer">
                        <button type="submit" class="btn btn-primary">Сохранить изменения</button>
                    </div>
                </form>
            </div>
        </div><!-- /.container-fluid -->
    </section>
    <!-- /.content -->
@endsection
