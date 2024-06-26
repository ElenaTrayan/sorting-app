@extends('layouts.admin_layout')

@section('title', 'Все посты')

@section('content')
    <!-- Content Header (Page header) -->
    <div class="content-header">
        <div class="container-fluid">
            <div class="row mb-2">
                <div class="col-sm-6">
                    <h1 class="m-0">Все посты</h1>
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

                @include('admin.includes.modal_delete_item')

                <div class="card-header" style="display: flex; justify-content: end;">
                    <a href="{{ route('media-parser') }}" class="btn btn-outline-info" style="width: 200px;">Импорт изображений</a>
                    <a href="{{ route('posts.create') }}" class="btn btn-block btn-outline-info" style="width: 160px;">Добавить пост</a>
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

                    <div class="b-search-and-hashtags"><!-- section with search and hashtags -->
                        <div class="input-field">
                            <input id="search-term" type="text" class="validate">
                            <label for="search-term">Поиск</label>
                        </div>

                        <div class="input-field">
                            <input id="include-tag-input" type="text" class="validate">
                            <label for="include-tag-input">Включить хештег</label>
                            <a id="add-include-tag" class="btn-floating btn-small waves-effect waves-light blue"><i class="material-icons">add</i></a>
                        </div>
                        <div id="include-tags" class="tags-container"></div>

                        <div class="input-field">
                            <input id="exclude-tag-input" type="text" class="validate">
                            <label for="exclude-tag-input">Исключить хештег</label>
                            <a id="add-exclude-tag" class="btn-floating btn-small waves-effect waves-light red"><i class="material-icons">add</i></a>
                        </div>
                        <div id="exclude-tags" class="tags-container"></div>

                        <a id="search-btn" class="btn waves-effect waves-light blue">Поиск</a>

                        <ul id="tags" class="tags">
                            <li data-id="505">#Armani<i class="js-delete-image fas fa-times-circle"></i></li>
                        </ul>
                    </div><!-- /.b-search-and-hashtags -->


                    <div class="tags">
                        <span class="tag">#тест</span>
                        <span class="tag">#модельныйтест<a href="#" class="tag-addon" onclick="removeTag('модельныйтест'); return false;">X</a></span>
                        <span class="tag">#modelagency<a href="#" class="tag-addon" onclick="removeTag('modelagency'); return false;">X</a></span>
                        <span class="tag">#beauty<a href="#" class="tag-addon" onclick="removeTag('beauty'); return false;">X</a></span>
                        <span class="tag">#modeltest<a href="#" class="tag-addon" onclick="removeTag('modeltest'); return false;">X</a></span>
                        <span class="tag">#like<a href="#" class="tag-addon" onclick="removeTag('like'); return false;">X</a></span>
                        <span class="tag">#dnepr<a href="#" class="tag-addon" onclick="removeTag('dnepr'); return false;">X</a></span>
                        <span class="tag">#красота<a href="#" class="tag-addon" onclick="removeTag('красота'); return false;">X</a></span>
                        <span class="tag">#2amodels<a href="#" class="tag-addon" onclick="removeTag('2amodels'); return false;">X</a></span>
                        <span class="tag">#интересное<a href="#" class="tag-addon" onclick="removeTag('интересное'); return false;">X</a></span>
                        <span class="tag">#style<a href="#" class="tag-addon" onclick="removeTag('style'); return false;">X</a></span>
                        <span class="tag">#photo<a href="#" class="tag-addon" onclick="removeTag('photo'); return false;">X</a></span>
                        <span class="tag">#modelscout<a href="#" class="tag-addon" onclick="removeTag('modelscout'); return false;">X</a></span>
                        <span class="tag">#followme<a href="#" class="tag-addon" onclick="removeTag('followme'); return false;">X</a></span>
                        <span class="tag">#blackandwhite<a href="#" class="tag-addon" onclick="removeTag('blackandwhite'); return false;">X</a></span>
                        <span class="tag">#статьмоделью<a href="#" class="tag-addon" onclick="removeTag('статьмоделью'); return false;">X</a></span>
                        <span class="tag">#модельноеагентство<a href="#" class="tag-addon" onclick="removeTag('модельноеагентство'); return false;">X</a></span>
                        <span class="tag">#ukraine<a href="#" class="tag-addon" onclick="removeTag('ukraine'); return false;">X</a></span>
                        <span class="tag">#portrait<a href="#" class="tag-addon" onclick="removeTag('portrait'); return false;">X</a></span>
                        <span class="tag">#natural<a href="#" class="tag-addon" onclick="removeTag('natural'); return false;">X</a></span>
                        <span class="tag">#modeljob<a href="#" class="tag-addon" onclick="removeTag('modeljob'); return false;">X</a></span>
                        <span class="tag">#model<a href="#" class="tag-addon" onclick="removeTag('model'); return false;">X</a></span>
                        <span class="tag">#dnipro<a href="#" class="tag-addon" onclick="removeTag('dnipro'); return false;">X</a></span>
                        <span class="tag">#опрос<a href="#" class="tag-addon" onclick="removeTag('опрос'); return false;">X</a></span>
                        <span class="tag">#обучениебесплатно<a href="#" class="tag-addon" onclick="removeTag('обучениебесплатно'); return false;">X</a></span>
                        <span class="tag">#моделинг<a href="#" class="tag-addon" onclick="removeTag('моделинг'); return false;">X</a></span>
                        <span class="tag">#test<a href="#" class="tag-addon" onclick="removeTag('test'); return false;">X</a></span>
                        <span class="tag">#photographer<a href="#" class="tag-addon" onclick="removeTag('photographer'); return false;">X</a></span>
                        <span class="tag">#modelagent<a href="#" class="tag-addon" onclick="removeTag('modelagent'); return false;">X</a></span>
                        <span class="tag">#моделькиев<a href="#" class="tag-addon" onclick="removeTag('моделькиев'); return false;">X</a></span>
                    </div>


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

                    <div class="b-cards">
                        <div class="grid">
                            @include('admin.posts.parts.post_items')
                        </div>
                    </div><!-- /.b-cards -->

                    <div class="posts-pagination">
{{--                        <a id="load-more" href="">Показать ещё</a>--}}
                        <div class="ajax-load text-center" style="display:none">
                            <p><img src="/images/loading-slow-internet.gif">Loading More post</p>
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
{{--                        {{ $posts->links('admin.includes.pagination') }}--}}
                    </div>
                </div><!-- /.b-list-of-posts -->

            </div><!-- /.posts-index -->

        </div><!-- /.container-fluid -->
    </section>
    <!-- /.content -->
@endsection
