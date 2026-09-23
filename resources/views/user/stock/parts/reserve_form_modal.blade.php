@php
    $isEdit = $mode === 'edit';
    $modalId = $isEdit ? 'modal-edit-reserve-' . $reserveItem->id : 'modal-create-reserve';
    $action = $isEdit ? route('stock.reserve.update', $reserveItem) : route('stock.reserve.store');
@endphp
<div class="modal fade" id="{{ $modalId }}" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered" role="document">
        <div class="modal-content b-stock-form-modal">
            <div class="modal-header b-stock-form-modal__header">
                <div class="b-stock-form-modal__icon"><i class="fas fa-shield-alt"></i></div>
                <div class="b-stock-form-modal__heading"><h5 class="modal-title">{{ $isEdit ? 'Редактировать позицию резерва' : 'Добавить в резерв' }}</h5></div>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
            </div>
            <form data-stock-ajax-form data-method="{{ $isEdit ? 'PUT' : 'POST' }}" action="{{ $action }}">
                <div class="modal-body">
                    <div class="b-stock-form-field">
                        <label>Название <span class="text-danger">*</span></label>
                        <div class="b-stock-form-input"><i class="fas fa-tag"></i><input type="text" name="name" required maxlength="150" value="{{ $isEdit ? $reserveItem->name : '' }}"></div>
                    </div>
                    <div class="b-stock-form-row">
                        <div class="b-stock-form-field">
                            <label>Категория <span class="text-danger">*</span></label>
                            <div class="b-stock-form-input">
                                <i class="fas fa-th-large"></i>
                                <select name="category" required>
                                    @foreach ($categories as $value => $label)
                                        <option value="{{ $value }}" @selected(($reserveItem->category ?? 'other') === $value)>{{ $label }}</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>
                        <div class="b-stock-form-field">
                            <label>Единица <span class="text-danger">*</span></label>
                            <div class="b-stock-form-input">
                                <i class="fas fa-balance-scale"></i>
                                <select name="unit" required>
                                    @foreach (['шт', 'л', 'кг', 'уп'] as $unit)
                                        <option value="{{ $unit }}" @selected($isEdit && $reserveItem->unit === $unit)>{{ $unit }}</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>
                    </div>
                    <div class="b-stock-form-row">
                        <div class="b-stock-form-field">
                            <label>Минимум <span class="text-danger">*</span></label>
                            <div class="b-stock-form-input"><i class="fas fa-arrow-down"></i><input type="number" name="minimum_qty" step="0.01" min="0" required value="{{ $isEdit ? $reserveItem->minimum_qty : '' }}"></div>
                        </div>
                        <div class="b-stock-form-field">
                            <label>Есть сейчас <span class="text-danger">*</span></label>
                            <div class="b-stock-form-input"><i class="fas fa-cubes"></i><input type="number" name="current_qty" step="0.01" min="0" required value="{{ $isEdit ? $reserveItem->current_qty : '' }}"></div>
                        </div>
                    </div>
                    <label style="display:flex;align-items:center;gap:8px;font-weight:600;font-size:14px;">
                        <input type="checkbox" name="is_critical" value="1" @checked($isEdit && $reserveItem->is_critical)> Критически важно
                    </label>
                </div>
                <div class="modal-footer b-stock-form-modal__footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Отмена</button>
                    <button type="submit" class="btn btn-primary">{{ $isEdit ? 'Сохранить' : 'Добавить' }}</button>
                </div>
            </form>
        </div>
    </div>
</div>
