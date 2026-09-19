@extends('layouts.admin_layout')

@section('title', 'Резерв (ЧС)')

@push('head-styles')
    @include('admin.stock.parts.head_assets')
@endpush

@push('navbar-breadcrumb')
    @include('admin.stock.parts.breadcrumb', ['page' => 'Резерв (ЧС)'])
@endpush

@section('content')
<section class="content b-stock">
    <div class="container-fluid">

        <div class="b-stock-page-header">
            <div>
                <h1 class="b-stock-page-header__title">Резерв на случай ЧС</h1>
                <p class="b-stock-page-header__subtitle">Минимально необходимый запас воды, еды, медикаментов и других вещей</p>
            </div>
            <div class="b-stock-page-header__actions">
                <button type="button" class="b-stock-btn b-stock-btn--primary" data-toggle="modal" data-target="#modal-create-reserve"><i class="fas fa-plus"></i> Добавить позицию</button>
            </div>
        </div>

        <div class="b-stock-section">
            <div class="b-stock-readiness">
                <div class="b-stock-readiness__ring" style="--pct: {{ $readiness }};" data-pct="{{ $readiness }}"></div>
                <div>
                    <div style="font-weight:800;font-size:18px;">Готовность резерва: {{ $readiness }}%</div>
                    <div style="color:#9099b8;font-size:13.5px;">Рассчитывается как отношение имеющегося количества к минимально необходимому по всем позициям</div>
                </div>
            </div>
        </div>

        @forelse ($grouped as $category => $items)
            <div class="b-stock-section">
                <div class="b-stock-section__head">
                    <h2 class="b-stock-section__title">{{ \App\Models\ReserveItem::CATEGORIES[$category] ?? $category }}</h2>
                </div>
                @foreach ($items as $item)
                    <div class="b-stock-reserve-item @if ($item->needs_restock) needs-restock @endif">
                        <div class="b-stock-reserve-item__top">
                            <div>
                                <span class="b-stock-reserve-item__name">{{ $item->name }}</span>
                                @if ($item->is_critical)<span class="b-stock-badge b-stock-badge--favorite" style="margin-left:8px;">Критично</span>@endif
                            </div>
                            <div class="b-stock-more">
                                <button type="button" class="b-stock-more__btn"><i class="fas fa-ellipsis-v"></i></button>
                                <div class="b-stock-more__menu">
                                    <button type="button" data-toggle="modal" data-target="#modal-edit-reserve-{{ $item->id }}"><i class="fas fa-pen"></i> Редактировать</button>
                                    @if ($item->needs_restock)
                                        <button type="button" data-stock-post-url="{{ route('stock.reserve.add-to-shopping-list', $item) }}" data-success-message="Добавлено в список покупок" data-no-reload><i class="fas fa-shopping-basket"></i> Нужно докупить</button>
                                    @endif
                                    <button type="button" class="b-stock-more__danger" data-stock-delete-url="{{ route('stock.reserve.destroy', $item) }}" data-stock-delete-title="Удалить «{{ $item->name }}» из резерва?"><i class="fas fa-trash"></i> Удалить</button>
                                </div>
                            </div>
                        </div>
                        <div class="b-stock-reserve-item__qty">Есть: {{ $item->current_qty == floor($item->current_qty) ? (int) $item->current_qty : $item->current_qty }} {{ $item->unit }} · Минимум: {{ $item->minimum_qty == floor($item->minimum_qty) ? (int) $item->minimum_qty : $item->minimum_qty }} {{ $item->unit }}</div>
                        <div class="b-stock-progress" style="margin-top:8px;">
                            <div class="b-stock-progress__bar" style="width: {{ $item->readiness_percent }}%;"></div>
                        </div>
                        @if ($item->needs_restock)
                            <div style="margin-top:6px;font-size:12.5px;color:#dc3545;font-weight:700;">Нужно докупить {{ $item->missing_qty == floor($item->missing_qty) ? (int) $item->missing_qty : $item->missing_qty }} {{ $item->unit }}</div>
                        @endif
                    </div>
                    @include('admin.stock.parts.reserve_form_modal', ['mode' => 'edit', 'reserveItem' => $item, 'categories' => $categories])
                @endforeach
            </div>
        @empty
            <div class="b-stock-empty">
                <i class="fas fa-shield-alt b-stock-empty__icon"></i>
                <p>Резерв ещё не настроен</p>
                <button type="button" class="b-stock-btn b-stock-btn--primary" data-toggle="modal" data-target="#modal-create-reserve">Добавить первую позицию</button>
            </div>
        @endforelse

    </div>
</section>

@include('admin.stock.parts.reserve_form_modal', ['mode' => 'create', 'categories' => $categories])
@include('admin.includes.modal_delete_item')
@endsection

@push('footer-scripts')
    @include('admin.stock.parts.footer_scripts')
@endpush
