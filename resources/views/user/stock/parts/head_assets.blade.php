{{-- Подключается через @include в @push('head-styles') на каждой странице раздела "Мои запасы" --}}
<link rel="stylesheet" href="/admin/fonts/manrope/manrope.css">
<link rel="stylesheet" href="/admin/my-stock.css?v={{ filemtime(public_path('admin/my-stock.css')) }}">
