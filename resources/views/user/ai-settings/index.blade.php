@extends('layouts.user_layout')

@section('title', 'Настройки ИИ')

{{-- Стили форм — общие с разделом "Мои запасы" (my-stock.css) --}}
@push('head-styles')
    @include('user.stock.parts.head_assets')
@endpush

@push('navbar-breadcrumb')
    <li class="nav-item d-flex align-items-center">
        <ol class="breadcrumb b-navbar-breadcrumb">
            <li class="breadcrumb-item"><a href="{{ route('cabinet.home') }}">Главная</a></li>
            <li class="breadcrumb-item active">Настройки ИИ</li>
        </ol>
    </li>
@endpush

@section('content')
<section class="content b-stock">
    <div class="container-fluid">

        <div class="b-stock-page-header">
            <div>
                <h1 class="b-stock-page-header__title">Настройки ИИ</h1>
                <p class="b-stock-page-header__subtitle">Подбор хештегов по содержимому картинки через ваш аккаунт OpenAI</p>
            </div>
        </div>

        @if (session('success'))
            <div class="alert alert-success">{{ session('success') }}</div>
        @endif

        <div class="b-stock-row">
            <div class="b-stock-col">
                <form method="POST" action="{{ route('ai-settings.update') }}" autocomplete="off">
                    @csrf
                    @method('PUT')

                    <div class="b-stock-section">
                        <div class="b-stock-section__head"><h2 class="b-stock-section__title">Ключ OpenAI API</h2></div>

                        @if ($settings->hasKey())
                            <p style="font-size:14px;margin-bottom:12px;">
                                <i class="fas fa-check-circle" style="color:#22a06b;"></i>
                                Подключён ключ <code>{{ $settings->maskedKey() }}</code>
                            </p>
                        @else
                            <p style="color:#9099b8;font-size:13px;margin-bottom:12px;">
                                Ключ не подключён — подсказываются только теги по цвету картинки.
                            </p>
                        @endif

                        <div class="b-stock-form-field">
                            <label for="api_key">{{ $settings->hasKey() ? 'Заменить ключ' : 'Ключ' }}</label>
                            <div class="b-stock-form-input">
                                <i class="fas fa-key"></i>
                                <input type="password" id="api_key" name="api_key" maxlength="300" placeholder="sk-…" autocomplete="new-password">
                            </div>
                            @error('api_key')
                                <div style="color:#e5484d;font-size:13px;margin-top:6px;">{{ $message }}</div>
                            @enderror
                            <p style="color:#9099b8;font-size:12.5px;margin:8px 0 0;">
                                При сохранении ключ проверяется в OpenAI. Хранится в зашифрованном виде и используется только на сервере.
                            </p>
                        </div>

                        <div class="b-stock-form-field">
                            <label for="model">Модель</label>
                            <div class="b-stock-form-input">
                                <i class="fas fa-microchip"></i>
                                <select id="model" name="model">
                                    @foreach ($models as $value => $label)
                                        <option value="{{ $value }}" @selected(old('model', $settings->openAiModel()) === $value)>{{ $label }}</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>

                        <div class="b-stock-form-field">
                            <label>Что делать с подобранными тегами</label>
                            @foreach ($modes as $value => $label)
                                <label style="display:flex;align-items:flex-start;gap:8px;font-weight:400;font-size:14px;color:#1f2430;margin-bottom:6px;">
                                    <input type="radio" name="tags_mode" value="{{ $value }}" style="margin-top:4px;" @checked(old('tags_mode', $settings->tags_mode ?: 'suggest') === $value)>
                                    <span>{{ $label }}</span>
                                </label>
                            @endforeach
                        </div>

                        <button type="submit" class="b-stock-btn b-stock-btn--primary" style="width:100%;">Сохранить</button>
                    </div>
                </form>

                @if ($settings->hasKey())
                    <form method="POST" action="{{ route('ai-settings.destroy-key') }}" onsubmit="return confirm('Удалить ключ OpenAI? Подбор тегов по содержимому картинки перестанет работать.');">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="b-stock-btn b-stock-btn--outline" style="width:100%;">Удалить ключ</button>
                    </form>
                @endif
            </div>

            <div class="b-stock-col">
                <div class="b-stock-section">
                    <div class="b-stock-section__head"><h2 class="b-stock-section__title">Как получить ключ</h2></div>
                    <ol style="font-size:14px;padding-left:18px;line-height:1.7;margin-bottom:12px;">
                        <li>Войдите на <a href="https://platform.openai.com" target="_blank" rel="noopener">platform.openai.com</a> (можно тем же аккаунтом, что и в ChatGPT).</li>
                        <li>Пополните баланс в разделе <b>Billing</b> — хватит $5.</li>
                        <li>Откройте <a href="https://platform.openai.com/api-keys" target="_blank" rel="noopener">API keys</a> → <b>Create new secret key</b>.</li>
                        <li>Скопируйте ключ (начинается с <code>sk-</code>) и вставьте слева.</li>
                    </ol>
                    <p style="color:#9099b8;font-size:12.5px;margin:0;">
                        <i class="fas fa-info-circle"></i>
                        Подписка ChatGPT Plus/Pro для этого не подходит: API оплачивается отдельно, напрямую OpenAI.
                        Подбор тегов к одной картинке на модели GPT-6 Luna стоит примерно десятую долю цента.
                    </p>
                </div>
            </div>
        </div>

    </div>
</section>
@endsection
