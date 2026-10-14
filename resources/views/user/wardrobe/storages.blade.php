@extends('layouts.user_layout')

@section('title', 'Места хранения')

@push('head-styles')
    @include('user.wardrobe.parts.head_assets')
@endpush

@push('navbar-breadcrumb')
    @include('user.wardrobe.parts.breadcrumb', ['page' => 'Места хранения'])
@endpush

@section('content')
<section class="content b-stock b-wardrobe">
    <div class="container-fluid">

        <div class="b-stock-page-header">
            <div>
                <h1 class="b-stock-page-header__title">Места хранения</h1>
                <p class="b-stock-page-header__subtitle">Где лежат и висят вещи: тремпель, ящик, коробка, органайзер</p>
            </div>
            <div class="b-stock-page-header__actions">
                <button type="button" class="b-stock-btn b-stock-btn--primary" data-toggle="modal" data-target="#modal-create-wardrobe-storage"><i class="fas fa-plus"></i> Добавить место</button>
            </div>
        </div>

        @include('user.wardrobe.parts.section_tabs', ['active' => 'storages'])

        @if ($storages->isEmpty())
            <div class="b-stock-empty">
                <i class="fas fa-box-open b-stock-empty__icon"></i>
                <p>Мест хранения пока нет</p>
                <button type="button" class="b-stock-btn b-stock-btn--primary" data-toggle="modal" data-target="#modal-create-wardrobe-storage">Добавить первое</button>
            </div>
        @else
            <div class="b-stock-grid">
                @foreach ($storages as $storage)
                    <div class="b-stock-store-card">
                        @if ($storage->image_url)
                            <a href="{{ $storage->image_url }}" target="_blank" rel="noopener" class="b-wardrobe-storage-photo" style="background-image:url('{{ $storage->image_url }}')" title="Открыть фото"></a>
                        @endif
                        <div class="b-stock-store-card__top">
                            @if (!$storage->image_url)
                                <div class="b-stock-store-card__logo"><i class="fas fa-box-open"></i></div>
                            @endif
                            <div style="flex:1;min-width:0;">
                                <div class="b-stock-store-card__name">{{ $storage->name }}</div>
                                <div class="b-stock-store-card__type">Вещей: {{ $storage->items_count }}</div>
                            </div>
                            <div class="b-stock-more">
                                <button type="button" class="b-stock-more__btn"><i class="fas fa-ellipsis-v"></i></button>
                                <div class="b-stock-more__menu">
                                    <button type="button" data-toggle="modal" data-target="#modal-edit-wardrobe-storage-{{ $storage->id }}"><i class="fas fa-pen"></i> Редактировать</button>
                                    <button type="button" class="b-stock-more__danger"
                                            data-stock-delete-url="{{ route('wardrobe.storages.destroy', $storage) }}"
                                            data-stock-delete-title="Удалить «{{ $storage->name }}»?"
                                            data-stock-delete-text="{{ $storage->items_count ? 'Вещи останутся в гардеробе, но место хранения у них будет не указано.' : 'Это действие нельзя отменить.' }}"><i class="fas fa-trash"></i> Удалить</button>
                                </div>
                            </div>
                        </div>

                        @if ($storage->note)
                            <div class="b-wardrobe-storage-note">{!! nl2br(e($storage->note)) !!}</div>
                        @endif

                        <div class="b-stock-store-card__footer">
                            <a href="{{ route('wardrobe.items.index', ['storage' => $storage->id]) }}" class="b-stock-btn b-stock-btn--outline b-stock-btn--sm"><i class="fas fa-tshirt"></i> Показать вещи</a>
                        </div>
                    </div>
                    @include('user.wardrobe.parts.storage_form_modal', ['mode' => 'edit', 'storage' => $storage])
                @endforeach
            </div>
        @endif

        @if ($withoutStorage)
            <p class="b-wardrobe-hint" style="margin-top:18px;">
                Без места хранения: {{ $withoutStorage }} —
                <a href="{{ route('wardrobe.items.index', ['status' => 'owned', 'storage' => 'none']) }}">показать эти вещи</a>
            </p>
        @endif

    </div>
</section>

@include('user.wardrobe.parts.storage_form_modal', ['mode' => 'create'])
@include('user.includes.modal_delete_item')
@endsection

@push('footer-scripts')
    @include('user.wardrobe.parts.footer_scripts')
@endpush
