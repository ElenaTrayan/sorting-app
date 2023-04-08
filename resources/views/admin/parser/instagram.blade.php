@extends('layouts.admin_layout')

@section('title', 'Все посты')

@section('content')
    <!-- Content Header (Page header) -->
    <div class="content-header">
        <div class="container-fluid">
            <div class="row mb-2">
                <div class="col-sm-6">
                    <h1 class="m-0">Instagram</h1>
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
                    <span data-href="{{ route('instagram-parser-get-media') }}" class="btn btn-block btn-outline-info" style="width: 160px;">Спарсить картинки</span>
                </div>

            <div class="b-list-of-posts">
                <div class="b-search-and-hashtags"><!-- section with search and hashtags -->

                </div><!-- /.b-search-and-hashtags -->

                <div class="b-cards">

                    <div class="grid">

                    </div>

                </div><!-- /.b-cards -->

            </div><!-- /.b-list-of-posts -->

            </div><!-- /.posts-index -->

        </div><!-- /.container-fluid -->
    </section>
    <!-- /.content -->

    @push('footer-scripts')
        <script>
            window.fbAsyncInit = function() {
                FB.init({
                    appId      : '2977494662559993',
                    cookie     : true,
                    xfbml      : true,
                    version    : 'v16.0'
                });

                FB.AppEvents.logPageView();

                FB.getLoginStatus(function(response) {
                    // Обработка ответа
                    console.log(response);
                });
            };

            (function(d, s, id){
                var js, fjs = d.getElementsByTagName(s)[0];
                if (d.getElementById(id)) {return;}
                js = d.createElement(s); js.id = id;
                js.src = "https://connect.facebook.net/en_US/sdk.js";
                fjs.parentNode.insertBefore(js, fjs);
            }(document, 'script', 'facebook-jssdk'));
        </script>
    @endpush

@endsection
