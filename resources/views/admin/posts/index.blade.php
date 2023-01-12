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

                </div><!-- /.b-search-and-hashtags -->

                <div class="b-cards">

                    <div class="grid">
                        @include('admin.posts.parts.post_items')
                    </div>

                </div><!-- /.b-cards -->

                {{ $posts->links('admin.includes.pagination') }}

            </div><!-- /.b-list-of-posts -->

            </div><!-- /.posts-index -->

        </div><!-- /.container-fluid -->
    </section>
    <!-- /.content -->
@endsection
