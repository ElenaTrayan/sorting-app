{{-- Подключается через @include в @push('footer-scripts') на каждой странице раздела "Мои запасы" --}}
<script src="/admin/my-stock.js?v={{ filemtime(public_path('admin/my-stock.js')) }}"></script>
