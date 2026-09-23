@extends('layouts.user_layout')

@section('title', 'Главная')

@section('content')
<!-- Content Header (Page header) -->
<div class="content-header">
    <div class="container-fluid">
        <div class="row mb-2">
            <div class="col-sm-6">
                <h1 class="m-0">Главная</h1>
            </div><!-- /.col -->
            <div class="col-sm-6">
                <ol class="breadcrumb float-sm-right">
                    <li class="breadcrumb-item active">Главная</li>
                </ol>
            </div><!-- /.col -->
        </div><!-- /.row -->
    </div><!-- /.container-fluid -->
</div>
<!-- /.content-header -->

<!-- Main content -->
<section class="content">
    <div class="container-fluid">

        {{-- middleware EnsureServiceEnabled возвращает сюда, если раздел не входит в подписку --}}
        @if (session('service_denied'))
            <div class="alert alert-warning">{{ session('service_denied') }}</div>
        @endif

        <div class="row">
            @foreach ($services as $service)
                <div class="col-lg-4 col-md-6">
                    <div class="card @unless ($service['active']) bg-light text-muted @endunless">
                        <div class="card-body">
                            <div class="d-flex align-items-center mb-2">
                                @include('layouts.parts.sidebar_icon', ['name' => $service['icon'], 'class' => 'mr-2', 'style' => 'width:22px;height:22px'])
                                <h5 class="m-0">{{ $service['name'] }}</h5>
                            </div>
                            <p class="mb-3">{{ $service['description'] }}</p>

                            @if ($service['active'])
                                <div class="d-flex justify-content-between align-items-center">
                                    <span class="text-muted">Записей: <strong>{{ $service['count'] }}</strong></span>
                                    <a href="{{ route($service['route']) }}" class="btn btn-sm btn-outline-info">Открыть</a>
                                </div>
                            @else
                                <span class="badge badge-secondary">Не входит в вашу подписку</span>
                            @endif
                        </div>
                    </div>
                </div>
            @endforeach
        </div>

    </div><!-- /.container-fluid -->
</section>
<!-- /.content -->
@endsection
