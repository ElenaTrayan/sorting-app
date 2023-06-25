@extends('layouts.admin_layout')

@section('title', 'Добавить пост')

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
        <div class="card card-primary card-create">

            <div class="alert alert-dismissible hide">
{{--                <button type="button" class="close" data-dismiss="alert" aria-hidden="true">×</button>--}}
                <h5><i class="icon fas fa-ban"></i> Alert!</h5>
                <span class="message"></span>
            </div>

            <!-- form start -->
            <form action="{{ route('posts.store') }}" method="POST" enctype="multipart/form-data" id="creationform" name="creationform">
                @csrf
                <div class="card-body">
                    <div class="card-body-block">

                        <div class="b-parse-info">
                            <div class="input-group input-group-lg mb-3">
                                <div class="input-group-prepend">
                                    <button type="button" id="change-type-for-parsing" class="btn btn-warning dropdown-toggle" data-toggle="dropdown" aria-expanded="false" data-type="">
                                        Тип поста
                                    </button>
                                    <ul class="dropdown-menu" x-placement="bottom-start" style="position: absolute; will-change: transform; top: 0px; left: 0px; transform: translate3d(0px, 48px, 0px);">
                                        <li class="dropdown-item" data-type="text_picture"><a href="#">Текст + Изображение</a></li>
                                        <li class="dropdown-item" data-type="image"><a href="#">Изображение</a></li>
                                        <li class="dropdown-item" data-type="text"><a href="#" >Текст</a></li>
                                        <li class="dropdown-item" data-type="film"><a href="#">Фильм / Сериал</a></li>
                                        <li class="dropdown-item" data-type="recipe"><a href="#">Рецепт</a></li>
                                        <li class="dropdown-item"><a href="#">Something else here</a></li>
                                        <li class="dropdown-divider"></li>
                                        <li class="dropdown-item"><a href="#">Separated link</a></li>
                                    </ul>
                                </div>
                                <!-- /btn-group -->
                                <input id="link-for-parsing" type="text" class="form-control">
                            </div>
                            <button type="button" class="btn btn-info btn-flat" id="get-parsed-post-info" data-action="{{ route('parse.get-parsed-post-info') }}">Спарсить информацию</button>
                        </div>

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
                                <option value="">-</option>
                                @foreach($categories as $category)
                                    <option value="{{ $category->id }}">{{ $category->title }}</option>
                                @endforeach
                            </select>
                        </div>

                        <div class="b-parsing-type-blocks">
                            <div id="tab-text_picture">

                            </div>
                            <div id="tab-picture">
                            </div>

                            <div id="tab-text">

                            </div>

                            <div id="tab-film">
                                <div class="form-group">
                                    <label for="film-genres">Жанры фильма:</label>
                                    <input type="text" class="form-control" name="film-genres" id="film-genres" placeholder="">
                                </div>

                                <div class="form-group">
                                    <label for="imdb-rating">Рейтинг IMDb:</label>
                                    <input type="text" class="form-control" name="imdb-rating" id="imdb-rating" placeholder="">
                                </div>

                                <div class="form-group">
                                    <label for="my-assessment">Моя оценка:</label>
                                    <select name="my-assessment" id="my-assessment">
                                        <option value="value0">-</option>
                                        <option value="value10">10</option>
                                        <option value="value9">9</option>
                                        <option value="value8">8</option>
                                        <option value="value7">7</option>
                                        <option value="value6">6</option>
                                        <option value="value5">5</option>
                                        <option value="value4">4</option>
                                        <option value="value3">3</option>
                                        <option value="value2">2</option>
                                        <option value="value1">1</option>
                                    </select>
                                </div>

                                <div class="form-group">
                                    <label for="film-year">Год:</label>
                                    <input type="text" class="form-control" name="film-year" id="film-year" placeholder="">
                                </div>

                                <div class="form-group">
                                    <label for="film-country">Страна:</label>
                                    <input type="text" class="form-control" name="film-country" id="film-country" placeholder="">
                                </div>

                                <div class="form-group">
                                    <label for="film-director">Режиссер:</label>
                                    <input type="text" class="form-control" name="film-director" id="film-director" placeholder="">
                                </div>

                                <div class="form-group">
                                    <label for="film-actors">Актёры:</label>
                                    <input type="text" class="form-control" name="film-actors" id="film-actors" placeholder="">
                                </div>

                                <div class="form-group">
                                    <label for="film-duration">Длительность:</label>
                                    <input type="text" class="form-control" name="film-duration" id="film-duration" placeholder="">
                                </div>

                                <div class="form-group">
                                    <label for="film-rating-mpaa">Pейтинг MPAA:</label>
                                    <input type="text" class="form-control" name="film-rating-mpaa" id="film-rating-mpaa" placeholder="">
                                </div>

                                <div class="form-group">
                                    <label for="film-description">Описание:</label>
                                    <textarea class="form-control" name="film-description" id="film-description" placeholder=""></textarea>
                                </div>

                            </div>

                            <div id="tab-recipe">

                            </div>
                        </div>

                        <button type="button" class="btn btn-info btn-flat" id="generate-post" data-action="">Сформировать пост</button>

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

                    </div>

                    <div class="card-body-block">
                        <div>
                            <!-- TODO -->
                            <input type="radio" id="one-post" name="one-post" value="one-post">
                            <label for="one-post">Один пост</label><br>
                            <input type="radio" id="two-posts" name="two-posts" value="two-posts">
                            <label for="two-posts">Несколько постов</label><br>
                        </div>

                        <div>
                            <input type="checkbox" id="vehicle1" name="vehicle1" value="Bike">
                            <label for="vehicle1">Сгруппировать посты</label><br>
                            <button type="button" class="js-get-text-from-image" data-action="{{ route('get-text-from-image') }}">
                                Считать текст с изображения
                            </button>
                        </div>

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
                    </div>
                </div>

                <!-- /.card-body -->
                <div class="card-footer">
                    <div class="form-group">
                        <textarea id="editor" name="content" placeholder="Введите текст поста ..."></textarea>
                    </div>

                    <button id="submit-creation-form" type="submit" class="btn btn-primary">Добавить</button>
                </div>
            </form>



{{--            <div class="js-b-popup-1 b-popup-black">--}}
{{--                <div class="b-popup-1 card card-success">--}}
{{--                    <div class="card-header">--}}
{{--                        <h3 class="card-title">Загрузка файлов</h3>--}}

{{--                        <div class="card-tools">--}}
{{--                            <button type="button" class="js-b-popup-1-maximize btn btn-tool">--}}
{{--                                data-card-widget="maximize"--}}
{{--                                <i class="fas fa-expand"></i>--}}
{{--                            </button>--}}
{{--                            <button type="button" class="js-b-popup-1-close btn btn-tool" data-card-widget="remove">--}}
{{--                                <i class="fas fa-times"></i>--}}
{{--                            </button>--}}
{{--                        </div>--}}
{{--                        <!-- /.card-tools -->--}}
{{--                    </div>--}}
{{--                    <!-- /.card-header -->--}}
{{--                    <div class="card-body">--}}
{{--                        <form data-action="{{ route('image.upload') }}" method="POST" enctype="multipart/form-data" id="add-project-form">--}}
{{--                        @csrf              <!-- с версии Laravel 5.6 -->--}}
{{--                            <!-- поле для загрузки файла -->--}}
{{--                            --}}{{--                <input type="file" name="userfile">--}}

{{--                            <div class="js-upload-image-section upload-image-section">--}}
{{--                                <section>--}}
{{--                                    <div class="js-images images"></div>--}}
{{--                                    <div class="title">--}}
{{--                                        <figure></figure>--}}
{{--                                        <p>Перетащите сюда фото или видео</p>--}}
{{--                                    </div>--}}
{{--                                    <input type="file" name="files[]" multiple="multiple" accept="image/x-png,image/jpeg">--}}
{{--                                </section>--}}
{{--                                <div class="progress">--}}
{{--                                    <div class="progress-bar"></div>--}}
{{--                                    <div class="progress-value">0 %</div>--}}
{{--                                </div>--}}
{{--                                <div class="error"></div>--}}
{{--                            </div>--}}

{{--                            --}}{{--                <input type="submit">--}}
{{--                            <button class="js-btn-upload-image btn btn-primary">Готово</button>--}}
{{--                        </form>--}}
{{--                    </div>--}}
{{--                    <!-- /.card-body -->--}}
{{--                </div>--}}
{{--            </div>--}}

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
