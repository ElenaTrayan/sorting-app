{{-- Стили и скрипт выпадающих списков гардероба (parts/directory_select, parts/size_select) — один раз на страницу,
     сколько бы списков на ней ни было: дважды подключённый скрипт повесил бы обработчики дважды. --}}
@once
    @push('head-styles')
        <link rel="stylesheet" href="/admin/print-select.css?v={{ filemtime(public_path('admin/print-select.css')) }}">
    @endpush
    @push('footer-scripts')
        <script src="/admin/print-select.js?v={{ filemtime(public_path('admin/print-select.js')) }}"></script>
    @endpush
@endonce
