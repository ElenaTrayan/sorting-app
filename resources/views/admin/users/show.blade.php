@extends('layouts.admin_layout')

@section('title', $account->name)

@push('navbar-breadcrumb')
    <li class="nav-item d-flex align-items-center">
        <ol class="breadcrumb b-navbar-breadcrumb">
            <li class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}">Главная</a></li>
            <li class="breadcrumb-item"><a href="{{ route('admin.users.index') }}">Пользователи</a></li>
            <li class="breadcrumb-item active">{{ $account->name }}</li>
        </ol>
    </li>
@endpush

@section('content')
<div class="content-header">
    <div class="container-fluid">
        <h1 class="m-0">
            {{ $account->name }}
            @if ($account->isAdmin()) <span class="badge badge-primary">админ</span> @endif
            @if ($account->isBlocked()) <span class="badge badge-danger">заблокирован</span> @endif
        </h1>
    </div>
</div>

<section class="content">
    <div class="container-fluid">
        <div class="row">

            <div class="col-lg-5">
                <div class="card">
                    <div class="card-header"><h3 class="card-title">Аккаунт</h3></div>
                    <div class="card-body p-0">
                        <table class="table table-sm mb-0">
                            <tbody>
                            <tr><th style="width: 40%">ID</th><td>{{ $account->id }}</td></tr>
                            <tr><th>Email</th><td>{{ $account->email }}</td></tr>
                            <tr><th>Роль</th><td>{{ $account->roles->pluck('name')->implode(', ') ?: '—' }}</td></tr>
                            <tr><th>Регистрация</th><td>{{ $account->created_at ? $account->created_at->format('d.m.Y H:i') : '—' }}</td></tr>
                            <tr><th>Email подтверждён</th><td>{{ $account->email_verified_at ? $account->email_verified_at->format('d.m.Y H:i') : 'нет' }}</td></tr>
                            </tbody>
                        </table>
                    </div>
                </div>

                <div class="card">
                    <div class="card-header"><h3 class="card-title">Данные пользователя</h3></div>
                    <div class="card-body p-0">
                        <table class="table table-sm mb-0">
                            <tbody>
                            @foreach ($usage as $label => $count)
                                <tr><td>{{ $label }}</td><td class="text-right"><span class="badge badge-secondary">{{ $count }}</span></td></tr>
                            @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>

                <div class="card {{ $account->isBlocked() ? 'card-danger' : '' }}">
                    <div class="card-header"><h3 class="card-title">Блокировка</h3></div>
                    <div class="card-body">
                        @if ($account->isBlocked())
                            <p>
                                Заблокирован {{ $account->blocked_at->format('d.m.Y H:i') }}.
                                @if ($account->blocked_reason) <br>Причина: {{ $account->blocked_reason }} @endif
                            </p>
                            <form method="POST" action="{{ route('admin.users.unblock', $account) }}">
                                @csrf
                                <button type="submit" class="btn btn-success">Разблокировать</button>
                            </form>
                        @elseif ($account->isAdmin() || $account->id === Auth::id())
                            <p class="text-muted mb-0">{{ $account->id === Auth::id() ? 'Себя заблокировать нельзя.' : 'Администратора заблокировать нельзя.' }}</p>
                        @else
                            <form method="POST" action="{{ route('admin.users.block', $account) }}" onsubmit="return confirm('Заблокировать пользователя? Он сразу потеряет доступ.');">
                                @csrf
                                <div class="form-group">
                                    <label for="block-reason">Причина (увидит пользователь при попытке войти)</label>
                                    <input type="text" id="block-reason" name="reason" maxlength="500" class="form-control">
                                </div>
                                <button type="submit" class="btn btn-danger">Заблокировать</button>
                            </form>
                        @endif
                    </div>
                </div>
            </div>

            <div class="col-lg-7">
                <div class="card">
                    <div class="card-header"><h3 class="card-title">Подписка: включённые сервисы</h3></div>
                    @if ($account->isAdmin())
                        <div class="card-body text-muted">У администратора доступны все сервисы — подписка не требуется.</div>
                    @else
                        <form method="POST" action="{{ route('admin.users.services.update', $account) }}">
                            @csrf
                            @method('PUT')
                            <div class="card-body p-0">
                                <table class="table mb-0">
                                    <thead>
                                    <tr>
                                        <th style="width: 1%"></th>
                                        <th>Сервис</th>
                                        <th style="width: 200px">Действует до</th>
                                    </tr>
                                    </thead>
                                    <tbody>
                                    @foreach ($services as $key => $service)
                                        @php $current = $userServices->get($key); @endphp
                                        <tr>
                                            <td>
                                                <input type="checkbox" name="services[]" value="{{ $key }}" id="svc-{{ $key }}" {{ $current ? 'checked' : '' }}>
                                            </td>
                                            <td>
                                                <label for="svc-{{ $key }}" class="mb-0 font-weight-bold">{{ $service['name'] }}</label>
                                                @if ($current && !$current->isActive())
                                                    <span class="badge badge-warning">срок истёк</span>
                                                @endif
                                                <div class="text-muted text-sm">{{ $service['description'] }}</div>
                                            </td>
                                            <td>
                                                <input type="date" name="expires_at[{{ $key }}]" class="form-control form-control-sm"
                                                       value="{{ $current && $current->expires_at ? $current->expires_at->format('Y-m-d') : '' }}">
                                                <div class="text-muted text-xs">пусто — без срока</div>
                                            </td>
                                        </tr>
                                    @endforeach
                                    </tbody>
                                </table>
                            </div>
                            <div class="card-footer">
                                <button type="submit" class="btn btn-info">Сохранить подписку</button>
                            </div>
                        </form>
                    @endif
                </div>
            </div>

        </div>
    </div>
</section>
@endsection
