{{-- Подключается через @include в @push('footer-scripts') на каждой странице раздела "Мой гардероб".
     my-stock.js — общие AJAX-формы, удаление с подтверждением, меню "..." и drawer фильтров
     (работает на страницах с .b-stock); wardrobe.js — всё своё --}}
<script src="/admin/my-stock.js?v={{ filemtime(public_path('admin/my-stock.js')) }}"></script>
<script src="/admin/wardrobe.js?v={{ filemtime(public_path('admin/wardrobe.js')) }}"></script>
