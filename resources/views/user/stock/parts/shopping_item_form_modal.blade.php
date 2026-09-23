@php
    $isEdit = $mode === 'edit';
    $modalId = $isEdit ? 'modal-edit-shopping-item-' . $item->id : 'modal-create-shopping-item';
    $action = $isEdit ? route('stock.shopping-list.update', $item) : route('stock.shopping-list.store');
@endphp
<div class="modal fade" id="{{ $modalId }}" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered" role="document">
        <div class="modal-content b-stock-form-modal">
            <div class="modal-header b-stock-form-modal__header">
                <div class="b-stock-form-modal__icon"><i class="fas fa-shopping-basket"></i></div>
                <div class="b-stock-form-modal__heading">
                    <h5 class="modal-title">{{ $isEdit ? 'Редактировать покупку' : 'Добавить в список покупок' }}</h5>
                </div>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
            </div>
            <form data-stock-ajax-form data-method="{{ $isEdit ? 'PUT' : 'POST' }}" action="{{ $action }}">
                <div class="modal-body">
                    <div class="b-stock-form-field">
                        <label>Название <span class="text-danger">*</span></label>
                        <div class="b-stock-form-input">
                            <i class="fas fa-tag"></i>
                            <input type="text" name="name" required maxlength="255" value="{{ $isEdit ? $item->name : '' }}">
                        </div>
                    </div>
                    <div class="b-stock-form-row">
                        <div class="b-stock-form-field">
                            <label>Количество <span class="text-danger">*</span></label>
                            <div class="b-stock-form-input">
                                <i class="fas fa-cubes"></i>
                                <input type="number" name="quantity" step="0.01" min="0" required value="{{ $isEdit ? $item->quantity : 1 }}">
                            </div>
                        </div>
                        <div class="b-stock-form-field">
                            <label>Единица <span class="text-danger">*</span></label>
                            <div class="b-stock-form-input">
                                <i class="fas fa-balance-scale"></i>
                                <select name="unit" required>
                                    @foreach (['шт', 'кг', 'г', 'л', 'мл', 'уп'] as $unit)
                                        <option value="{{ $unit }}" @selected($isEdit && $item->unit === $unit)>{{ $unit }}</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>
                    </div>
                    <div class="b-stock-form-field">
                        <label>Заметка</label>
                        <div class="b-stock-form-input">
                            <i class="fas fa-sticky-note"></i>
                            <input type="text" name="note" maxlength="255" value="{{ $isEdit ? $item->note : '' }}">
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
