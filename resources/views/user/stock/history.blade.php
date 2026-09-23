@extends('layouts.user_layout')

@section('title', 'История покупок')

@push('head-styles')
    @include('user.stock.parts.head_assets')
@endpush

@push('navbar-breadcrumb')
    @include('user.stock.parts.breadcrumb', ['page' => 'История покупок'])
@endpush

@section('content')
<section class="content b-stock">
    <div class="container-fluid">

        <div class="b-stock-page-header">
            <div>
                <h1 class="b-stock-page-header__title">История покупок</h1>
                <p class="b-stock-page-header__subtitle">Завершённые покупки и анализ расходов</p>
            </div>
            <div class="b-stock-page-header__actions">
                <a href="{{ route('stock.history.export', request()->query()) }}" class="b-stock-btn b-stock-btn--outline"><i class="fas fa-file-export"></i> Экспорт CSV</a>
                <button type="button" class="b-stock-btn b-stock-btn--primary" data-toggle="modal" data-target="#modal-create-history"><i class="fas fa-plus"></i> Записать покупку</button>
            </div>
        </div>

        <div class="b-stock-summary">
            <div class="b-stock-summary__card b-stock-summary__card--info">
                <div class="b-stock-summary__icon"><i class="fas fa-receipt"></i></div>
                <div><div class="b-stock-summary__value">{{ $stats['count'] }}</div><div class="b-stock-summary__label">Покупок</div></div>
            </div>
            <div class="b-stock-summary__card b-stock-summary__card--success">
                <div class="b-stock-summary__icon"><i class="fas fa-hryvnia-sign"></i></div>
                <div><div class="b-stock-summary__value">{{ number_format($stats['total'], 0, ',', ' ') }}</div><div class="b-stock-summary__label">Всего потрачено, грн</div></div>
            </div>
            <div class="b-stock-summary__card b-stock-summary__card--info">
                <div class="b-stock-summary__icon"><i class="fas fa-calculator"></i></div>
                <div><div class="b-stock-summary__value">{{ number_format($stats['avg'], 0, ',', ' ') }}</div><div class="b-stock-summary__label">Средний чек, грн</div></div>
            </div>
            <div class="b-stock-summary__card b-stock-summary__card--warning">
                <div class="b-stock-summary__icon"><i class="fas fa-percent"></i></div>
                <div><div class="b-stock-summary__value">{{ $stats['discounted'] }}</div><div class="b-stock-summary__label">Покупок со скидкой</div></div>
            </div>
        </div>

        <div class="b-stock-row">
            <div class="b-stock-col">
                <div class="b-stock-section">
                    <div class="b-stock-section__head"><h2 class="b-stock-section__title">Расходы по месяцам</h2></div>
                    <div class="b-stock-chart-wrap"><canvas id="stock-chart-by-month"></canvas></div>
                </div>
            </div>
            <div class="b-stock-col">
                <div class="b-stock-section">
                    <div class="b-stock-section__head"><h2 class="b-stock-section__title">Расходы по категориям</h2></div>
                    <div class="b-stock-chart-wrap"><canvas id="stock-chart-by-category"></canvas></div>
                </div>
            </div>
        </div>

        <div class="b-stock-row">
            <div class="b-stock-col">
                <div class="b-stock-section">
                    <div class="b-stock-section__head"><h2 class="b-stock-section__title">Любимые магазины</h2></div>
                    @forelse ($byStore as $storeName => $sum)
                        @php $max = $byStore->max() ?: 1; @endphp
                        <div style="margin-bottom:10px;">
                            <div style="display:flex;justify-content:space-between;font-size:13.5px;margin-bottom:4px;"><span>{{ $storeName }}</span><strong>{{ number_format($sum, 0, ',', ' ') }} грн</strong></div>
                            <div class="b-stock-progress"><div class="b-stock-progress__bar" style="width: {{ round($sum / $max * 100) }}%;"></div></div>
                        </div>
                    @empty
                        <p style="color:#9099b8;margin:0;">Пока нет данных</p>
                    @endforelse
                </div>
            </div>
            <div class="b-stock-col">
                <div class="b-stock-section">
                    <div class="b-stock-section__head"><h2 class="b-stock-section__title">Часто покупаемые товары</h2></div>
                    @forelse ($topProducts as $name => $count)
                        @php $max = max($topProducts) ?: 1; @endphp
                        <div style="margin-bottom:10px;">
                            <div style="display:flex;justify-content:space-between;font-size:13.5px;margin-bottom:4px;"><span>{{ $name }}</span><strong>{{ $count }}×</strong></div>
                            <div class="b-stock-progress"><div class="b-stock-progress__bar" style="width: {{ round($count / $max * 100) }}%;"></div></div>
                        </div>
                    @empty
                        <p style="color:#9099b8;margin:0;">Пока нет данных</p>
                    @endforelse
                </div>
            </div>
        </div>

        <div class="b-stock-toolbar">
            <div class="b-stock-toolbar__spacer"></div>
            <button type="button" class="b-stock-btn b-stock-btn--outline" data-filter-panel-open="history"><i class="fas fa-sliders-h"></i> Фильтры</button>
        </div>

        <div class="b-stock-section" style="padding:0;overflow:hidden;">
            @if ($entries->isEmpty())
                <div class="b-stock-empty">
                    <i class="fas fa-receipt b-stock-empty__icon"></i>
                    <p>Записей пока нет</p>
                </div>
            @else
                @foreach ($entries as $entry)
                    <div class="b-stock-history-row">
                        <div>{{ $entry->purchased_at->format('d.m.Y') }}</div>
                        <div>{{ optional($entry->store)->name ?? '—' }}</div>
                        <div style="color:#9099b8;">{{ collect($entry->items)->pluck('name')->take(3)->implode(', ') }}</div>
                        <div>{{ $entry->payment_method ?: '—' }}</div>
                        <div style="font-weight:800;">{{ number_format($entry->total_amount, 0, ',', ' ') }} грн</div>
                    </div>
                @endforeach
            @endif
        </div>

    </div>
</section>

<script type="application/json" id="stock-history-data">
{!! json_encode([
    'byMonth' => ['labels' => $byMonth->keys(), 'values' => $byMonth->values()],
    'byCategory' => ['labels' => $byCategory->keys(), 'values' => $byCategory->values()],
]) !!}
</script>

@include('user.stock.parts.history_form_modal', ['stores' => $stores])
@include('user.stock.parts.history_filters')
@endsection

@push('footer-scripts')
    @include('user.stock.parts.footer_scripts')
@endpush
