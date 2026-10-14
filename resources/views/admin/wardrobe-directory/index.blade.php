{{--
    Админка: справочник "Моего гардероба" с группами и образцами — одна страница для принтов, материалов и цветов
    (Admin\WardrobeDirectoryController). $dir — настройки справочника: route, title, noun, Noun, count, field,
    sample, swatchPrefix, groups, hex (true у цветов: вместо картинки-образца — сам цвет, у двухцветных два).
--}}
@extends('layouts.admin_layout')

@section('title', $dir['title'])

@php $hasHex = !empty($dir['hex']); @endphp

@push('head-styles')
    {{-- образец .b-print-swatch — тот же, что в списке у пользователей --}}
    <link rel="stylesheet" href="/admin/print-select.css?v={{ filemtime(public_path('admin/print-select.css')) }}">
@endpush

@push('navbar-breadcrumb')
    <li class="nav-item d-flex align-items-center">
        <ol class="breadcrumb b-navbar-breadcrumb">
            <li class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}">Главная</a></li>
            <li class="breadcrumb-item active">{{ $dir['title'] }}</li>
        </ol>
    </li>
@endpush

@section('content')
<div class="content-header">
    <div class="container-fluid">
        <h1 class="m-0">{{ $dir['title'] }}</h1>
        <p class="text-muted mb-0">
            Общий справочник — список в поле «{{ $dir['field'] }}» у вещей «Моего гардероба» и у пинов «Одежда».
            @if ($hasHex)
                {{ $dir['count'] }}: {{ $sharedTotal }}, с заданным цветом: {{ $withImage }}.
            @else
                {{ $dir['count'] }}: {{ $sharedTotal }}, с образцом: {{ $withImage }}. Без образца показывается условный узор группы.
            @endif
        </p>
    </div>
</div>

<section class="content">
    <div class="container-fluid">

        <div class="card">
            <div class="card-header"><h3 class="card-title">Добавить {{ $dir['noun'] }}</h3></div>
            <div class="card-body">
                <form method="POST" action="{{ route($dir['route'] . '.store') }}" enctype="multipart/form-data" class="form-row align-items-end">
                    @csrf
                    <div class="form-group col-md-4">
                        <label>Название</label>
                        <input type="text" name="name" class="form-control" required maxlength="150" value="{{ old('name') }}">
                    </div>
                    <div class="form-group col-md-3">
                        <label>Группа</label>
                        <select name="group" class="form-control" required>
                            @foreach ($dir['groups'] as $group => $label)
                                <option value="{{ $group }}" @if (old('group') === $group) selected @endif>{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="form-group col-md-3">
                        @if ($hasHex)
                            <label>Цвет</label>
                            <div class="d-flex align-items-center">
                                <input type="color" name="hex" value="{{ old('hex', '#c9cfe0') }}" style="width:48px;height:38px;padding:2px;">
                                <label class="mb-0 ml-3 font-weight-normal"><input type="checkbox" name="has_hex2" value="1"> второй:</label>
                                <input type="color" name="hex2" value="{{ old('hex2', '#ffffff') }}" class="ml-2" style="width:48px;height:38px;padding:2px;">
                            </div>
                        @else
                            <label>Образец ({{ $dir['sample'] }})</label>
                            <input type="file" name="image" class="form-control-file" accept="image/jpeg,image/png,image/gif,image/webp">
                        @endif
                    </div>
                    <div class="form-group col-md-2">
                        <button type="submit" class="btn btn-primary btn-block">Добавить</button>
                    </div>
                </form>
            </div>
        </div>

        @if ($userEntries->isNotEmpty())
            <div class="card">
                <div class="card-header"><h3 class="card-title">Добавлены пользователями ({{ $userEntries->count() }})</h3></div>
                <div class="card-body p-0">
                    <table class="table table-striped mb-0">
                        <thead>
                        <tr>
                            @if ($hasHex)<th style="width: 1%">Цвет</th>@endif
                            <th>{{ $dir['Noun'] }}</th>
                            <th>Пользователь</th>
                            <th>Используется</th>
                            <th style="width: 1%"></th>
                        </tr>
                        </thead>
                        <tbody>
                        @foreach ($userEntries as $entry)
                            <tr>
                                @if ($hasHex)
                                    <td>
                                        <span class="b-print-swatch b-print-swatch--lg b-print-swatch--{{ $dir['swatchPrefix'] . $entry->group }}"
                                              @if ($entry->swatch_style) style="{{ $entry->swatch_style }}" @endif></span>
                                    </td>
                                @endif
                                <td class="align-middle">{{ $entry->name }}</td>
                                <td class="align-middle">{{ optional($entry->user)->name ?? '—' }}</td>
                                <td class="align-middle">{{ $entry->items_count + $entry->post_clothes_count }}</td>
                                <td class="align-middle text-nowrap">
                                    <form method="POST" action="{{ route($dir['route'] . '.share', $entry->id) }}" class="d-inline">
                                        @csrf
                                        <button type="submit" class="btn btn-sm btn-outline-info">Сделать общим</button>
                                    </form>
                                </td>
                            </tr>
                        @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        @endif

        @foreach ($sharedByGroup as $group => $entries)
            @continue($entries->isEmpty())
            <div class="card">
                <div class="card-header"><h3 class="card-title">{{ $dir['groups'][$group] }} ({{ $entries->count() }})</h3></div>
                <div class="card-body p-0">
                    <table class="table table-striped mb-0">
                        <thead>
                        <tr>
                            <th style="width: 1%">{{ $hasHex ? 'Цвет' : 'Образец' }}</th>
                            <th>{{ $dir['Noun'] }}</th>
                            @if ($hasHex)<th style="width: 170px">Код</th>@endif
                            <th style="width: 140px">Используется</th>
                            <th style="width: 1%"></th>
                        </tr>
                        </thead>
                        <tbody>
                        @foreach ($entries as $entry)
                            @php $used = $entry->items_count + $entry->post_clothes_count; @endphp
                            <tr>
                                <td>
                                    <span class="b-print-swatch b-print-swatch--lg b-print-swatch--{{ $dir['swatchPrefix'] . $group }} @if ($entry->image_url) has-image @endif"
                                          @if ($entry->image_url) style="background-image:url('{{ $entry->image_url }}')" @elseif ($entry->swatch_style) style="{{ $entry->swatch_style }}" @endif></span>
                                </td>
                                <td class="align-middle">{{ $entry->name }}</td>
                                @if ($hasHex)<td class="align-middle text-muted">{{ implode(' + ', array_filter([$entry->hex, $entry->hex2])) ?: '—' }}</td>@endif
                                <td class="align-middle">{{ $used }}</td>
                                <td class="align-middle text-nowrap">
                                    <button type="button" class="btn btn-sm btn-outline-info js-directory-edit"
                                            data-url="{{ route($dir['route'] . '.update', $entry->id) }}"
                                            data-name="{{ $entry->name }}"
                                            data-group="{{ $group }}"
                                            data-image="{{ $entry->image_url }}"
                                            @if ($hasHex) data-hex="{{ $entry->hex }}" data-hex2="{{ $entry->hex2 }}" @endif>Изменить</button>
                                    <form method="POST" action="{{ route($dir['route'] . '.destroy', $entry->id) }}" class="d-inline"
                                          onsubmit="return confirm({{ json_encode('Удалить ' . $dir['noun'] . ' «' . $entry->name . '»?' . ($used ? ' У вещей и пинов (' . $used . ') ' . $dir['noun'] . ' будет снят.' : '')) }})">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="btn btn-sm btn-outline-danger">Удалить</button>
                                    </form>
                                </td>
                            </tr>
                        @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        @endforeach

    </div>
</section>

{{-- одна форма редактирования на все записи: данные подставляет скрипт ниже по кнопке "Изменить" --}}
<div class="modal fade" id="modal-directory-edit" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered" role="document">
        <form method="POST" action="" enctype="multipart/form-data" class="modal-content">
            @csrf
            @method('PUT')
            <div class="modal-header">
                <h5 class="modal-title">Изменить {{ $dir['noun'] }}</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
            </div>
            <div class="modal-body">
                <div class="form-group">
                    <label>Название</label>
                    <input type="text" name="name" class="form-control" required maxlength="150">
                </div>
                <div class="form-group">
                    <label>Группа</label>
                    <select name="group" class="form-control" required>
                        @foreach ($dir['groups'] as $group => $label)
                            <option value="{{ $group }}">{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
                @if ($hasHex)
                    <div class="form-group mb-0">
                        <label>Цвет</label>
                        <div class="d-flex align-items-center">
                            <input type="color" name="hex" value="#c9cfe0" style="width:56px;height:38px;padding:2px;">
                            <label class="mb-0 ml-3 font-weight-normal"><input type="checkbox" name="has_hex2" value="1"> второй цвет:</label>
                            <input type="color" name="hex2" value="#ffffff" class="ml-2" style="width:56px;height:38px;padding:2px;">
                        </div>
                        <label class="mt-2 mb-0 font-weight-normal d-block">
                            <input type="checkbox" name="no_hex" value="1"> Без образца цвета (например, «Разноцветный»)
                        </label>
                    </div>
                @else
                    <div class="form-group mb-0">
                        <label>Образец</label>
                        <div class="d-flex align-items-center">
                            <span class="b-print-swatch b-print-swatch--lg mr-3 js-directory-edit-swatch"></span>
                            <div>
                                <input type="file" name="image" class="form-control-file" accept="image/jpeg,image/png,image/gif,image/webp">
                                <small class="form-text text-muted">Обрежется до квадрата из середины картинки</small>
                                <label class="mt-1 mb-0 font-weight-normal js-directory-edit-remove">
                                    <input type="hidden" name="remove_image" value="0">
                                    <input type="checkbox" name="remove_image" value="1"> Убрать образец
                                </label>
                            </div>
                        </div>
                    </div>
                @endif
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Отмена</button>
                <button type="submit" class="btn btn-primary">Сохранить</button>
            </div>
        </form>
    </div>
</div>
@endsection

@push('footer-scripts')
<script>
    (function () {
        const modal = document.getElementById('modal-directory-edit');
        const form = modal.querySelector('form');
        const swatch = modal.querySelector('.js-directory-edit-swatch');
        const removeLabel = modal.querySelector('.js-directory-edit-remove');
        const swatchPrefix = @json($dir['swatchPrefix']);
        const hasHex = @json($hasHex);

        document.addEventListener('click', function (e) {
            const btn = e.target.closest('.js-directory-edit');
            if (!btn) {
                return;
            }
            const image = btn.getAttribute('data-image');

            form.setAttribute('action', btn.getAttribute('data-url'));
            form.elements['name'].value = btn.getAttribute('data-name');
            form.elements['group'].value = btn.getAttribute('data-group');

            if (hasHex) {
                const hex = btn.getAttribute('data-hex');
                const hex2 = btn.getAttribute('data-hex2');
                form.elements['hex'].value = (hex || '#c9cfe0').toLowerCase();
                form.elements['hex2'].value = (hex2 || '#ffffff').toLowerCase();
                form.elements['has_hex2'].checked = !!hex2;
                form.elements['no_hex'].checked = !hex;
            } else {
                form.querySelector('input[type="file"]').value = '';
                form.querySelector('input[type="checkbox"]').checked = false;

                swatch.className = 'b-print-swatch b-print-swatch--lg mr-3 js-directory-edit-swatch b-print-swatch--' + swatchPrefix + btn.getAttribute('data-group') + (image ? ' has-image' : '');
                swatch.style.backgroundImage = image ? 'url("' + image + '")' : '';
                removeLabel.style.display = image ? '' : 'none';
            }

            $(modal).modal('show');
        });
    })();
</script>
@endpush
