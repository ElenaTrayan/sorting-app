<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Админ-панель - @yield('title')</title>

    <link rel="stylesheet" href="/admin/fonts/source-sans-pro/source-sans-pro.css">
    <link rel="stylesheet" href="/admin/fonts/nunito/nunito.css">
    <link rel="stylesheet" href="/admin/plugins/fontawesome-free/css/all.min.css">
    <link rel="stylesheet" href="/admin/dist/css/adminlte.min.css">
    <link rel="stylesheet" href="/admin/plugins/overlayScrollbars/css/OverlayScrollbars.min.css">
    <link rel="stylesheet" href="/admin/plugins/toastr/toastr.css">
    <link rel="stylesheet" href="/admin/admin-panel.css?v={{ filemtime(public_path('admin/admin-panel.css')) }}">

    @stack('head-styles')
</head>
{{-- Админ-панель — управление приложением (пользователи, подписка, внешние API). Рабочие
     разделы (посты, контакты, запасы и т.д.) живут в кабинете — resources/views/layouts/user_layout.blade.php.
     Меню и стили те же (admin-panel.css), но без поп-апа поиска, демо-виджетов и admin.js кабинета. --}}
<body class="hold-transition sidebar-mini layout-fixed">
<div class="wrapper">

    <nav class="main-header navbar navbar-expand navbar-white navbar-light">
        <ul class="navbar-nav navbar-nav-left">
            <li class="nav-item">
                <a class="nav-link" data-widget="pushmenu" href="#" role="button"><i class="fas fa-bars"></i></a>
            </li>
            @stack('navbar-breadcrumb')
        </ul>
        <ul class="navbar-nav ml-auto navbar-nav-right">
            <li class="nav-item">
                <a class="nav-link" data-widget="fullscreen" href="#" role="button">
                    <i class="fas fa-expand-arrows-alt"></i>
                </a>
            </li>
        </ul>
    </nav>

    <aside class="main-sidebar b-sidebar elevation-1">
        <a href="{{ route('admin.dashboard') }}" class="b-sidebar-brand">
            <span class="b-sidebar-brand__icon">A</span>
            <span class="b-sidebar-brand__text">Админ-панель</span>
        </a>

        <div class="sidebar b-sidebar__body">
            <div class="b-sidebar-user">
                <img src="/admin/dist/img/user2-160x160.jpg" class="b-sidebar-user__photo" alt="User Image">
                <div class="b-sidebar-user__info">
                    <div class="b-sidebar-user__name">{{ Auth::user()->name }}</div>
                    <div class="b-sidebar-user__role">Администратор</div>
                </div>
            </div>

            <nav class="mt-2">
                <div class="b-sidebar-nav__section">
                    <div class="b-sidebar-nav__section-label">Обзор</div>
                    <ul class="nav b-sidebar-nav flex-column" role="menu">
                        <li class="nav-item">
                            <a href="{{ route('admin.dashboard') }}" class="b-sidebar-nav__link @if (request()->routeIs('admin.dashboard')) is-active @endif">
                                @include('layouts.parts.sidebar_icon', ['name' => 'home', 'class' => 'b-sidebar-nav__icon'])
                                <span>Главная</span>
                            </a>
                        </li>
                    </ul>
                </div>

                <div class="b-sidebar-nav__section">
                    <div class="b-sidebar-nav__section-label">Управление</div>
                    <ul class="nav b-sidebar-nav flex-column" role="menu">
                        <li class="nav-item">
                            <a href="{{ route('admin.users.index') }}" class="b-sidebar-nav__link @if (request()->routeIs('admin.users.*')) is-active @endif">
                                @include('layouts.parts.sidebar_icon', ['name' => 'users', 'class' => 'b-sidebar-nav__icon'])
                                <span>Пользователи</span>
                            </a>
                        </li>
                        <li class="nav-item">
                            <a href="{{ route('admin.integrations.index') }}" class="b-sidebar-nav__link @if (request()->routeIs('admin.integrations.*')) is-active @endif">
                                @include('layouts.parts.sidebar_icon', ['name' => 'plug', 'class' => 'b-sidebar-nav__icon'])
                                <span>Внешние API</span>
                            </a>
                        </li>
                    </ul>
                </div>

                <div class="b-sidebar-divider"></div>

                <ul class="nav b-sidebar-nav flex-column">
                    <li class="nav-item">
                        <a href="{{ route('cabinet.home') }}" class="b-sidebar-nav__link">
                            @include('layouts.parts.sidebar_icon', ['name' => 'layout-dashboard', 'class' => 'b-sidebar-nav__icon'])
                            <span>Мой кабинет</span>
                        </a>
                    </li>
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
        </div>
    </aside>

    <div class="content-wrapper">
        @yield('content')
    </div>
    <footer class="main-footer"></footer>
</div>

<script src="/admin/plugins/jquery/jquery.min.js"></script>
<script src="/admin/plugins/bootstrap/js/bootstrap.bundle.min.js"></script>
<script src="/admin/plugins/overlayScrollbars/js/jquery.overlayScrollbars.min.js"></script>
<script src="/admin/dist/js/adminlte.js"></script>
<script src="/admin/plugins/toastr/toastr.min.js"></script>
<script>
    @if (session('success'))
        toastr.success(@json(session('success')));
    @endif
    @if ($errors->any())
        toastr.error(@json($errors->first()));
    @endif
</script>
@stack('footer-scripts')

</body>
</html>
