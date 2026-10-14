{{--
    Конструктор образа (создание и редактирование): слева — данные образа и выбранные вещи,
    справа — все вещи пользователя (клик добавляет/убирает). "Из постов" создаёт вещь "нужно купить"
    с картинкой поста и сразу кладёт её в образ. Логика — wardrobe.js (initOutfitBuilder).
--}}
@extends('layouts.user_layout')

@section('title', $outfit->exists ? 'Редактировать образ' : 'Новый образ')

@push('head-styles')
    @include('user.wardrobe.parts.head_assets')
@endpush

@push('navbar-breadcrumb')
    @include('user.wardrobe.parts.breadcrumb', [
        'parent' => ['Образы', route('wardrobe.outfits.index')],
        'page' => $outfit->exists ? $outfit->name : 'Новый образ',
    ])
@endpush

@section('content')
@php
    use App\Models\WardrobeItem;
    use App\Models\WardrobeOutfit;
    $isEdit = $outfit->exists;
    $canPickFromPosts = auth()->user()->hasService('posts');
    $backUrl = $isEdit ? route('wardrobe.outfits.show', $outfit) : route('wardrobe.outfits.index');
@endphp
<section class="content b-stock b-wardrobe">
    <div class="container-fluid">

        <div class="b-stock-page-header">
            <div>
                <h1 class="b-stock-page-header__title">{{ $isEdit ? 'Редактировать образ' : 'Новый образ' }}</h1>
                <p class="b-stock-page-header__subtitle">Выберите вещи справа — они появятся в образе</p>
            </div>
        </div>

        <form class="b-wardrobe-builder js-wardrobe-outfit-form"
              action="{{ $isEdit ? route('wardrobe.outfits.update', $outfit) : route('wardrobe.outfits.store') }}"
              data-method="{{ $isEdit ? 'PUT' : 'POST' }}"
              data-selected="{{ json_encode($selectedIds) }}"
              data-item-store-url="{{ route('wardrobe.items.store') }}">
            <input type="hidden" name="remove_image" value="0">
            {{-- фото повернули/отразили кнопками под превью — применяется при сохранении (WardrobeImage::transform) --}}
            <input type="hidden" name="image_rotate" value="0">
            <input type="hidden" name="image_flip" value="0">

            <div class="b-wardrobe-builder__main">
                <div class="b-stock-section">
                    <div class="b-stock-form-field">
                        <label>Название <span class="text-danger">*</span></label>
                        <div class="b-stock-form-input">
                            <i class="fas fa-tag"></i>
                            <input type="text" name="name" required maxlength="255" placeholder="Напр. Прогулка в выходной" value="{{ $outfit->name }}">
                        </div>
                    </div>

                    <div class="b-stock-form-field">
                        <label>Вещи в образе <span class="text-danger">*</span></label>
                        <div class="b-wardrobe-selected js-wardrobe-selected"></div>
                        <div class="b-wardrobe-selected__empty js-wardrobe-selected-empty">Пока ничего не выбрано</div>
                    </div>

                    <div class="b-stock-form-field">
                        <label>Сезон</label>
                        <div class="b-wardrobe-choice b-wardrobe-choice--wrap">
                            @foreach (WardrobeOutfit::SEASONS as $slug => $label)
                                <label class="b-wardrobe-choice__item">
                                    <input type="checkbox" name="seasons[]" value="{{ $slug }}" @if (in_array($slug, $outfit->seasons ?: [], true)) checked @endif>
                                    <span>{{ $label }}</span>
                                </label>
                            @endforeach
                        </div>
                    </div>

                    <div class="b-stock-form-field">
                        <label>Комфортная температура, °C</label>
                        <div class="b-stock-form-row">
                            <div class="b-stock-form-input">
                                <i class="fas fa-temperature-low"></i>
                                <input type="number" name="temp_min" min="-60" max="60" placeholder="от" value="{{ $outfit->temp_min }}">
                            </div>
                            <div class="b-stock-form-input">
                                <i class="fas fa-temperature-high"></i>
                                <input type="number" name="temp_max" min="-60" max="60" placeholder="до" value="{{ $outfit->temp_max }}">
                            </div>
                        </div>
                    </div>

                    <div class="b-stock-form-field">
                        <label>Кому подходит</label>
                        <div class="b-wardrobe-choice b-wardrobe-choice--wrap">
                            @foreach (WardrobeItem::BODY_TYPES as $slug => $label)
                                <label class="b-wardrobe-choice__item">
                                    <input type="checkbox" name="body_types[]" value="{{ $slug }}" @if (in_array($slug, $outfit->body_types ?: [], true)) checked @endif>
                                    <span>{{ $label }}</span>
                                </label>
                            @endforeach
                        </div>
                    </div>

                    <div class="b-stock-form-field">
                        <label>Фото образа</label>
                        <div class="b-wardrobe-outfit-photo">
                            <div class="b-wardrobe-photo b-wardrobe-photo--small js-wardrobe-photo @if ($outfit->image_url) has-image @endif"
                                 @if ($outfit->image_url) style="background-image:url('{{ $outfit->image_url }}')" @endif>
                                <i class="fas fa-camera"></i>
                            </div>
                            <div>
                                <label class="b-stock-btn b-stock-btn--outline b-stock-btn--sm">
                                    <i class="fas fa-upload"></i> Загрузить фото
                                    <input type="file" name="image" accept="image/jpeg,image/png,image/gif,image/webp,image/avif" hidden>
                                </label>
                                <div class="b-wardrobe-form__photo-tools js-wardrobe-photo-tools" @if (!$outfit->image_url) style="display:none;" @endif>
                                    <button type="button" class="b-wardrobe-form__photo-tool js-wardrobe-photo-rotate"><i class="fas fa-redo"></i> Повернуть</button>
                                    <button type="button" class="b-wardrobe-form__photo-tool js-wardrobe-photo-flip"><i class="fas fa-arrows-alt-h"></i> Отразить</button>
                                </div>
                                <button type="button" class="b-wardrobe-form__photo-remove js-wardrobe-photo-remove" @if (!$outfit->image_url) style="display:none;" @endif>Убрать фото</button>
                                <div class="b-wardrobe-hint">Необязательно: без фото на карточке будет коллаж из вещей</div>
                            </div>
                        </div>
                    </div>

                    <div class="b-stock-form-field">
                        <label>Заметка</label>
                        <div class="b-stock-form-input">
                            <i class="fas fa-sticky-note"></i>
                            <textarea name="note" rows="2" maxlength="2000" placeholder="Необязательно">{{ $outfit->note }}</textarea>
                        </div>
                    </div>

                    <div class="b-wardrobe-builder__actions">
                        <a href="{{ $backUrl }}" class="b-stock-btn b-stock-btn--outline">Отмена</a>
                        <button type="submit" class="b-stock-btn b-stock-btn--primary">{{ $isEdit ? 'Сохранить' : 'Создать образ' }}</button>
                    </div>
                </div>
            </div>

            <div class="b-wardrobe-builder__picker">
                <div class="b-stock-section">
                    <div class="b-stock-section__head">
                        <h2 class="b-stock-section__title">Вещи</h2>
                        @if ($canPickFromPosts)
                            <button type="button" class="b-stock-btn b-stock-btn--outline b-stock-btn--sm js-wardrobe-add-from-posts"><i class="far fa-images"></i> Из постов</button>
                        @endif
                    </div>

                    <div class="b-stock-toolbar">
                        <div class="b-stock-toolbar__search">
                            <i class="fas fa-search"></i>
                            <input type="text" class="js-wardrobe-tile-search" placeholder="Поиск по названию">
                        </div>
                        <select class="b-wardrobe-select js-wardrobe-tile-status">
                            <option value="">Все вещи</option>
                            @foreach (WardrobeItem::STATUSES as $slug => $label)
                                <option value="{{ $slug }}">{{ $label }}</option>
                            @endforeach
                        </select>
                        <select class="b-wardrobe-select js-wardrobe-tile-category">
                            <option value="">Все категории</option>
                            @foreach (\App\Models\WardrobeCategory::options() as $slug => $label)
                                <option value="{{ $slug }}">{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div class="b-wardrobe-tiles js-wardrobe-tiles">
                        @foreach ($items as $item)
                            <button type="button" class="b-wardrobe-tile js-wardrobe-tile"
                                    data-id="{{ $item->id }}"
                                    data-name="{{ $item->name }}"
                                    data-status="{{ $item->status }}"
                                    data-category="{{ $item->category }}"
                                    data-image="{{ $item->image_url }}">
                                <span class="b-wardrobe-tile__photo" @if ($item->image_url) style="background-image:url('{{ $item->image_url }}')" @endif>
                                    @if (!$item->image_url)<i class="fas fa-tshirt"></i>@endif
                                    @if ($item->isWishlist())<span class="b-wardrobe-badge b-wardrobe-badge--wishlist">Купить</span>@endif
                                    <span class="b-wardrobe-tile__check"><i class="fas fa-check"></i></span>
                                </span>
                                <span class="b-wardrobe-tile__name">{{ $item->name }}</span>
                            </button>
                        @endforeach
                    </div>
                    <div class="b-wardrobe-tiles__empty js-wardrobe-tiles-empty" @if ($items->isNotEmpty()) style="display:none;" @endif>
                        Подходящих вещей нет. Добавьте одежду в разделе
                        <a href="{{ route('wardrobe.items.index') }}">«Одежда»</a>@if ($canPickFromPosts) или возьмите картинку из постов@endif.
                    </div>
                </div>
            </div>
        </form>

    </div>
</section>

@if ($canPickFromPosts)
    @include('user.wardrobe.parts.post_picker')

    {{-- вещь "нужно купить" из выбранной картинки поста: название и категория --}}
    <div class="modal fade" id="modal-wardrobe-quick-item" tabindex="-1" role="dialog" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered" role="document">
            <div class="modal-content b-stock-form-modal">
                <div class="modal-header b-stock-form-modal__header">
                    <div class="b-stock-form-modal__icon"><i class="fas fa-shopping-bag"></i></div>
                    <div class="b-stock-form-modal__heading">
                        <h5 class="modal-title">Вещь из поста</h5>
                        <div class="b-stock-form-modal__subtitle">Попадёт в список «Нужно купить» и в этот образ</div>
                    </div>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
                </div>
                <form class="js-wardrobe-quick-item-form">
                    <div class="modal-body">
                        <div class="b-wardrobe-outfit-photo">
                            <div class="b-wardrobe-photo b-wardrobe-photo--small has-image js-wardrobe-quick-item-photo"></div>
                            <div style="flex:1;min-width:0;">
                                <div class="b-stock-form-field">
                                    <label>Название <span class="text-danger">*</span></label>
                                    <div class="b-stock-form-input">
                                        <i class="fas fa-tag"></i>
                                        <input type="text" name="name" required maxlength="255">
                                    </div>
                                </div>
                                <div class="b-stock-form-field">
                                    <label>Категория</label>
                                    <div class="b-stock-form-input">
                                        <i class="fas fa-th-large"></i>
                                        <select name="category">
                                            @foreach (\App\Models\WardrobeCategory::options() as $slug => $label)
                                                <option value="{{ $slug }}">{{ $label }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer b-stock-form-modal__footer">
                        <button type="button" class="btn btn-secondary" data-dismiss="modal">Отмена</button>
                        <button type="submit" class="btn btn-primary">Добавить в образ</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endif
@endsection

@push('footer-scripts')
    @include('user.wardrobe.parts.footer_scripts')
@endpush
