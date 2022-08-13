@extends('layouts.admin_layout')

@section('title', 'Добавить категорию')

@section('content')
<!-- Content Header (Page header) -->
<div class="content-header">
    <div class="container-fluid">
        <div class="row mb-2">
            <div class="col-sm-6">
                <h1 class="m-0">Добавить пост</h1>
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
            <form action="{{ route('posts.store') }}" method="POST" enctype="multipart/form-data" id="creationform">
                @csrf
                <div class="card-body">
                    <div class="form-group">
                        <label for="title">Название поста</label>
                        <input type="text" class="form-control" name="title" id="title" placeholder="Введите название категории ..." required>
                    </div>
                    <div class="form-group">
                        <label for="alias">Alias поста</label>
                        <input type="text" class="form-control" name="alias" id="alias" placeholder="Введите alias для категории ..." required>
                    </div>
                    <div class="form-group">
                        <label>Выберете категорию</label>
                        <select class="form-control" name="category_id" id="category_id">
                            @foreach($categories as $category)
                                <option value="{{ $category->id }}">{{ $category->title }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div class="b-upload-images">
                        <a class="js-b-popup-1-open b-popup-1-open" href="#">Загрузить фото или видео</a>
                        <div class="js-images-block images">
                        </div>
                        <input type="file" name="images[]" multiple="multiple" accept="image/x-png,image/jpeg">
                    </div>

                    <div class="form-group">
                        <textarea id="editor" name="content" placeholder="Введите текст поста ...">Hello, World!</textarea>
                    </div>

                </div>
                <!-- /.card-body -->
                <div class="card-footer">
                    <button type="submit" class="btn btn-primary">Добавить</button>
                </div>
            </form>





            <div class="js-b-popup-1 b-popup-black">
                <div class="b-popup-1 card card-success">
                    <div class="card-header">
                        <h3 class="card-title">Загрузка файлов</h3>

                        <div class="card-tools">
                            <button type="button" class="js-b-popup-1-maximize btn btn-tool">
{{--                                data-card-widget="maximize"--}}
                                <i class="fas fa-expand"></i>
                            </button>
                            <button type="button" class="js-b-popup-1-close btn btn-tool" data-card-widget="remove">
                                <i class="fas fa-times"></i>
                            </button>
                        </div>
                        <!-- /.card-tools -->
                    </div>
                    <!-- /.card-header -->
                    <div class="card-body">
                        <form data-action="{{ route('image.upload') }}" method="POST" enctype="multipart/form-data" id="add-project-form">
                        @csrf              <!-- с версии Laravel 5.6 -->
                            <!-- поле для загрузки файла -->
                            {{--                <input type="file" name="userfile">--}}

                            <div class="js-upload-image-section upload-image-section">
                                <section>
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
                                <div class="error"></div>
                            </div>

                            {{--                <input type="submit">--}}
                            <button class="js-btn-upload-image btn btn-primary">Готово</button>
                        </form>
                    </div>
                    <!-- /.card-body -->
                </div>
            </div>

{{--            <div class="popup-black">--}}
{{--                <div class="popup">--}}
{{--                    <a class="popup-close" href="#">Закрыть</a>--}}

{{--                    --}}
{{--                </div>--}}
{{--            </div>--}}

        </div>
    </div><!-- /.container-fluid -->
</section>
<!-- /.content -->
@endsection
