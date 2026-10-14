{{-- Админка → Категории одежды "Моего гардероба" (Admin\WardrobeCategoryController, App\Models\WardrobeCategory) --}}
@extends('layouts.admin_layout')

@section('title', 'Категории одежды')

@push('navbar-breadcrumb')
    <li class="nav-item d-flex align-items-center">
        <ol class="breadcrumb b-navbar-breadcrumb">
            <li class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}">Главная</a></li>
            <li class="breadcrumb-item active">Категории одежды</li>
        </ol>
    </li>
@endpush

@section('content')
<div class="content-header">
    <div class="container-fluid">
        <h1 class="m-0">Категории одежды</h1>
        <p class="text-muted mb-0">
            Общий список — поле «Категория» у вещей «Моего гардероба» у всех пользователей, в этом же порядке.
            Типы вещей — подсказки поля «Тип вещи» у пина «Одежда»: по типу определяется категория при импорте пина в гардероб.
        </p>
    </div>
</div>

<section class="content">
    <div class="container-fluid">

        <div class="card">
            <div class="card-header"><h3 class="card-title">Добавить категорию</h3></div>
            <div class="card-body">
                <form method="POST" action="{{ route('admin.wardrobe-categories.store') }}" class="form-row align-items-end">
                    @csrf
                    <div class="form-group col-md-4">
                        <label>Название</label>
                        <input type="text" name="name" class="form-control" required maxlength="100" value="{{ old('name') }}">
                    </div>
                    <div class="form-group col-md-6">
                        <label>Типы вещей (через запятую, необязательно)</label>
                        <input type="text" name="kinds" class="form-control" maxlength="5000" placeholder="Купальник, парео, плавки" value="{{ old('kinds') }}">
                    </div>
                    <div class="form-group col-md-2">
                        <button type="submit" class="btn btn-primary btn-block">Добавить</button>
                    </div>
                </form>
            </div>
        </div>

        <div class="card">
            <div class="card-header"><h3 class="card-title">Категории ({{ $categories->count() }})</h3></div>
            <div class="card-body p-0">
                <table class="table table-striped mb-0">
                    <thead>
                    <tr>
                        <th style="width: 1%">Порядок</th>
                        <th style="width: 22%">Категория</th>
                        <th>Типы вещей</th>
                        <th style="width: 90px">Вещей</th>
                        <th style="width: 1%"></th>
                    </tr>
                    </thead>
                    <tbody>
                    @foreach ($categories as $category)
                        @php
                            $used = (int)($itemCounts[$category->slug] ?? 0);
                            $kinds = $category->kinds ?: [];
                        @endphp
                        <tr>
                            <td class="align-middle text-nowrap">
                                @foreach (['up' => 'fa-arrow-up', 'down' => 'fa-arrow-down'] as $direction => $icon)
                                    <form method="POST" action="{{ route('admin.wardrobe-categories.move', $category) }}" class="d-inline">
                                        @csrf
                                        <input type="hidden" name="direction" value="{{ $direction }}">
                                        <button type="submit" class="btn btn-sm btn-outline-secondary" title="{{ $direction === 'up' ? 'Выше' : 'Ниже' }}"
                                                @if (($direction === 'up' && $loop->parent->first) || ($direction === 'down' && $loop->parent->last)) disabled @endif>
                                            <i class="fas {{ $icon }}"></i>
                                        </button>
                                    </form>
                                @endforeach
                            </td>
                            <td class="align-middle">{{ $category->name }}</td>
                            <td class="align-middle text-muted">{{ $kinds ? implode(', ', $kinds) : '—' }}</td>
                            <td class="align-middle">{{ $used }}</td>
                            <td class="align-middle text-nowrap">
                                <button type="button" class="btn btn-sm btn-outline-info js-category-edit"
                                        data-url="{{ route('admin.wardrobe-categories.update', $category) }}"
                                        data-name="{{ $category->name }}"
                                        data-kinds="{{ implode("\n", $kinds) }}">Изменить</button>
                                @if (!$category->isFallback())
                                    <form method="POST" action="{{ route('admin.wardrobe-categories.destroy', $category) }}" class="d-inline"
                                          onsubmit="return confirm({{ json_encode('Удалить категорию «' . $category->name . '»?' . ($used ? ' Вещи (' . $used . ') перейдут в «Другое».' : '')) }})">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="btn btn-sm btn-outline-danger">Удалить</button>
                                    </form>
                                @endif
                            </td>
                        </tr>
                    @endforeach
                    </tbody>
                </table>
            </div>
        </div>

    </div>
</section>

{{-- одна форма редактирования на все категории: данные подставляет скрипт ниже по кнопке "Изменить" --}}
<div class="modal fade" id="modal-category-edit" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered" role="document">
        <form method="POST" action="" class="modal-content">
            @csrf
            @method('PUT')
            <div class="modal-header">
                <h5 class="modal-title">Изменить категорию</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
            </div>
            <div class="modal-body">
                <div class="form-group">
                    <label>Название</label>
                    <input type="text" name="name" class="form-control" required maxlength="100">
                </div>
                <div class="form-group mb-0">
                    <label>Типы вещей</label>
                    <textarea name="kinds" class="form-control" rows="8" maxlength="5000"></textarea>
                    <small class="form-text text-muted">По одному в строке или через запятую</small>
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
        const modal = document.getElementById('modal-category-edit');
        const form = modal.querySelector('form');

        document.addEventListener('click', function (e) {
            const btn = e.target.closest('.js-category-edit');
            if (!btn) {
                return;
            }
            form.setAttribute('action', btn.getAttribute('data-url'));
            form.elements['name'].value = btn.getAttribute('data-name');
            form.elements['kinds'].value = btn.getAttribute('data-kinds');

            $(modal).modal('show');
        });
    })();
</script>
@endpush
