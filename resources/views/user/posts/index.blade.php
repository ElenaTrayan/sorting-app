@extends('layouts.user_layout')

@section('title', 'Все посты')

@push('navbar-breadcrumb')
    <li class="nav-item d-flex align-items-center">
        <ol class="breadcrumb b-navbar-breadcrumb">
            <li class="breadcrumb-item"><a href="{{ route('cabinet.home') }}">Главная</a></li>
            <li class="breadcrumb-item active">Все посты</li>
        </ol>
    </li>
@endpush

@push('head-styles')
    {{-- Manrope — шрифт макета Claude Design, тот же, что и на "База контактов"
         (самостоятельно захостен в /admin/fonts/manrope) --}}
    <link rel="stylesheet" href="/admin/fonts/manrope/manrope.css">
@endpush

@section('content')
    {{-- хлебные крошки для этой страницы вынесены в верхний navbar-nav-left, см.
         resources/views/layouts/user_layout.blade.php --}}
    {{-- шапка, панель вкладок/сортировки — по макету Claude Design, взяты у "База
         контактов" (см. https://drive.google.com/file/d/1b4hWwYnDBMjNaom0xNmx7T2FbcfXWQq8);
         сама сетка карточек постов (.b-card/.grid, Masonry) не тронута --}}
    <!-- Content Header (Page header) -->
    <div class="content-header content-header--posts">
        <div class="container-fluid">
            <div class="b-posts-header">
                <div>
                    <h1 class="m-0">Все посты</h1>
                    <div class="b-posts-header__subtitle">Управляйте контентом, просматривайте, редактируйте и публикуйте посты</div>
                </div>
                <div class="b-posts-header__actions">
                    <a href="{{ route('posts.create') }}" class="b-posts-toolbar__create">
                        <i class="fas fa-plus"></i> Добавить пост
                    </a>
                    @if ($withoutHashtags)
                        <a href="{{ request()->fullUrlWithQuery(['without_hashtags' => null]) }}" class="b-posts-header__btn b-posts-header__btn--active">
                            <i class="fas fa-times"></i> Сбросить фильтр
                        </a>
                    @else
                        <a href="{{ request()->fullUrlWithQuery(['without_hashtags' => 1]) }}" class="b-posts-header__btn">
                            <i class="fas fa-hashtag"></i> Посты без хештегов
                        </a>
                    @endif
                    <button type="button" id="js-export-images-btn" class="b-posts-header__btn" data-action="{{ route('search.export-images-by-hashtags') }}">
                        <i class="fas fa-file-export"></i> Экспорт изображений
                    </button>
                </div>
            </div>
        </div><!-- /.container-fluid -->
    </div>
    <!-- /.content-header -->

    <!-- Main content -->
    <section class="content">
        <div class="container-fluid">
            <div class="posts-index ribbon-box" id="posts-index">
                @if (session('success'))
                    @push('footer-scripts')
                        <script>
                            toastr.success('{{ session('success') }}');
                        </script>
                    @endpush
                @elseif(session('errors'))
                    @push('footer-scripts')
                        <script>
                            toastr.error('{{ session('errors') }}');
                        </script>
                    @endpush
                @endif

                @include('user.includes.modal_delete_item')

                @php
                    $postsSortLabels = ['new' => 'Сначала новые', 'old' => 'Сначала старые', 'title' => 'По названию А-Я'];
                @endphp
                <div class="b-posts-toolbar">
                    <div class="b-posts-toolbar__tabs">
                        <a href="{{ request()->fullUrlWithQuery(['type' => null, 'page' => null]) }}" class="b-posts-toolbar__tab @if ($activeType === 'all') b-posts-toolbar__tab--active @endif">Все ({{ $typeCounts['all'] }})</a>
                        <a href="{{ request()->fullUrlWithQuery(['type' => 'photo', 'page' => null]) }}" class="b-posts-toolbar__tab @if ($activeType === 'photo') b-posts-toolbar__tab--active @endif">Фото ({{ $typeCounts['photo'] }})</a>
                        <a href="{{ request()->fullUrlWithQuery(['type' => 'video', 'page' => null]) }}" class="b-posts-toolbar__tab @if ($activeType === 'video') b-posts-toolbar__tab--active @endif">Видео ({{ $typeCounts['video'] }})</a>
                        <a href="{{ request()->fullUrlWithQuery(['type' => 'carousel', 'page' => null]) }}" class="b-posts-toolbar__tab @if ($activeType === 'carousel') b-posts-toolbar__tab--active @endif">Карусель ({{ $typeCounts['carousel'] }})</a>
                    </div>

                    <div class="b-posts-sort js-posts-sort">
                        <button type="button" class="b-posts-sort__trigger js-posts-sort-trigger">
                            <i class="fas fa-sort-amount-down"></i>
                            <span>{{ $postsSortLabels[$activeSort] }}</span>
                            <i class="fas fa-chevron-down b-posts-sort__chevron"></i>
                        </button>
                        <ul class="b-posts-sort__list js-posts-sort-list">
                            {{-- у каждого пункта своё явное значение sort — никто не полагается на
                                 "нет параметра = дефолт", т.к. дефолт (см. PostController::index())
                                 это "old", а не первый пункт в списке --}}
                            <li><a href="{{ request()->fullUrlWithQuery(['sort' => 'new', 'page' => null]) }}" class="b-posts-sort__option @if ($activeSort === 'new') is-active @endif">Сначала новые</a></li>
                            <li><a href="{{ request()->fullUrlWithQuery(['sort' => 'old', 'page' => null]) }}" class="b-posts-sort__option @if ($activeSort === 'old') is-active @endif">Сначала старые</a></li>
                            <li><a href="{{ request()->fullUrlWithQuery(['sort' => 'title', 'page' => null]) }}" class="b-posts-sort__option @if ($activeSort === 'title') is-active @endif">По названию А-Я</a></li>
                        </ul>
                    </div>
                </div>

{{--            <form id="generator-form-keyword" class="row ajax-submit bind-refresh-ad" action="/hashtag/ajax-generator/" method="post" onsubmit="return false;">--}}
{{--                <input type="hidden" name="_csrf" value="h2Zisgc82r1iYo_WVADORPygmPNvVJBJ3S0_dNumSVTBKQTdREaA0xAtu6wlYqg8kfPchhts3gWCcg85gtY7eQ==">--}}
{{--                <input type="hidden" name="type" value="keyword">--}}
{{--                <div class="col-lg-10 mb-2 mb-lg-0">--}}
{{--                    <div class="js-focus-state field-keywordform-keyword required validating">--}}
{{--                        <div class="input-group">--}}
{{--                            <input type="text" id="keywordform-keyword" class="form-control is-valid" name="KeywordForm[keyword]" aria-required="true" aria-invalid="false">--}}
{{--                        </div>--}}

{{--                        <div class="invalid-feedback"></div>--}}
{{--                    </div>--}}
{{--                    <div class="mt-1">--}}
{{--                        <p class="font-size-1 hint text-dark popular-search">--}}
{{--                            Popular searches:--}}
{{--                            <a class="link-muted" href="javascript:void(0)" data-value="Travel girl">Travel girl, </a>--}}
{{--                            <a class="link-muted ml-1" href="javascript:void(0)" data-value="Paris">Paris, </a>--}}
{{--                            <a class="link-muted ml-1" href="javascript:void(0)" data-value="Like4like">Like4like</a>--}}
{{--                        </p>--}}
{{--                    </div>--}}
{{--                </div>--}}
{{--                <div class="col-lg-2 align-items-start">--}}
{{--                    <button type="submit" class="btn btn-block btn-primary transition-3d-hover">Поиск</button>--}}
{{--                </div>--}}

{{--                <div class="form-group field-keywordform-recaptcha">--}}
{{--                    <input type="hidden" id="keywordform-recaptcha" class="form-control" name="KeywordForm[reCaptcha]" value="03AIIukzjz8H8wRdqtD0rGx77rfN_-MbSCOA7Yi6dNdBnwSZCOfm3audvj2nOr-Zd87SsDBQnPCI2TT_QVWY4DHyhL2iVzbZmltGXXLE5d57baYq7ImWiMHQt2E9liDY8ju5n3X-ilDKOkmbTebSlQxNUJktdgTAXQoGZLwKW4fXjPqSWeAeR1EJi0BvGypEv-qra4DA51ADzgcWjA4lC2WY63KCQ5v73490aDI8hH0gT0W83Y6gcySCfnwSHCuPDV_8dmsQu0YZ2c8Von899OLI4KOtxiM1grGjd-8XLsC2cSRmiaGiCPvzIf4W_D_KEPUECxmc1GJq2epB6FiyF5GAX0q9Iy2fKydIuVOMwtTDGQk1dG4rVhkUho0mB4PDSbsCNbAmGJTAFUAOYopNzhdvSSlqWKpkoCF6GdWh81bXHUA6n262YuHUmL0JxNg7lfJnHtNagr45PwXHnWnZm99W6UqSbIUm7MweDf4HqajhZWrg8L-4YSrp0mJwtt0xqsKPS6xglx_WjwFYH5Ww50wzqhBNU7fn-q8w">--}}
{{--                    <div class="invalid-feedback"></div>--}}
{{--                </div>--}}
{{--            </form>--}}

                <div class="b-list-of-posts">

                    <div class="modal fade" id="modal-add-hashtag" tabindex="-1" role="dialog" aria-labelledby="modalAddHashtagLabel"
                         aria-hidden="true">
                        <div class="modal-dialog modal-dialog-centered" role="document">
                            <div class="modal-content">
                                <div class="modal-header">
                                    <h5 class="modal-title" id="modalAddHashtagLabel"></h5>
                                </div>
                                <div class="modal-body">
                                    <div class="b-add-tag">
                                        <div class="input-group input-group-sm add-tag">
                                            <input id="search-input-2" type="text" class="form-control"
                                                   data-action="{{ route('search.hashtag') }}">
                                            <span class="input-group-append">
                            <button type="button" class="btn btn-info btn-flat" id="add-tag"
                                    data-action="{{ route('hashtags.store') }}">Добавить тег</button>
                        </span>
                                        </div>
                                        <ul id="b-search__results-2" class="b-search__results-2"></ul>
                                        <ul id="b-selected-tags-2" class="b-selected-tags-2"></ul>
                                    </div>
                                </div>
                                <div class="modal-footer">
                                    <button type="button" class="btn btn-primary" data-action="save-request">Сохранить</button>
                                    <button type="button" class="btn btn-secondary" data-action="close-modal-add-hashtag">Отмена
                                    </button>
                                    {{--                data-dismiss="modal"--}}
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="b-search-and-hashtags"><!-- section with search and hashtags -->

                    </div><!-- /.b-search-and-hashtags -->

                    <div class="modal fade" id="modal-export-images" tabindex="-1" role="dialog" aria-hidden="true">
                        <div class="modal-dialog modal-dialog-centered" role="document">
                            <div class="modal-content">
                                <div class="modal-header">
                                    <h5 class="modal-title">Экспорт изображений</h5>
                                </div>
                                <div class="modal-body">
                                    <p>Будет создан zip-архив с изображениями из текущих результатов поиска по хештегам.</p>
                                    <label for="export-images-filename">Имя архива</label>
                                    <input type="text" id="export-images-filename" class="form-control" maxlength="150">
                                    <div class="js-export-images__suggestions mt-2"></div>
                                </div>
                                <div class="modal-footer">
                                    <button type="button" class="btn btn-primary js-export-images__confirm">Скачать</button>
                                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Отмена</button>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="b-cards">
                        <div class="grid are-images-unloaded">
                            @include('user.posts.parts.post_items')
                        </div>
                    </div><!-- /.b-cards -->

                    <div class="posts-pagination">
{{--                        <a id="load-more" href="">Показать ещё</a>--}}
                        <div class="ajax-load" style="display:none">
                            <span class="ajax-load__spinner"></span>
                            <span class="ajax-load__text">Загрузка постов…</span>
                        </div>
                        <div class="page-load-status">
                            <div class="loader-ellips infinite-scroll-request">
                                <span class="loader-ellips__dot"></span>
                                <span class="loader-ellips__dot"></span>
                                <span class="loader-ellips__dot"></span>
                                <span class="loader-ellips__dot"></span>
                            </div>
                            <p class="infinite-scroll-last">End of content</p>
                            <p class="infinite-scroll-error">No more pages to load</p>
                        </div>
{{--                        {{ $posts->links('user.includes.pagination') }}--}}
                    </div>
                </div><!-- /.b-list-of-posts -->

            </div><!-- /.posts-index -->

        </div><!-- /.container-fluid -->
    </section>
    <!-- /.content -->

{{-- галерея-просмотрщик постов (клик по картинке/видео/заголовку карточки —
     data-fancybox="posts-gallery" в post_items.blade.php) — один Fancybox.bind на
     всю группу с делегированием, поэтому подхватывает и карточки, дозагруженные
     бесконечным скроллом; сам биндинг делается только тут (не в post_items.blade.php),
     чтобы не навешивать его повторно при каждой AJAX-подгрузке страницы --}}
@push('footer-scripts')
<script>
    Fancybox.bind('[data-fancybox="posts-gallery"]', {
        type: 'ajax',
        dragToClose: false,
        // на всю страницу вместо небольшой карточки-модалки + свой цвет крестика
        // закрытия (иначе он белый по умолчанию — не виден на белой панели превью),
        // см. .fancybox-posts-gallery в admin-panel.css
        mainClass: 'fancybox-posts-gallery',
        // лента миниатюр внизу (плагин Thumbs) — Fancybox показывает её сама, раз
        // посты сгруппированы в одну галерею; стрелки prev/next между постами
        // (Navigation) остаются, убираем только ленту
        Thumbs: false,
    });

    // клик по картинке внутри поп-апа с превью поста — открывает её отдельно, поверх
    // текущего поп-апа, в полный размер с зумом (data-fancybox="post-preview-image-{id}",
    // свой id на каждый пост — см. комментарий в post_preview.blade.php про предзагрузку
    // соседних слайдов); контент превью подгружается через ajax уже после загрузки
    // страницы, поэтому тоже через делегирование, отдельным Fancybox.bind
    Fancybox.bind('[data-fancybox^="post-preview-image-"]', {
        dragToClose: false,
        // свой mainClass, отдельный от 'fancybox-posts-gallery' — это чистый просмотр
        // картинки, а не белая карточка превью, поэтому .fancybox__content там не
        // должен получать padding:20px 36px из превью-попапа (см. .fancybox-post-zoom
        // в admin-panel.css)
        mainClass: 'fancybox-post-zoom',
    });
</script>
@endpush

@endsection
