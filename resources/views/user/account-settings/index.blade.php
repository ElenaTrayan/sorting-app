@extends('layouts.user_layout')

@section('title', 'Настройки аккаунта')

{{-- Стили форм — общие с разделом "Мои запасы" (my-stock.css), как у "Настроек ИИ" --}}
@push('head-styles')
    @include('user.stock.parts.head_assets')
@endpush

@push('navbar-breadcrumb')
    <li class="nav-item d-flex align-items-center">
        <ol class="breadcrumb b-navbar-breadcrumb">
            <li class="breadcrumb-item"><a href="{{ route('cabinet.home') }}">Главная</a></li>
            <li class="breadcrumb-item active">Настройки аккаунта</li>
        </ol>
    </li>
@endpush

@php use App\Http\Controllers\User\AccountSettingsController; @endphp

@section('content')
<section class="content b-stock">
    <div class="container-fluid">

        <div class="b-stock-page-header">
            <div>
                <h1 class="b-stock-page-header__title">Настройки аккаунта</h1>
                <p class="b-stock-page-header__subtitle">{{ Auth::user()->name }} · {{ Auth::user()->email }}</p>
            </div>
        </div>

        @if (session('success'))
            <div class="alert alert-success">{{ session('success') }}</div>
        @endif

        <div class="b-stock-row">
            <div class="b-stock-col">
                <form method="POST" action="{{ route('account-settings.update') }}">
                    @csrf
                    @method('PUT')

                    <div class="b-stock-section">
                        <div class="b-stock-section__head"><h2 class="b-stock-section__title">Часовой пояс</h2></div>

                        <div class="b-stock-form-field">
                            <label for="timezone">Ваш часовой пояс</label>
                            <div class="b-stock-form-input">
                                <i class="fas fa-globe-europe"></i>
                                <select id="timezone" name="timezone">
                                    <optgroup label="Популярные">
                                        @foreach ($popular as $value => $city)
                                            <option value="{{ $value }}" @selected(old('timezone', $timezone) === $value)>{{ $city }} ({{ AccountSettingsController::offsetLabel($value) }})</option>
                                        @endforeach
                                    </optgroup>
                                    @foreach ($others as $region => $zones)
                                        <optgroup label="{{ $region }}">
                                            @foreach ($zones as $value)
                                                <option value="{{ $value }}" @selected(old('timezone', $timezone) === $value)>{{ str_replace('_', ' ', $value) }} ({{ AccountSettingsController::offsetLabel($value) }})</option>
                                            @endforeach
                                        </optgroup>
                                    @endforeach
                                </select>
                            </div>
                            @error('timezone')
                                <div style="color:#e5484d;font-size:13px;margin-top:6px;">{{ $message }}</div>
                            @enderror
                            <p style="color:#9099b8;font-size:12.5px;margin:8px 0 0;">
                                Сейчас у вас по этому поясу: <b>{{ $localNow->format('H:i, d.m.Y') }}</b>
                            </p>
                        </div>

                        <button type="submit" class="b-stock-btn b-stock-btn--primary" style="width:100%;">Сохранить</button>
                    </div>
                </form>
            </div>

            <div class="b-stock-col">
                <div class="b-stock-section">
                    <div class="b-stock-section__head"><h2 class="b-stock-section__title">Зачем это нужно</h2></div>
                    <p style="font-size:14px;line-height:1.6;margin-bottom:8px;">
                        По часовому поясу считается, какое у вас «сегодня»: например, дата при «Постирала» в «Моём гардеробе»
                        и «вчера / 5 дн. назад». Без него после полуночи могла записаться вчерашняя дата.
                    </p>
                    <p style="color:#9099b8;font-size:12.5px;margin:0;">
                        <i class="fas fa-info-circle"></i>
                        Уже сохранённые даты и время не меняются.
                    </p>
                </div>
            </div>
        </div>

    </div>
</section>
@endsection
