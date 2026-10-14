{{--
    Форма добавления/редактирования места хранения — по образцу stock/parts/store_form_modal.blade.php.
    $mode: create|edit, $storage — WardrobeStorage при edit. Отправляет my-stock.js (data-stock-ajax-form).
--}}
@php
    $isEdit = $mode === 'edit';
    $modalId = $isEdit ? 'modal-edit-wardrobe-storage-' . $storage->id : 'modal-create-wardrobe-storage';
    $action = $isEdit ? route('wardrobe.storages.update', $storage) : route('wardrobe.storages.store');
@endphp
<div class="modal fade" id="{{ $modalId }}" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered" role="document">
        <div class="modal-content b-stock-form-modal">
            <div class="modal-header b-stock-form-modal__header">
                <div class="b-stock-form-modal__icon"><i class="fas fa-box-open"></i></div>
                <div class="b-stock-form-modal__heading">
                    <h5 class="modal-title">{{ $isEdit ? 'Редактировать место' : 'Добавить место хранения' }}</h5>
                    <div class="b-stock-form-modal__subtitle">Где лежат или висят вещи</div>
                </div>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
            </div>
            <form data-stock-ajax-form data-method="{{ $isEdit ? 'PUT' : 'POST' }}" action="{{ $action }}" class="js-wardrobe-storage-form">
                <input type="hidden" name="remove_image" value="0">
                {{-- фото повернули/отразили кнопками под превью — применяется при сохранении (WardrobeImage::transform) --}}
                <input type="hidden" name="image_rotate" value="0">
                <input type="hidden" name="image_flip" value="0">
                <div class="modal-body">
                    {{-- превью, поворот и "Убрать фото" — wardrobe.js, initPhotoField --}}
                    @php $imageUrl = $isEdit ? $storage->image_url : null; @endphp
                    <div class="b-stock-form-field">
                        <label>Фото</label>
                        <div class="b-wardrobe-outfit-photo">
                            <div class="b-wardrobe-photo b-wardrobe-photo--small b-wardrobe-photo--square js-wardrobe-photo @if ($imageUrl) has-image @endif"
                                 @if ($imageUrl) style="background-image:url('{{ $imageUrl }}')" @endif>
                                <i class="fas fa-camera"></i>
                            </div>
                            <div>
                                <label class="b-stock-btn b-stock-btn--outline b-stock-btn--sm">
                                    <i class="fas fa-upload"></i> Загрузить фото
                                    <input type="file" name="image" accept="image/jpeg,image/png,image/gif,image/webp,image/avif" hidden>
                                </label>
                                <div class="b-wardrobe-form__photo-tools js-wardrobe-photo-tools" @if (!$imageUrl) style="display:none;" @endif>
                                    <button type="button" class="b-wardrobe-form__photo-tool js-wardrobe-photo-rotate"><i class="fas fa-redo"></i> Повернуть</button>
                                    <button type="button" class="b-wardrobe-form__photo-tool js-wardrobe-photo-flip"><i class="fas fa-arrows-alt-h"></i> Отразить</button>
                                </div>
                                <button type="button" class="b-wardrobe-form__photo-remove js-wardrobe-photo-remove" @if (!$imageUrl) style="display:none;" @endif>Убрать фото</button>
                                <div class="b-wardrobe-hint">Необязательно: как выглядит ящик, коробка или органайзер</div>
                            </div>
                        </div>
                    </div>
                    <div class="b-stock-form-field">
                        <label>Название <span class="text-danger">*</span></label>
                        <div class="b-stock-form-input">
                            <i class="fas fa-tag"></i>
                            <input type="text" name="name" required maxlength="255" placeholder="Напр. Органайзер ИКЕА BAXNA" value="{{ $isEdit ? $storage->name : '' }}">
                        </div>
                    </div>
                    <div class="b-stock-form-field">
                        <label>Описание</label>
                        <div class="b-stock-form-input">
                            <i class="fas fa-sticky-note"></i>
                            <textarea name="note" rows="3" maxlength="2000" placeholder="Что это и где стоит: напр. открытый контейнер, арт. 204.743.66, шкаф в спальне, верхняя полка">{{ $isEdit ? $storage->note : '' }}</textarea>
                        </div>
                    </div>
                </div>
                <div class="modal-footer b-stock-form-modal__footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Отмена</button>
                    <button type="submit" class="btn btn-primary">{{ $isEdit ? 'Сохранить' : 'Добавить' }}</button>
                </div>
            </form>
        </div>
    </div>
</div>
