@php
    $isEdit = $mode === 'edit';
    $modalId = $isEdit ? 'modal-edit-store-' . $store->id : 'modal-create-store';
    $action = $isEdit ? route('stock.stores.update', $store) : route('stock.stores.store');
@endphp
<div class="modal fade" id="{{ $modalId }}" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered" role="document">
        <div class="modal-content b-stock-form-modal">
            <div class="modal-header b-stock-form-modal__header">
                <div class="b-stock-form-modal__icon"><i class="fas fa-store"></i></div>
                <div class="b-stock-form-modal__heading">
                    <h5 class="modal-title">{{ $isEdit ? 'Редактировать магазин' : 'Добавить магазин' }}</h5>
                </div>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
            </div>
            <form data-stock-ajax-form data-method="{{ $isEdit ? 'PUT' : 'POST' }}" action="{{ $action }}">
                <div class="modal-body">
                    <div class="b-stock-form-field">
                        <label>Название <span class="text-danger">*</span></label>
                        <div class="b-stock-form-input"><i class="fas fa-store"></i><input type="text" name="name" required maxlength="150" placeholder="Напр. Сільпо" value="{{ $isEdit ? $store->name : '' }}"></div>
                    </div>
                    <div class="b-stock-form-row">
                        <div class="b-stock-form-field">
                            <label>Тип</label>
                            <div class="b-stock-form-input">
                                <i class="fas fa-tag"></i>
                                <select name="type">
                                    @foreach (\App\Models\Store::TYPES as $value => $label)
                                        <option value="{{ $value }}" @selected(($store->type ?? '') === $value)>{{ $label }}</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>
                        <div class="b-stock-form-field">
                            <label>Сайт</label>
                            <div class="b-stock-form-input"><i class="fas fa-link"></i><input type="text" name="website" maxlength="255" placeholder="https://" value="{{ $isEdit ? $store->website : '' }}"></div>
                        </div>
                    </div>
                    <div class="b-stock-form-field">
                        <label>Категории товаров (через запятую)</label>
                        <div class="b-stock-form-input"><i class="fas fa-list"></i><input type="text" name="categories" placeholder="продукты, бытовая химия" value="{{ $isEdit ? implode(', ', $store->categories_list) : '' }}"></div>
                    </div>
                    <div class="b-stock-form-row">
                        <label style="display:flex;align-items:center;gap:8px;font-weight:600;font-size:14px;"><input type="checkbox" name="delivery" value="1" @checked($isEdit && $store->delivery)> Доставка</label>
                        <label style="display:flex;align-items:center;gap:8px;font-weight:600;font-size:14px;"><input type="checkbox" name="pickup" value="1" @checked($isEdit && $store->pickup)> Самовывоз</label>
                        <label style="display:flex;align-items:center;gap:8px;font-weight:600;font-size:14px;"><input type="checkbox" name="monitor_promotions" value="1" @checked(!$isEdit || $store->monitor_promotions)> Мониторинг акций</label>
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
