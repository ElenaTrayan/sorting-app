@extends('layouts.admin_layout')

@section('title', 'Внешние API')

@push('navbar-breadcrumb')
    <li class="nav-item d-flex align-items-center">
        <ol class="breadcrumb b-navbar-breadcrumb">
            <li class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}">Главная</a></li>
            <li class="breadcrumb-item active">Внешние API</li>
        </ol>
    </li>
@endpush

@section('content')
<div class="content-header">
    <div class="container-fluid">
        <h1 class="m-0">Внешние API</h1>
    </div>
</div>

<section class="content">
    <div class="container-fluid">
        <div class="card">
            <div class="card-body p-0">
                <table class="table table-striped mb-0">
                    <thead>
                    <tr>
                        <th>Сервис</th>
                        <th>Ключи</th>
                        <th>Статус</th>
                        <th style="width: 1%"></th>
                    </tr>
                    </thead>
                    <tbody>
                    @foreach ($integrations as $key => $integration)
                        <tr>
                            <td>
                                <strong>{{ $integration['name'] }}</strong>
                                <div class="text-muted text-sm">{{ $integration['description'] }}</div>
                                <a href="{{ $integration['url'] }}" target="_blank" rel="noopener" class="text-sm">{{ $integration['url'] }}</a>
                            </td>
                            <td>
                                @forelse ($integration['fields'] as $field => $meta)
                                    <div>
                                        {{ $meta['label'] }}:
                                        @if ($integration['sources'][$field] === 'panel')
                                            <span class="badge badge-success">задан в панели</span>
                                        @elseif ($integration['sources'][$field] === 'env')
                                            <span class="badge badge-info">из .env</span>
                                        @else
                                            <span class="badge badge-warning">не задан</span>
                                        @endif
                                    </div>
                                @empty
                                    <span class="text-muted">ключи не нужны</span>
                                @endforelse
                            </td>
                            <td>
                                @if ($integration['enabled'])
                                    <span class="badge badge-success">включено</span>
                                @else
                                    <span class="badge badge-secondary">отключено</span>
                                @endif
                            </td>
                            <td><a href="{{ route('admin.integrations.edit', $key) }}" class="btn btn-sm btn-outline-info">Настроить</a></td>
                        </tr>
                    @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</section>
@endsection
