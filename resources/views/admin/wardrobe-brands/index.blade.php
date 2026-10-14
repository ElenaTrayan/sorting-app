@extends('layouts.admin_layout')

@section('title', 'Бренды одежды')

@push('navbar-breadcrumb')
    <li class="nav-item d-flex align-items-center">
        <ol class="breadcrumb b-navbar-breadcrumb">
            <li class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}">Главная</a></li>
            <li class="breadcrumb-item active">Бренды одежды</li>
        </ol>
    </li>
@endpush

@section('content')
<div class="content-header">
    <div class="container-fluid">
        <h1 class="m-0">Бренды одежды</h1>
        <p class="text-muted mb-0">
            Общий список — подсказки в поле «Бренд» у всех пользователей «Моего гардероба».
            Брендов: {{ $sharedBrands->count() }}, с логотипом: {{ $withLogo }}. Логотип показывается рядом с названием бренда у вещей и пинов.
        </p>
    </div>
</div>

<section class="content">
    <div class="container-fluid">
        <div class="row">
            <div class="col-lg-6">
                <div class="card">
                    <div class="card-header">
                        <h3 class="card-title">Общий список ({{ $sharedBrands->count() }})</h3>
                    </div>
                    <div class="card-body">
                        <form method="POST" action="{{ route('admin.wardrobe-brands.store') }}" enctype="multipart/form-data">
                            @csrf
                            <div class="d-flex">
                                <input type="text" name="name" class="form-control mr-2" required maxlength="150" placeholder="Название бренда" value="{{ old('name') }}">
                                <button type="submit" class="btn btn-primary text-nowrap">Добавить</button>
                            </div>
                            <div class="mt-2">
                                <label class="mb-1 font-weight-normal text-muted">Логотип (необязательно)</label>
                                <input type="file" name="image" class="form-control-file" accept=".svg,image/svg+xml,image/jpeg,image/png,image/gif,image/webp">
                            </div>
                        </form>
                    </div>
                    <div class="card-body p-0">
                        <table class="table table-striped mb-0">
                            <thead>
                            <tr>
                                <th style="width: 1%">Логотип</th>
                                <th>Бренд</th>
                                <th>Вещей</th>
                                <th style="width: 1%"></th>
                            </tr>
                            </thead>
                            <tbody>
                            @forelse ($sharedBrands as $brand)
                                <tr>
                                    <td class="align-middle text-center">
                                        @if ($brand->image_url)
                                            <img src="{{ $brand->image_url }}" class="b-brand-logo b-brand-logo--lg" alt="">
                                        @else
                                            <span class="text-muted">—</span>
                                        @endif
                                    </td>
                                    <td class="align-middle">{{ $brand->name }}</td>
                                    <td class="align-middle">{{ $brand->items_count }}</td>
                                    <td class="align-middle text-nowrap">
                                        <button type="button" class="btn btn-sm btn-outline-info js-brand-edit"
                                                data-url="{{ route('admin.wardrobe-brands.update', $brand) }}"
                                                data-name="{{ $brand->name }}"
                                                data-image="{{ $brand->image_url }}">Изменить</button>
                                        <form method="POST" action="{{ route('admin.wardrobe-brands.destroy', $brand) }}" class="d-inline"
                                              onsubmit="return confirm({{ json_encode('Удалить бренд «' . $brand->name . '»?' . ($brand->items_count ? ' У вещей (' . $brand->items_count . ') бренд будет снят.' : '')) }})">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="btn btn-sm btn-outline-danger">Удалить</button>
                                        </form>
                                    </td>
                                </tr>
                            @empty
                                <tr><td colspan="4" class="text-muted">Список пуст</td></tr>
                            @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <div class="col-lg-6">
                <div class="card">
                    <div class="card-header">
                        <h3 class="card-title">Добавлены пользователями ({{ $userBrands->count() }})</h3>
                    </div>
                    <div class="card-body p-0">
                        <table class="table table-striped mb-0">
                            <thead>
                            <tr>
                                <th>Бренд</th>
                                <th>Пользователь</th>
                                <th>Вещей</th>
                                <th style="width: 1%"></th>
                            </tr>
                            </thead>
                            <tbody>
                            @forelse ($userBrands as $brand)
                                <tr>
                                    <td>{{ $brand->name }}</td>
                                    <td>{{ optional($brand->user)->name ?? '—' }}</td>
                                    <td>{{ $brand->items_count }}</td>
                                    <td>
                                        <form method="POST" action="{{ route('admin.wardrobe-brands.share', $brand) }}">
                                            @csrf
                                            <button type="submit" class="btn btn-sm btn-outline-info text-nowrap">Сделать общим</button>
                                        </form>
                                    </td>
                                </tr>
                            @empty
                                <tr><td colspan="4" class="text-muted">Своих брендов пользователи пока не добавляли</td></tr>
                            @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

{{-- одна форма редактирования на все бренды: данные подставляет скрипт ниже по кнопке "Изменить" --}}
<div class="modal fade" id="modal-brand-edit" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered" role="document">
        <form method="POST" action="" enctype="multipart/form-data" class="modal-content">
            @csrf
            @method('PUT')
            <div class="modal-header">
                <h5 class="modal-title">Изменить бренд</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
            </div>
            <div class="modal-body">
                <div class="form-group">
                    <label>Название</label>
                    <input type="text" name="name" class="form-control" required maxlength="150">
                </div>
                <div class="form-group mb-0">
                    <label>Логотип</label>
                    <div class="d-flex align-items-center">
                        <img src="" class="b-brand-logo b-brand-logo--lg mr-3 js-brand-edit-logo" alt="">
                        <div>
                            <input type="file" name="image" class="form-control-file" accept=".svg,image/svg+xml,image/jpeg,image/png,image/gif,image/webp">
                            <small class="form-text text-muted">SVG, PNG, JPG, GIF или WebP. Не обрезается; лучше SVG или PNG с прозрачным фоном</small>
                            <label class="mt-1 mb-0 font-weight-normal js-brand-edit-remove">
                                <input type="hidden" name="remove_image" value="0">
                                <input type="checkbox" name="remove_image" value="1"> Убрать логотип
                            </label>
                        </div>
                    </div>
                </div>
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
        const modal = document.getElementById('modal-brand-edit');
        const form = modal.querySelector('form');
        const logo = modal.querySelector('.js-brand-edit-logo');
        const removeLabel = modal.querySelector('.js-brand-edit-remove');

        document.addEventListener('click', function (e) {
            const btn = e.target.closest('.js-brand-edit');
            if (!btn) {
                return;
            }
            const image = btn.getAttribute('data-image');

            form.setAttribute('action', btn.getAttribute('data-url'));
            form.elements['name'].value = btn.getAttribute('data-name');
            form.querySelector('input[type="file"]').value = '';
            form.querySelector('input[type="checkbox"]').checked = false;

            logo.style.display = image ? '' : 'none';
            logo.setAttribute('src', image || '');
            removeLabel.style.display = image ? '' : 'none';

            $(modal).modal('show');
        });
    })();
</script>
@endpush
