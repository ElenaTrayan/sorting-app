@extends('layouts.admin_layout')

@section('title', $integration['name'])

@push('navbar-breadcrumb')
    <li class="nav-item d-flex align-items-center">
        <ol class="breadcrumb b-navbar-breadcrumb">
            <li class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}">Главная</a></li>
            <li class="breadcrumb-item"><a href="{{ route('admin.integrations.index') }}">Внешние API</a></li>
            <li class="breadcrumb-item active">{{ $integration['name'] }}</li>
        </ol>
    </li>
@endpush

@section('content')
<div class="content-header">
    <div class="container-fluid">
        <h1 class="m-0">{{ $integration['name'] }}</h1>
    </div>
</div>

<section class="content">
    <div class="container-fluid">
        <div class="card" style="max-width: 720px;">
            <form method="POST" action="{{ route('admin.integrations.update', $key) }}" autocomplete="off">
                @csrf
                @method('PUT')
                <div class="card-body">
                    <p class="text-muted">{{ $integration['description'] }}</p>

                    <div class="form-group">
                        <input type="hidden" name="is_enabled" value="0">
                        <div class="custom-control custom-switch">
                            <input type="checkbox" class="custom-control-input" id="is_enabled" name="is_enabled" value="1" {{ $enabled ? 'checked' : '' }}>
                            <label class="custom-control-label" for="is_enabled">Интеграция включена</label>
                        </div>
                        <small class="text-muted">Выключенный сервис приложение не вызывает — связанные функции сообщают, что отключены.</small>
                    </div>

                    @foreach ($integration['fields'] as $field => $meta)
                        <div class="form-group">
                            <label for="cred-{{ $field }}">{{ $meta['label'] }}</label>
                            <input type="{{ !empty($meta['secret']) ? 'password' : 'text' }}" id="cred-{{ $field }}"
                                   name="credentials[{{ $field }}]" class="form-control" autocomplete="new-password"
                                   placeholder="{{ $sources[$field] ? 'Задан — оставьте пустым, чтобы не менять' : 'Не задан' }}">
                            <small class="text-muted">
                                Сейчас:
                                @if ($sources[$field] === 'panel') значение из панели
                                @elseif ($sources[$field] === 'env') значение из .env
                                @else не задано @endif
                            </small>
                            @if ($sources[$field] === 'panel')
                                <div class="custom-control custom-checkbox mt-1">
                                    <input type="checkbox" class="custom-control-input" id="clear-{{ $field }}" name="clear[]" value="{{ $field }}">
                                    <label class="custom-control-label" for="clear-{{ $field }}">Удалить значение из панели (вернуться к .env)</label>
                                </div>
                            @endif
                        </div>
                    @endforeach
                </div>
                <div class="card-footer">
                    <button type="submit" class="btn btn-info">Сохранить</button>
                    <a href="{{ route('admin.integrations.index') }}" class="btn btn-link">Назад</a>
                </div>
            </form>
        </div>
    </div>
</section>
@endsection
