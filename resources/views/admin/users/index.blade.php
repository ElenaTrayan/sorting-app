@extends('layouts.admin_layout')

@section('title', 'Пользователи')

@push('navbar-breadcrumb')
    <li class="nav-item d-flex align-items-center">
        <ol class="breadcrumb b-navbar-breadcrumb">
            <li class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}">Главная</a></li>
            <li class="breadcrumb-item active">Пользователи</li>
        </ol>
    </li>
@endpush

@section('content')
<div class="content-header">
    <div class="container-fluid">
        <h1 class="m-0">Пользователи <small class="text-muted">{{ $users->total() }} из {{ $total }}</small></h1>
    </div>
</div>

<section class="content">
    <div class="container-fluid">
        <div class="card">
            <div class="card-header">
                <form method="GET" action="{{ route('admin.users.index') }}" class="form-inline">
                    <input type="search" name="q" value="{{ $search }}" class="form-control mr-2 mb-1" placeholder="Имя или email">
                    <select name="status" class="form-control mr-2 mb-1">
                        <option value="all" {{ $status === 'all' ? "selected" : "" }}>Все</option>
                        <option value="active" {{ $status === 'active' ? "selected" : "" }}>Активные</option>
                        <option value="blocked" {{ $status === 'blocked' ? "selected" : "" }}>Заблокированные</option>
                    </select>
                    <select name="service" class="form-control mr-2 mb-1">
                        <option value="">Любой сервис</option>
                        @foreach ($services as $key => $item)
                            <option value="{{ $key }}" {{ $service === $key ? "selected" : "" }}>{{ $item['name'] }}</option>
                        @endforeach
                    </select>
                    <button type="submit" class="btn btn-info mb-1">Найти</button>
                    @if ($search !== '' || $status !== 'all' || $service)
                        <a href="{{ route('admin.users.index') }}" class="btn btn-link mb-1">Сбросить</a>
                    @endif
                </form>
            </div>
            <div class="card-body p-0">
                <table class="table table-striped table-hover mb-0">
                    <thead>
                    <tr>
                        <th style="width: 1%">#</th>
                        <th>Пользователь</th>
                        <th>Сервисы подписки</th>
                        <th>Регистрация</th>
                        <th>Статус</th>
                        <th style="width: 1%"></th>
                    </tr>
                    </thead>
                    <tbody>
                    @forelse ($users as $account)
                        <tr>
                            <td>{{ $account->id }}</td>
                            <td>
                                <a href="{{ route('admin.users.show', $account) }}"><strong>{{ $account->name }}</strong></a>
                                @if ($account->isAdmin())
                                    <span class="badge badge-primary">админ</span>
                                @endif
                                <div class="text-muted text-sm">{{ $account->email }}</div>
                            </td>
                            <td>
                                @if ($account->isAdmin())
                                    <span class="text-muted">все</span>
                                @else
                                    @php $active = $account->services->filter(function ($s) { return $s->isActive(); }); @endphp
                                    @forelse ($active as $s)
                                        <span class="badge badge-info" @if ($s->expires_at) title="до {{ $s->expires_at->format('d.m.Y') }}" @endif>{{ config('subscription.services.' . $s->service . '.name', $s->service) }}</span>
                                    @empty
                                        <span class="text-muted">нет</span>
                                    @endforelse
                                @endif
                            </td>
                            <td>{{ $account->created_at ? $account->created_at->format('d.m.Y') : '—' }}</td>
                            <td>
                                @if ($account->isBlocked())
                                    <span class="badge badge-danger">заблокирован</span>
                                @else
                                    <span class="badge badge-success">активен</span>
                                @endif
                            </td>
                            <td><a href="{{ route('admin.users.show', $account) }}" class="btn btn-sm btn-outline-info">Открыть</a></td>
                        </tr>
                    @empty
                        <tr><td colspan="6" class="text-center text-muted p-4">Никого не найдено</td></tr>
                    @endforelse
                    </tbody>
                </table>
            </div>
            @if ($users->hasPages())
                <div class="card-footer">{{ $users->links('pagination::bootstrap-4') }}</div>
            @endif
        </div>
    </div>
</section>
@endsection
