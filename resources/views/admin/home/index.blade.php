@extends('layouts.admin_layout')

@section('title', 'Главная')

@section('content')
<div class="content-header">
    <div class="container-fluid">
        <h1 class="m-0">Админ-панель</h1>
    </div>
</div>

<section class="content">
    <div class="container-fluid">

        <div class="row">
            <div class="col-lg-3 col-6">
                <div class="small-box bg-info">
                    <div class="inner">
                        <h3>{{ $usersTotal }}</h3>
                        <p>Пользователей</p>
                    </div>
                    <div class="icon"><i class="fas fa-users"></i></div>
                    <a href="{{ route('admin.users.index') }}" class="small-box-footer">Все пользователи <i class="fas fa-arrow-circle-right"></i></a>
                </div>
            </div>
            <div class="col-lg-3 col-6">
                <div class="small-box bg-success">
                    <div class="inner">
                        <h3>{{ $usersActive }}</h3>
                        <p>Активных</p>
                    </div>
                    <div class="icon"><i class="fas fa-user-check"></i></div>
                    <a href="{{ route('admin.users.index', ['status' => 'active']) }}" class="small-box-footer">Показать <i class="fas fa-arrow-circle-right"></i></a>
                </div>
            </div>
            <div class="col-lg-3 col-6">
                <div class="small-box bg-danger">
                    <div class="inner">
                        <h3>{{ $usersBlocked }}</h3>
                        <p>Заблокировано</p>
                    </div>
                    <div class="icon"><i class="fas fa-user-slash"></i></div>
                    <a href="{{ route('admin.users.index', ['status' => 'blocked']) }}" class="small-box-footer">Показать <i class="fas fa-arrow-circle-right"></i></a>
                </div>
            </div>
            <div class="col-lg-3 col-6">
                <div class="small-box bg-warning">
                    <div class="inner">
                        <h3>{{ $usersNew }}</h3>
                        <p>Новых за 7 дней</p>
                    </div>
                    <div class="icon"><i class="fas fa-user-plus"></i></div>
                    <a href="{{ route('admin.users.index') }}" class="small-box-footer">Показать <i class="fas fa-arrow-circle-right"></i></a>
                </div>
            </div>
        </div>

        <div class="row">
            <div class="col-lg-4">
                <div class="card">
                    <div class="card-header"><h3 class="card-title">Подписка: кто чем пользуется</h3></div>
                    <div class="card-body p-0">
                        <table class="table table-sm mb-0">
                            <tbody>
                            @foreach ($services as $service)
                                <tr>
                                    <td>{{ $service['name'] }}</td>
                                    <td class="text-right"><span class="badge badge-info">{{ $service['subscribers'] }}</span></td>
                                </tr>
                            @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <div class="col-lg-4">
                <div class="card">
                    <div class="card-header">
                        <h3 class="card-title">Внешние API</h3>
                        <div class="card-tools"><a href="{{ route('admin.integrations.index') }}" class="btn btn-tool" title="Управление"><i class="fas fa-cog"></i></a></div>
                    </div>
                    <div class="card-body p-0">
                        <table class="table table-sm mb-0">
                            <tbody>
                            @foreach ($integrations as $integration)
                                <tr>
                                    <td>{{ $integration['name'] }}</td>
                                    <td class="text-right">
                                        @if (!$integration['enabled'])
                                            <span class="badge badge-secondary">отключено</span>
                                        @elseif ($integration['missing'])
                                            <span class="badge badge-warning" title="Не задано: {{ implode(', ', $integration['missing']) }}">нет ключа</span>
                                        @else
                                            <span class="badge badge-success">работает</span>
                                        @endif
                                    </td>
                                </tr>
                            @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <div class="col-lg-4">
                <div class="card">
                    <div class="card-header"><h3 class="card-title">Последние регистрации</h3></div>
                    <div class="card-body p-0">
                        <table class="table table-sm mb-0">
                            <tbody>
                            @foreach ($latestUsers as $latest)
                                <tr>
                                    <td>
                                        <a href="{{ route('admin.users.show', $latest) }}">{{ $latest->name }}</a>
                                        <div class="text-muted text-sm">{{ $latest->email }}</div>
                                    </td>
                                    <td class="text-right text-muted text-sm">{{ $latest->created_at->format('d.m.Y') }}</td>
                                </tr>
                            @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

    </div>
</section>
@endsection
