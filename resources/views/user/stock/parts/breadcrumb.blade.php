{{-- $page — название текущей подстраницы раздела --}}
<li class="nav-item d-flex align-items-center">
    <ol class="breadcrumb b-navbar-breadcrumb">
        <li class="breadcrumb-item"><a href="{{ route('cabinet.home') }}">Главная</a></li>
        <li class="breadcrumb-item"><a href="{{ route('stock.overview') }}">Мои запасы</a></li>
        @if (!empty($page))
            <li class="breadcrumb-item active">{{ $page }}</li>
        @endif
    </ol>
</li>
