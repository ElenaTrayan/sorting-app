@extends('layouts.admin_layout')

@section('title', 'Добавить категорию')

@section('content')
<!-- Content Header (Page header) -->
<div class="content-header">
    <div class="container-fluid">
        <div class="row mb-2">
            <div class="col-sm-6">
                <h1 class="m-0">Добавить категорию</h1>
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
    <div class="container-fluid posts-categories-create" id="posts-categories-create">
        <div class="card card-primary card-create">
            <!-- form start -->
            <form action="{{ route('posts-categories.store') }}" method="POST" id="creationform2">
                @csrf
                <div class="card-body">
                    <div class="card-body-block">
                        <div class="form-group">
                            <label for="title">Название категории</label>
                            <input type="text" class="form-control" name="title" id="title" placeholder="Введите название категории" required>
                        </div>
                        <div class="form-group">
                            <label for="alias">Alias категории</label>
                            <input type="text" class="form-control" name="alias" id="alias" placeholder="Введите alias для категории" required>
                        </div>
                    </div>
                    <div class="card-body-block">
                        <div class="form-group">
                            <label>Выберете категорию-родителя</label>
                            <select class="form-control" name="parent_id" id="parent_id">
                                <option value="0">-</option>
                                @foreach($categories as $category)
                                    <option style="font-weight: 600;" value="{{ $category->id }}">{{ $category->title }}</option>
                                    @if (!empty($category->children))
                                        @foreach($category->children as $childrenCategory)
                                        <option value="{{ $childrenCategory->id }}"> - {{ $childrenCategory->title }}</option>
                                        @endforeach
                                    @endif
                                @endforeach
                            </select>
                        </div>
                        <div class="form-group">
                            <label for="short_description">Описание категории</label>
                            <textarea class="form-control" rows="3" name="short_description" id="short_description" placeholder="Введите описание для категории"></textarea>
                        </div>
                    </div>
                </div>
                <!-- /.card-body -->
                <div class="card-footer">
                    <button id="submit-creation-form2" type="submit" class="btn btn-primary">Добавить</button>
                </div>
            </form>
        </div>
    </div><!-- /.container-fluid -->
</section>
<!-- /.content -->
@endsection
