@extends('layouts.admin_layout')

@section('title', 'Настройки — Мои запасы')

@push('head-styles')
    @include('admin.stock.parts.head_assets')
@endpush

@push('navbar-breadcrumb')
    @include('admin.stock.parts.breadcrumb', ['page' => 'Настройки'])
@endpush

@section('content')
<section class="content b-stock">
    <div class="container-fluid">

        <div class="b-stock-page-header">
            <div>
                <h1 class="b-stock-page-header__title">Настройки</h1>
                <p class="b-stock-page-header__subtitle">Уведомления, магазины, валюта и интеграции раздела «Мои запасы»</p>
            </div>
        </div>

        <form data-stock-ajax-form data-method="PUT" action="{{ route('stock.settings.update') }}">
            <div class="b-stock-row">
                <div class="b-stock-col">

                    <div class="b-stock-section">
                        <div class="b-stock-section__head"><h2 class="b-stock-section__title">Уведомления</h2></div>
                        @foreach ($notificationKeys as $key => $label)
                            <label style="display:flex;align-items:center;justify-content:space-between;padding:10px 0;border-bottom:1px solid #f1f2f6;">
                                <span style="font-weight:600;font-size:14px;">{{ $label }}</span>
                                <label class="b-stock-switch">
                                    <input type="checkbox" name="notifications[{{ $key }}]" value="1" @checked($settings->notificationEnabled($key))>
                                    <span class="b-stock-switch__slider"></span>
                                </label>
                            </label>
                        @endforeach
                    </div>

                    <div class="b-stock-section">
                        <div class="b-stock-section__head"><h2 class="b-stock-section__title">Категории товаров</h2></div>
                        <p style="color:#9099b8;font-size:13px;margin-bottom:12px;">Используются при добавлении товаров в запасы, постоянные покупки и историю</p>
                        <div class="b-stock-store-card__chips">
                            @foreach ($categories as $label)
                                <span class="b-stock-chip">{{ $label }}</span>
                            @endforeach
                        </div>
                    </div>

                </div>

                <div class="b-stock-col">

                    <div class="b-stock-section">
                        <div class="b-stock-section__head"><h2 class="b-stock-section__title">Валюта и регион</h2></div>
                        <div class="b-stock-form-row">
                            <div class="b-stock-form-field">
                                <label>Валюта</label>
                                <div class="b-stock-form-input">
                                    <i class="fas fa-hryvnia-sign"></i>
                                    <select name="currency">
                                        @foreach (['UAH' => 'Гривна (UAH)', 'USD' => 'Доллар (USD)', 'EUR' => 'Евро (EUR)'] as $code => $label)
                                            <option value="{{ $code }}" @selected($settings->currency === $code)>{{ $label }}</option>
                                        @endforeach
                                    </select>
                                </div>
                            </div>
                            <div class="b-stock-form-field">
                                <label>Регион</label>
                                <div class="b-stock-form-input"><i class="fas fa-map-marker-alt"></i><input type="text" name="region" maxlength="100" placeholder="Напр. Киев" value="{{ $settings->region }}"></div>
                            </div>
                        </div>
                    </div>

                    <div class="b-stock-section">
                        <div class="b-stock-section__head"><h2 class="b-stock-section__title">Предпочитаемые магазины</h2></div>
                        @if ($stores->isEmpty())
                            <p style="color:#9099b8;">Сначала добавьте магазины в разделе <a href="{{ route('stock.stores.index') }}">«Магазины»</a></p>
                        @else
                            <div class="b-stock-store-card__chips">
                                @foreach ($stores as $store)
                                    <label style="display:flex;align-items:center;gap:6px;background:#f4f6f9;padding:5px 12px;border-radius:999px;font-size:13px;font-weight:600;">
                                        <input type="checkbox" name="preferred_store_ids[]" value="{{ $store->id }}" @checked(in_array($store->id, $settings->preferred_store_ids_list))> {{ $store->name }}
                                    </label>
                                @endforeach
                            </div>
                        @endif
                    </div>

                    <div class="b-stock-section">
                        <div class="b-stock-section__head"><h2 class="b-stock-section__title">Интеграции</h2></div>
                        <label style="display:flex;align-items:center;justify-content:space-between;padding:10px 0;border-bottom:1px solid #f1f2f6;">
                            <span>
                                <span style="font-weight:600;font-size:14px;display:block;">Email — автопоиск писем с заказами</span>
                                <span style="color:#9099b8;font-size:12.5px;">Автоматически находить заказы из интернет-магазинов по письмам</span>
                            </span>
                            <label class="b-stock-switch">
                                <input type="checkbox" name="email_integration_enabled" value="1" @checked($settings->email_integration_enabled)>
                                <span class="b-stock-switch__slider"></span>
                            </label>
                        </label>
                        <p style="color:#9099b8;font-size:12.5px;margin:10px 0 0;"><i class="fas fa-info-circle"></i> Реальное подключение почты и служб доставки появится позже — сейчас это переключатель-заготовка.</p>
                    </div>

                    <button type="submit" class="b-stock-btn b-stock-btn--primary" style="width:100%;">Сохранить настройки</button>

                </div>
            </div>
        </form>

    </div>
</section>
@endsection

@push('footer-scripts')
    @include('admin.stock.parts.footer_scripts')
@endpush
