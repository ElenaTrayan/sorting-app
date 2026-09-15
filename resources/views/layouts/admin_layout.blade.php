<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Админ-панель - @yield('title')</title>

    <!-- Google Font: Source Sans Pro (самостоятельно захостен в /admin/fonts/source-sans-pro) -->
    <link rel="stylesheet" href="/admin/fonts/source-sans-pro/source-sans-pro.css">
    <!-- Google Font: Nunito (самостоятельно захостен в /admin/fonts/nunito) — только для левого меню -->
    <link rel="stylesheet" href="/admin/fonts/nunito/nunito.css">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="/admin/plugins/fontawesome-free/css/all.min.css">
    <!-- Tempusdominus Bootstrap 4 -->
    <link rel="stylesheet" href="/admin/plugins/tempusdominus-bootstrap-4/css/tempusdominus-bootstrap-4.min.css">
    <!-- iCheck -->
    <link rel="stylesheet" href="/admin/plugins/icheck-bootstrap/icheck-bootstrap.min.css">
    <!-- JQVMap -->
    <link rel="stylesheet" href="/admin/plugins/jqvmap/jqvmap.min.css">
    <!-- Theme style -->
    <link rel="stylesheet" href="/admin/dist/css/adminlte.min.css">
    <!-- overlayScrollbars -->
    <link rel="stylesheet" href="/admin/plugins/overlayScrollbars/css/OverlayScrollbars.min.css">
    <!-- Daterange picker -->
    <link rel="stylesheet" href="/admin/plugins/daterangepicker/daterangepicker.css">
    <!-- summernote -->
    <link rel="stylesheet" href="/admin/plugins/summernote/summernote-bs4.min.css">

    <link rel="stylesheet" href="/admin/plugins/fancybox/fancybox.css" />

    <link rel="stylesheet" href="/admin/plugins/toastr/toastr.css">

    <link rel="stylesheet" href="/admin/admin-panel.css?v={{ filemtime(public_path('admin/admin-panel.css')) }}">

    @stack('head-styles')
</head>
<body class="hold-transition sidebar-mini layout-fixed" data-preload-duration="999999999">
<div class="wrapper">

    <!-- Preloader -->
    <div class="preloader flex-column justify-content-center align-items-center">
        <img class="animation__shake" src="/admin/dist/img/AdminLTELogo.png" alt="AdminLTELogo" height="60" width="60">
    </div>

    <!-- Navbar -->
    <nav class="main-header navbar navbar-expand navbar-white navbar-light">

        <!-- Left navbar links -->
        <ul class="navbar-nav navbar-nav-left">
            <li class="nav-item">
                <a class="nav-link" data-widget="pushmenu" href="#" role="button"><i class="fas fa-bars"></i></a>
            </li>

            @if (request()->routeIs('posts.index'))
            <div class="" style="display: flex;flex-flow: column;width: 100%;">
                @include('layouts.parts.search_widget')
            </div>
            @endif

            {{-- хлебные крошки в navbar-nav-left — страница сама решает, показывать ли их,
                 см. @push('navbar-breadcrumb') в resources/views/admin/contacts/index.blade.php
                 и .../contacts/show.blade.php (по макету Claude Design) --}}
            @stack('navbar-breadcrumb')
        </ul>
{{--        b-tags-results--}}

        <!-- Right navbar links -->
        <ul class="navbar-nav ml-auto navbar-nav-right">
            <!-- Navbar Search -->
            <li class="nav-item">
{{--                <a class="nav-link" data-widget="navbar-search" href="#" role="button">--}}
{{--                    <i class="fas fa-search"></i>--}}
{{--                </a>--}}
{{--                <div class="navbar-search-block">--}}
{{--                    <form class="form-inline">--}}
{{--                        <div class="input-group input-group-sm">--}}
{{--                            <input class="form-control form-control-navbar" type="search" placeholder="Search" aria-label="Search">--}}
{{--                            <div class="input-group-append">--}}
{{--                                <button class="btn btn-navbar" type="submit">--}}
{{--                                    <i class="fas fa-search"></i>--}}
{{--                                </button>--}}
{{--                                <button class="btn btn-navbar" type="button" data-widget="navbar-search">--}}
{{--                                    <i class="fas fa-times"></i>--}}
{{--                                </button>--}}
{{--                            </div>--}}
{{--                        </div>--}}
{{--                    </form>--}}
{{--                </div>--}}
            </li>

            <!-- Messages Dropdown Menu -->
            <li class="nav-item dropdown">
                <a class="nav-link" data-toggle="dropdown" href="#">
                    <i class="far fa-comments"></i>
                    <span class="badge badge-danger navbar-badge">3</span>
                </a>
                <div class="dropdown-menu dropdown-menu-lg dropdown-menu-right">
                    <a href="#" class="dropdown-item">
                        <!-- Message Start -->
                        <div class="media">
                            <img src="/admin/dist/img/user1-128x128.jpg" alt="User Avatar" class="img-size-50 mr-3 img-circle">
                            <div class="media-body">
                                <h3 class="dropdown-item-title">
                                    Brad Diesel
                                    <span class="float-right text-sm text-danger"><i class="fas fa-star"></i></span>
                                </h3>
                                <p class="text-sm">Call me whenever you can...</p>
                                <p class="text-sm text-muted"><i class="far fa-clock mr-1"></i> 4 Hours Ago</p>
                            </div>
                        </div>
                        <!-- Message End -->
                    </a>
                    <div class="dropdown-divider"></div>
                    <a href="#" class="dropdown-item">
                        <!-- Message Start -->
                        <div class="media">
                            <img src="/admin/dist/img/user8-128x128.jpg" alt="User Avatar" class="img-size-50 img-circle mr-3">
                            <div class="media-body">
                                <h3 class="dropdown-item-title">
                                    John Pierce
                                    <span class="float-right text-sm text-muted"><i class="fas fa-star"></i></span>
                                </h3>
                                <p class="text-sm">I got your message bro</p>
                                <p class="text-sm text-muted"><i class="far fa-clock mr-1"></i> 4 Hours Ago</p>
                            </div>
                        </div>
                        <!-- Message End -->
                    </a>
                    <div class="dropdown-divider"></div>
                    <a href="#" class="dropdown-item">
                        <!-- Message Start -->
                        <div class="media">
                            <img src="/admin/dist/img/user3-128x128.jpg" alt="User Avatar" class="img-size-50 img-circle mr-3">
                            <div class="media-body">
                                <h3 class="dropdown-item-title">
                                    Nora Silvester
                                    <span class="float-right text-sm text-warning"><i class="fas fa-star"></i></span>
                                </h3>
                                <p class="text-sm">The subject goes here</p>
                                <p class="text-sm text-muted"><i class="far fa-clock mr-1"></i> 4 Hours Ago</p>
                            </div>
                        </div>
                        <!-- Message End -->
                    </a>
                    <div class="dropdown-divider"></div>
                    <a href="#" class="dropdown-item dropdown-footer">See All Messages</a>
                </div>
            </li>
            <!-- Notifications Dropdown Menu -->
            <li class="nav-item dropdown">
                <a class="nav-link" data-toggle="dropdown" href="#">
                    <i class="far fa-bell"></i>
                    <span class="badge badge-warning navbar-badge">15</span>
                </a>
                <div class="dropdown-menu dropdown-menu-lg dropdown-menu-right">
                    <span class="dropdown-item dropdown-header">15 Notifications</span>
                    <div class="dropdown-divider"></div>
                    <a href="#" class="dropdown-item">
                        <i class="fas fa-envelope mr-2"></i> 4 new messages
                        <span class="float-right text-muted text-sm">3 mins</span>
                    </a>
                    <div class="dropdown-divider"></div>
                    <a href="#" class="dropdown-item">
                        <i class="fas fa-users mr-2"></i> 8 friend requests
                        <span class="float-right text-muted text-sm">12 hours</span>
                    </a>
                    <div class="dropdown-divider"></div>
                    <a href="#" class="dropdown-item">
                        <i class="fas fa-file mr-2"></i> 3 new reports
                        <span class="float-right text-muted text-sm">2 days</span>
                    </a>
                    <div class="dropdown-divider"></div>
                    <a href="#" class="dropdown-item dropdown-footer">See All Notifications</a>
                </div>
            </li>
            <li class="nav-item">
                <a class="nav-link" data-widget="fullscreen" href="#" role="button">
                    <i class="fas fa-expand-arrows-alt"></i>
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link" data-widget="control-sidebar" data-slide="true" href="#" role="button">
                    <i class="fas fa-th-large"></i>
                </a>
            </li>
        </ul>
    </nav>
    <!-- /.navbar -->

    <!-- Main Sidebar Container -->
    <aside class="main-sidebar b-sidebar elevation-1">
        <!-- Brand -->
        <a href="{{ route('homeAdmin') }}" class="b-sidebar-brand">
            <span class="b-sidebar-brand__icon">A</span>
            <span class="b-sidebar-brand__text">Админ-панель</span>
        </a>

        <!-- Sidebar -->
        <div class="sidebar b-sidebar__body">
            <!-- Sidebar user panel -->
            <div class="b-sidebar-user">
                <img src="/admin/dist/img/user2-160x160.jpg" class="b-sidebar-user__photo" alt="User Image">
                <div class="b-sidebar-user__info">
                    <div class="b-sidebar-user__name">{{ Auth::user()->name }}</div>
                    <div class="b-sidebar-user__role">Администратор</div>
                </div>
            </div>

            {{-- Кнопка "Поиск": на странице "Все посты" виджет и так виден в шапке (см. выше) —
                 просто прокручиваем/фокусируем его; на остальных страницах открывает
                 поп-ап modal-search-global (см. ниже, после </aside>), см. admin.js --}}
            <div class="b-sidebar-search">
                <button type="button" class="b-sidebar-search__btn js-sidebar-search-btn">
                    @include('layouts.parts.sidebar_icon', ['name' => 'search', 'class' => 'b-sidebar-search__icon'])
                    <span>Поиск</span>
                    @include('layouts.parts.sidebar_icon', ['name' => 'chevron-right', 'class' => 'b-sidebar-search__chevron'])
                </button>
            </div>

            <!-- Sidebar Menu (плоский список — без выпадающих подпунктов, пункт сразу ведёт
                 на "Все X"; "Добавить X" остаётся кнопкой на самой странице списка) -->
            <nav class="mt-2">
                <ul class="nav b-sidebar-nav flex-column" role="menu">
                    <li class="nav-item">
                        <a href="{{ route('homeAdmin') }}" class="b-sidebar-nav__link @if (request()->routeIs('homeAdmin')) is-active @endif">
                            @include('layouts.parts.sidebar_icon', ['name' => 'home', 'class' => 'b-sidebar-nav__icon'])
                            <span>Главная</span>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a href="{{ route('posts.index') }}" class="b-sidebar-nav__link @if (request()->routeIs('posts.*')) is-active @endif">
                            @include('layouts.parts.sidebar_icon', ['name' => 'edit', 'class' => 'b-sidebar-nav__icon'])
                            <span>Посты</span>
                            <span class="b-sidebar-nav__badge">6</span>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a href="{{ route('posts-categories.index') }}" class="b-sidebar-nav__link @if (request()->routeIs('posts-categories.*')) is-active @endif">
                            @include('layouts.parts.sidebar_icon', ['name' => 'grid', 'class' => 'b-sidebar-nav__icon'])
                            <span>Категории</span>
                            <span class="b-sidebar-nav__badge">6</span>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a href="{{ route('hashtags.index') }}" class="b-sidebar-nav__link @if (request()->routeIs('hashtags.*')) is-active @endif">
                            @include('layouts.parts.sidebar_icon', ['name' => 'hash', 'class' => 'b-sidebar-nav__icon'])
                            <span>Хештеги</span>
                            <span class="b-sidebar-nav__badge">6</span>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a href="pages/widgets.html" class="b-sidebar-nav__link">
                            @include('layouts.parts.sidebar_icon', ['name' => 'lightbulb', 'class' => 'b-sidebar-nav__icon'])
                            <span>Идеи Инстаграм</span>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a href="pages/widgets.html" class="b-sidebar-nav__link">
                            @include('layouts.parts.sidebar_icon', ['name' => 'image', 'class' => 'b-sidebar-nav__icon'])
                            <span>Позирование</span>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a href="{{ route('wishlists.index') }}" class="b-sidebar-nav__link @if (request()->routeIs('wishlists.*')) is-active @endif">
                            @include('layouts.parts.sidebar_icon', ['name' => 'gift', 'class' => 'b-sidebar-nav__icon'])
                            <span>My Gift List</span>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a href="{{ route('contacts.index') }}" class="b-sidebar-nav__link @if (request()->routeIs('contacts.*')) is-active @endif">
                            @include('layouts.parts.sidebar_icon', ['name' => 'users', 'class' => 'b-sidebar-nav__icon'])
                            <span>База контактов</span>
                        </a>
                    </li>

                    {{-- "Мои запасы" — единственный раскрывающийся пункт меню (остальные —
                         плоский список, см. комментарий выше), т.к. раздел из 10 подстраниц
                         этого требует напрямую. Группа авто-раскрыта, если активен любой
                         дочерний роут (stock.*). Поведение toggle — admin.js, #js-stock-nav-toggle. --}}
                    @php $stockActive = request()->routeIs('stock.*'); @endphp
                    <li class="nav-item b-sidebar-nav__group @if ($stockActive) is-open @endif">
                        <button type="button" class="b-sidebar-nav__link b-sidebar-nav__link--toggle js-stock-nav-toggle">
                            @include('layouts.parts.sidebar_icon', ['name' => 'package', 'class' => 'b-sidebar-nav__icon'])
                            <span>Мои запасы</span>
                            @include('layouts.parts.sidebar_icon', ['name' => 'chevron-down', 'class' => 'b-sidebar-nav__chevron'])
                        </button>
                        <ul class="b-sidebar-nav__submenu" @if (!$stockActive) style="display:none;" @endif>
                            <li><a href="{{ route('stock.overview') }}" class="b-sidebar-nav__sublink @if (request()->routeIs('stock.overview')) is-active @endif">@include('layouts.parts.sidebar_icon', ['name' => 'layout-dashboard', 'class' => 'b-sidebar-nav__icon']) <span>Обзор</span></a></li>
                            <li><a href="{{ route('stock.inventory.index') }}" class="b-sidebar-nav__sublink @if (request()->routeIs('stock.inventory.*')) is-active @endif">@include('layouts.parts.sidebar_icon', ['name' => 'box', 'class' => 'b-sidebar-nav__icon']) <span>Мои запасы</span></a></li>
                            <li><a href="{{ route('stock.shopping-list.index') }}" class="b-sidebar-nav__sublink @if (request()->routeIs('stock.shopping-list.*')) is-active @endif">@include('layouts.parts.sidebar_icon', ['name' => 'list', 'class' => 'b-sidebar-nav__icon']) <span>Список покупок</span></a></li>
                            <li><a href="{{ route('stock.regular-purchases.index') }}" class="b-sidebar-nav__sublink @if (request()->routeIs('stock.regular-purchases.*')) is-active @endif">@include('layouts.parts.sidebar_icon', ['name' => 'repeat', 'class' => 'b-sidebar-nav__icon']) <span>Постоянные покупки</span></a></li>
                            <li><a href="{{ route('stock.stores.index') }}" class="b-sidebar-nav__sublink @if (request()->routeIs('stock.stores.*')) is-active @endif">@include('layouts.parts.sidebar_icon', ['name' => 'store', 'class' => 'b-sidebar-nav__icon']) <span>Магазины</span></a></li>
                            <li><a href="{{ route('stock.promotions.index') }}" class="b-sidebar-nav__sublink @if (request()->routeIs('stock.promotions.*')) is-active @endif">@include('layouts.parts.sidebar_icon', ['name' => 'percent', 'class' => 'b-sidebar-nav__icon']) <span>Акции</span></a></li>
                            <li><a href="{{ route('stock.orders.index') }}" class="b-sidebar-nav__sublink @if (request()->routeIs('stock.orders.*')) is-active @endif">@include('layouts.parts.sidebar_icon', ['name' => 'truck', 'class' => 'b-sidebar-nav__icon']) <span>Заказы</span></a></li>
                            <li><a href="{{ route('stock.reserve.index') }}" class="b-sidebar-nav__sublink @if (request()->routeIs('stock.reserve.*')) is-active @endif">@include('layouts.parts.sidebar_icon', ['name' => 'shield', 'class' => 'b-sidebar-nav__icon']) <span>Резерв (ЧС)</span></a></li>
                            <li><a href="{{ route('stock.history.index') }}" class="b-sidebar-nav__sublink @if (request()->routeIs('stock.history.*')) is-active @endif">@include('layouts.parts.sidebar_icon', ['name' => 'clock', 'class' => 'b-sidebar-nav__icon']) <span>История покупок</span></a></li>
                            <li><a href="{{ route('stock.settings.index') }}" class="b-sidebar-nav__sublink @if (request()->routeIs('stock.settings.*')) is-active @endif">@include('layouts.parts.sidebar_icon', ['name' => 'settings', 'class' => 'b-sidebar-nav__icon']) <span>Настройки</span></a></li>
                        </ul>
                    </li>
                </ul>

                <div class="b-sidebar-divider"></div>

                <ul class="nav b-sidebar-nav flex-column">
                    <li class="nav-item">
                        <form action="{{ route('logout') }}" method="POST" class="m-0">
                            @csrf
                            <button type="submit" class="b-sidebar-nav__link b-sidebar-nav__link--danger">
                                @include('layouts.parts.sidebar_icon', ['name' => 'log-out', 'class' => 'b-sidebar-nav__icon'])
                                <span>Выйти</span>
                            </button>
                        </form>
                    </li>
                </ul>
            </nav>
            <!-- /.sidebar-menu -->
        </div>
        <!-- /.sidebar -->
    </aside>

    {{-- Поп-ап "Поиск" — открывается кнопкой "Поиск" в меню слева (js-sidebar-search-btn) на
         всех страницах, кроме "Все посты" (там тот же виджет уже виден прямо в шапке, см. выше,
         и на одной странице не может быть двух элементов с одинаковыми id) --}}
    @unless (request()->routeIs('posts.index'))
        @php
            $searchModalSubtitle = request()->routeIs('contacts.*')
                ? 'Ищите контакты по хэштегам, тексту или картинке'
                : 'Ищите по тексту';
        @endphp
        <div class="modal fade" id="modal-search-global" tabindex="-1" role="dialog" aria-hidden="true">
            <div class="modal-dialog modal-lg modal-dialog-centered" role="document">
                <div class="modal-content b-search-modal">
                    <div class="modal-header b-search-modal__header">
                        <div class="b-search-modal__heading">
                            <i class="fas fa-search"></i>
                            <div>
                                <h5 class="modal-title">Поиск</h5>
                                <div class="b-search-modal__subtitle">{{ $searchModalSubtitle }}</div>
                            </div>
                        </div>
                        <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
                    </div>
                    <div class="modal-body">
                        @include('layouts.parts.search_widget')
                    </div>
                </div>
            </div>
        </div>
    @endunless

    <!-- Content Wrapper. Contains page content -->
    <div class="content-wrapper @if (request()->routeIs('contacts.index')) content-wrapper--contacts @endif @if (request()->routeIs('wishlists.index') || request()->routeIs('wishlists.show')) content-wrapper--wishlists @endif">
        @yield('content')
    </div>
    <!-- /.content-wrapper -->
    <footer class="main-footer">

    </footer>

    <!-- Control Sidebar -->
    <aside class="control-sidebar control-sidebar-dark">
        <!-- Control sidebar content goes here -->
    </aside>
    <!-- /.control-sidebar -->
</div>
<!-- ./wrapper -->

<!-- jQuery -->
<script src="/admin/plugins/jquery/jquery.min.js"></script>
<!-- jQuery UI 1.11.4 -->
<script src="/admin/plugins/jquery-ui/jquery-ui.min.js"></script>
<!-- Resolve conflict in jQuery UI tooltip with Bootstrap tooltip -->
<script>
    $.widget.bridge('uibutton', $.ui.button)
</script>
<!-- Bootstrap 4 -->
<script src="/admin/plugins/bootstrap/js/bootstrap.bundle.min.js"></script>
<!-- ChartJS -->
<script src="/admin/plugins/chart.js/Chart.min.js"></script>
<!-- bs-custom-file-input -->
<script src="/admin/plugins/bs-custom-file-input/bs-custom-file-input.min.js"></script>
<!-- Sparkline -->
<script src="/admin/plugins/sparklines/sparkline.js"></script>
<!-- JQVMap -->
<script src="/admin/plugins/jqvmap/jquery.vmap.min.js"></script>
<script src="/admin/plugins/jqvmap/maps/jquery.vmap.usa.js"></script>
<!-- jQuery Knob Chart -->
<script src="/admin/plugins/jquery-knob/jquery.knob.min.js"></script>
<!-- daterangepicker -->
<script src="/admin/plugins/moment/moment.min.js"></script>
<script src="/admin/plugins/moment/locale/ru.js"></script>
<script src="/admin/plugins/daterangepicker/daterangepicker.js"></script>
<!-- Tempusdominus Bootstrap 4 -->
<script src="/admin/plugins/tempusdominus-bootstrap-4/js/tempusdominus-bootstrap-4.min.js"></script>
<!-- Inputmask (маска телефона в форме контакта, .js-contact-form__phone) -->
<script src="/admin/plugins/inputmask/jquery.inputmask.min.js"></script>
<!-- Summernote -->
<script src="/admin/plugins/summernote/summernote-bs4.min.js"></script>
<!-- overlayScrollbars -->
<script src="/admin/plugins/overlayScrollbars/js/jquery.overlayScrollbars.min.js"></script>
<!-- adminLTE App -->
<script src="/admin/dist/js/adminlte.js"></script>
<!-- AdminLTE for demo purposes -->
<script src="/admin/dist/js/demo.js"></script>
<!-- AdminLTE dashboard demo (This is only for demo purposes) -->
<script src="/admin/dist/js/pages/dashboard.js"></script>

<!-- TinyMCE (самостоятельно захостено в /admin/plugins/tinymce — было на CDN tiny.cloud,
     без интернета совсем не грузилось и рвало весь admin.js, т.к. дальше по коду
     идёт tinymce.init()) -->
<script src="/admin/plugins/tinymce/tinymce.min.js"></script>

<script src="/admin/plugins/toastr/toastr.min.js"></script>

<script src="/admin/plugins/fancybox/fancybox.umd.js"></script>

<!-- Masonry Cascading grid layout library -->
<script src="/admin/plugins/masonry-layout/masonry.pkgd.min.js"></script>
<script src="/admin/plugins/imagesloaded/imagesloaded.pkgd.min.js"></script>

<script src="/admin/admin.js?v={{ filemtime(public_path('admin/admin.js')) }}"></script>
@stack('footer-scripts')

</body>
</html>
