@extends('layouts.admin_layout')

@section('title', 'Редактирование категории')

@section('content')
    <!-- Content Header (Page header) -->
    <div class="content-header">
        <div class="container-fluid">
            <div class="row mb-2">
                <div class="col-sm-6">
                    <h1 class="m-0">Редактирование категории: {{ $category->title }}</h1>
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
                <form action="{{ route('posts-categories.update', $category->id) }}" method="POST">
                    @csrf
                    @method('PUT')
                    <div class="card-body">
                        <div class="form-group">
                            <label for="title">Название категории</label>
                            <input type="text" class="form-control" name="title" id="title" value="{{ $category->title }}" required>
                        </div>
                        <div class="form-group">
                            <label for="alias">Alias категории</label>
                            <input type="text" class="form-control" name="alias" id="alias" value="{{ $category->alias }}" required>
                        </div>
                        <div class="form-group">
                            <label for="parent_id">Категория-родитель</label>
                            <input type="text" class="form-control" name="parent_id" id="parent_id" value="{{ $category->parent_id == 0 ? '-' : $category->parent_id }}" placeholder="{{ $category->parent_id == 0 ? '-' : '' }}">
                        </div>
                        <div class="form-group">
                            <label for="short_description">Описание категории</label>
                            <textarea class="form-control" rows="3" name="short_description" id="short_description" value="{{ $category->short_description }}"></textarea>
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
