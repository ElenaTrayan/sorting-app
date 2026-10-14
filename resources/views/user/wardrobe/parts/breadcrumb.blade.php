{{-- $page — название текущей подстраницы раздела; $parent — [название, url] промежуточной (необязательно) --}}
<li class="nav-item d-flex align-items-center">
    <ol class="breadcrumb b-navbar-breadcrumb">
        <li class="breadcrumb-item"><a href="{{ route('cabinet.home') }}">Главная</a></li>
        <li class="breadcrumb-item"><a href="{{ route('wardrobe.items.index') }}">Мой гардероб</a></li>
        @if (!empty($parent))
            <li class="breadcrumb-item"><a href="{{ $parent[1] }}">{{ $parent[0] }}</a></li>
        @endif
        @if (!empty($page))
            <li class="breadcrumb-item active">{{ $page }}</li>
        @endif
    </ol>
</li>
