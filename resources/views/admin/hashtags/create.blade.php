@extends('layouts.admin_layout')

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
        <div class="card card-primary">
            <!-- form start -->
            <form action="{{ route('hashtags.store') }}" method="POST">
                @csrf
                <div class="card-body">
                    <div class="card-body-block">
                        <div class="form-group">
                            <label for="title">Хештег</label>
                            <input type="text" class="form-control" name="title" id="title" placeholder="Введите название хештега ..." required>
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
                    </div>
                    <div class="card-body-block">

                    </div>
                </div>
                <!-- /.card-body -->
                <div class="card-footer">
                    <button type="submit" class="btn btn-primary">Добавить</button>
                </div>
            </form>
        </div>
    </div><!-- /.container-fluid -->
</section>
<!-- /.content -->
@endsection
