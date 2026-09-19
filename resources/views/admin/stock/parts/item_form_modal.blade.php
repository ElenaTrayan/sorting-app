{{--
    Форма добавления/редактирования товара в "Мои запасы" — по структуре
    contacts/parts/contact_form_modal.blade.php (шапка с иконкой, поля в рамке).
    $mode: create|edit, $item — StockItem при edit.
--}}
@php
    $isEdit = $mode === 'edit';
    $modalId = $isEdit ? 'modal-edit-stock-item-' . $item->id : 'modal-create-stock-item';
    $action = $isEdit ? route('stock.inventory.update', $item) : route('stock.inventory.store');
@endphp
<div class="modal fade" id="{{ $modalId }}" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered" role="document">
        <div class="modal-content b-stock-form-modal">
            <div class="modal-header b-stock-form-modal__header">
                <div class="b-stock-form-modal__icon"><i class="fas fa-box"></i></div>
                <div class="b-stock-form-modal__heading">
                    <h5 class="modal-title">{{ $isEdit ? 'Редактировать товар' : 'Добавить товар' }}</h5>
                    <div class="b-stock-form-modal__subtitle">{{ $isEdit ? 'Обновите информацию о товаре' : 'Укажите товар, который есть дома' }}</div>
                </div>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
            </div>
            <form data-stock-ajax-form data-method="{{ $isEdit ? 'PUT' : 'POST' }}" action="{{ $action }}">
                <div class="modal-body">
                    <div class="b-stock-form-row">
                        <div class="b-stock-form-field">
                            <label>Название <span class="text-danger">*</span></label>
                            <div class="b-stock-form-input">
                                <i class="fas fa-tag"></i>
                                <input type="text" name="name" required maxlength="255" placeholder="Напр. Молоко" value="{{ $isEdit ? $item->name : '' }}">
                            </div>
                        </div>
                        <div class="b-stock-form-field">
                            <label>Бренд</label>
                            <div class="b-stock-form-input">
                                <i class="fas fa-copyright"></i>
                                <input type="text" name="brand" maxlength="150" placeholder="Напр. Яготинське" value="{{ $isEdit ? $item->brand : '' }}">
                            </div>
                        </div>
                    </div>

                    <div class="b-stock-form-row">
                        <div class="b-stock-form-field">
                            <label>Категория <span class="text-danger">*</span></label>
                            <div class="b-stock-form-input">
                                <i class="fas fa-th-large"></i>
                                <select name="category" required>
                                    @foreach ($categories as $slug => $label)
                                        <option value="{{ $slug }}" @selected($isEdit && $item->category === $slug)>{{ $label }}</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>
                        <div class="b-stock-form-field">
                            <label>Место хранения <span class="text-danger">*</span></label>
                            <div class="b-stock-form-input">
                                <i class="fas fa-map-marker-alt"></i>
                                <select name="location_id" required>
                                    @foreach ($locations as $location)
                                        <option value="{{ $location->id }}" @selected($isEdit && $item->location_id === $location->id)>{{ $location->name }}</option>
                                    @endforeach
                                </select>
                            </div>
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

                    <div class="b-stock-form-row">
                        <div class="b-stock-form-field">
                            <label>Дата покупки</label>
                            <div class="b-stock-form-input">
                                <i class="far fa-calendar-alt"></i>
                                <input type="date" name="purchased_at" value="{{ $isEdit ? optional($item->purchased_at)->format('Y-m-d') : '' }}">
                            </div>
                        </div>
                        <div class="b-stock-form-field">
                            <label>Срок годности</label>
                            <div class="b-stock-form-input">
                                <i class="far fa-calendar-times"></i>
                                <input type="date" name="expires_at" value="{{ $isEdit ? optional($item->expires_at)->format('Y-m-d') : '' }}">
                            </div>
                        </div>
                    </div>

                    <div class="b-stock-form-row">
                        <div class="b-stock-form-field">
                            <label>Цена</label>
                            <div class="b-stock-form-input">
                                <i class="fas fa-hryvnia-sign"></i>
                                <input type="number" name="price" step="0.01" min="0" placeholder="0" value="{{ $isEdit ? $item->price : '' }}">
                            </div>
                        </div>
                        <div class="b-stock-form-field">
                            <label>Магазин</label>
                            <div class="b-stock-form-input">
                                <i class="fas fa-store"></i>
                                <input type="text" name="store_name" maxlength="150" placeholder="Напр. Сільпо" value="{{ $isEdit ? optional($item->store)->name : '' }}">
                            </div>
                        </div>
                    </div>

                    <div class="b-stock-form-field">
                        <label>Заметка</label>
                        <div class="b-stock-form-input">
                            <i class="fas fa-sticky-note"></i>
                            <textarea name="note" rows="2" placeholder="Необязательно">{{ $isEdit ? $item->note : '' }}</textarea>
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
