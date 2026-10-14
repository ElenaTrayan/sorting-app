{{-- Подключается через @include в @push('head-styles') на каждой странице раздела "Мой гардероб".
     Раздел переиспользует оформление "Моих запасов" (.b-stock-*: шапка, кнопки, вкладки, модалки) —
     поэтому my-stock.css тоже нужен; своё — в wardrobe.css --}}
<link rel="stylesheet" href="/admin/fonts/manrope/manrope.css">
<link rel="stylesheet" href="/admin/my-stock.css?v={{ filemtime(public_path('admin/my-stock.css')) }}">
<link rel="stylesheet" href="/admin/wardrobe.css?v={{ filemtime(public_path('admin/wardrobe.css')) }}">
