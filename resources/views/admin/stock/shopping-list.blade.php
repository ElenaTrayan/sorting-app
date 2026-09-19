@extends('layouts.admin_layout')

@section('title', 'Список покупок')

@push('head-styles')
    @include('admin.stock.parts.head_assets')
@endpush

@push('navbar-breadcrumb')
    @include('admin.stock.parts.breadcrumb', ['page' => 'Список покупок'])
@endpush

@section('content')
<section class="content b-stock">
    <div class="container-fluid">

        <div class="b-stock-page-header">
            <div>
                <h1 class="b-stock-page-header__title">Список покупок</h1>
                <p class="b-stock-page-header__subtitle">{{ $active->count() }} {{ $active->count() == 1 ? 'позиция' : 'позиций' }} осталось купить</p>
            </div>
            <div class="b-stock-page-header__actions">
                <button type="button" class="b-stock-btn b-stock-btn--primary" data-toggle="modal" data-target="#modal-create-shopping-item"><i class="fas fa-plus"></i> Добавить</button>
            </div>
        </div>

        <div class="b-stock-section">
            <div class="b-stock-section__head">
                <h2 class="b-stock-section__title">Нужно купить</h2>
            </div>
            @if ($active->isEmpty())
                <div class="b-stock-empty" style="padding:30px;">
                    <i class="fas fa-shopping-basket b-stock-empty__icon"></i>
                    <p>Список покупок пуст</p>
                </div>
            @else
                <div class="b-stock-mini-list">
                    @foreach ($active as $item)
                        <div class="b-stock-check-item">
                            <div class="b-stock-check-item__checkbox" data-stock-toggle-url="{{ route('stock.shopping-list.toggle', $item) }}">
                                <i class="fas fa-check" style="font-size:11px;"></i>
                            </div>
                            <div class="b-stock-check-item__body">
                                <div class="b-stock-check-item__title">{{ $item->name }}</div>
                                <div class="b-stock-check-item__sub">
                                    {{ $item->quantity == floor($item->quantity) ? (int) $item->quantity : $item->quantity }} {{ $item->unit }}
                                    · {{ $item->source_label }}
                                    @if ($item->note) · {{ $item->note }} @endif
                                </div>
                            </div>
                            <div class="b-stock-more">
                                <button type="button" class="b-stock-more__btn"><i class="fas fa-ellipsis-v"></i></button>
                                <div class="b-stock-more__menu">
                                    <button type="button" data-toggle="modal" data-target="#modal-edit-shopping-item-{{ $item->id }}"><i class="fas fa-pen"></i> Редактировать</button>
                                    <button type="button" class="b-stock-more__danger" data-stock-delete-url="{{ route('stock.shopping-list.destroy', $item) }}" data-stock-delete-title="Удалить «{{ $item->name }}» из списка?"><i class="fas fa-trash"></i> Удалить</button>
                                </div>
                            </div>
                        </div>
                        @include('admin.stock.parts.shopping_item_form_modal', ['mode' => 'edit', 'item' => $item])
                    @endforeach
                </div>
            @endif
        </div>

        @if ($purchased->isNotEmpty())
            <div class="b-stock-section">
                <div class="b-stock-section__head">
                    <h2 class="b-stock-section__title">Куплено недавно</h2>
                    <button type="button" class="b-stock-section__link" style="background:none;border:none;cursor:pointer;" data-stock-post-url="{{ route('stock.shopping-list.clear-purchased') }}">Очистить</button>
                </div>
                <div class="b-stock-mini-list">
                    @foreach ($purchased as $item)
                        <div class="b-stock-check-item is-done">
                            <div class="b-stock-check-item__checkbox" data-stock-toggle-url="{{ route('stock.shopping-list.toggle', $item) }}"><i class="fas fa-check" style="font-size:11px;"></i></div>
                            <div class="b-stock-check-item__body">
                                <div class="b-stock-check-item__title">{{ $item->name }}</div>
                                <div class="b-stock-check-item__sub">{{ $item->quantity == floor($item->quantity) ? (int) $item->quantity : $item->quantity }} {{ $item->unit }}</div>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        @endif

    </div>
</section>

@include('admin.stock.parts.shopping_item_form_modal', ['mode' => 'create'])
@include('admin.includes.modal_delete_item')
@endsection

@push('footer-scripts')
    @include('admin.stock.parts.footer_scripts')
@endpush
