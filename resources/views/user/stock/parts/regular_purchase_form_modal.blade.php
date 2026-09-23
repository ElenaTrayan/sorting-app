@php
    $isEdit = $mode === 'edit';
    $modalId = $isEdit ? 'modal-edit-regular-' . $regularPurchase->id : 'modal-create-regular';
    $action = $isEdit ? route('stock.regular-purchases.update', $regularPurchase) : route('stock.regular-purchases.store');
    $product = $isEdit ? $regularPurchase->product : null;
@endphp
<div class="modal fade" id="{{ $modalId }}" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered" role="document">
        <div class="modal-content b-stock-form-modal">
            <div class="modal-header b-stock-form-modal__header">
                <div class="b-stock-form-modal__icon"><i class="fas fa-repeat"></i></div>
                <div class="b-stock-form-modal__heading">
                    <h5 class="modal-title">{{ $isEdit ? 'Редактировать постоянную покупку' : 'Добавить постоянную покупку' }}</h5>
                </div>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
            </div>
            <form data-stock-ajax-form data-method="{{ $isEdit ? 'PUT' : 'POST' }}" action="{{ $action }}">
                <div class="modal-body">
                    <div class="b-stock-form-row">
                        <div class="b-stock-form-field">
                            <label>Название <span class="text-danger">*</span></label>
                            <div class="b-stock-form-input"><i class="fas fa-tag"></i><input type="text" name="name" required maxlength="255" value="{{ $product->name ?? '' }}"></div>
                        </div>
                        <div class="b-stock-form-field">
                            <label>Бренд</label>
                            <div class="b-stock-form-input"><i class="fas fa-copyright"></i><input type="text" name="brand" maxlength="150" value="{{ $product->brand ?? '' }}"></div>
                        </div>
                    </div>
                    <div class="b-stock-form-row">
                        <div class="b-stock-form-field">
                            <label>Вариант товара</label>
                            <div class="b-stock-form-input"><i class="fas fa-box-open"></i><input type="text" name="variant" maxlength="255" placeholder="Напр. Cremette" value="{{ $product->variant ?? '' }}"></div>
                        </div>
                        <div class="b-stock-form-field">
                            <label>Категория <span class="text-danger">*</span></label>
                            <div class="b-stock-form-input">
                                <i class="fas fa-th-large"></i>
                                <select name="category" required>
                                    @foreach ($categories as $slug => $label)
                                        <option value="{{ $slug }}" @selected(($product->category ?? '') === $slug)>{{ $label }}</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>
                    </div>
                    <div class="b-stock-form-row">
                        <div class="b-stock-form-field">
                            <label>Вес/объём</label>
                            <div class="b-stock-form-input"><i class="fas fa-weight-hanging"></i><input type="number" step="0.01" min="0" name="size_value" value="{{ $product->size_value ?? '' }}"></div>
                        </div>
                        <div class="b-stock-form-field">
                            <label>Единица</label>
                            <div class="b-stock-form-input">
                                <i class="fas fa-balance-scale"></i>
                                <select name="size_unit">
                                    <option value="">—</option>
                                    @foreach (['кг', 'г', 'л', 'мл', 'шт'] as $unit)
                                        <option value="{{ $unit }}" @selected(($product->size_unit ?? '') === $unit)>{{ $unit }}</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>
                    </div>
                    <div class="b-stock-form-field">
                        <label>Предпочитаемый магазин</label>
                        <div class="b-stock-form-input">
                            <i class="fas fa-store"></i>
                            <select name="preferred_store_id">
                                <option value="">—</option>
                                @foreach ($stores as $store)
                                    <option value="{{ $store->id }}" @selected($isEdit && $regularPurchase->preferred_store_id === $store->id)>{{ $store->name }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                    <div class="b-stock-form-row">
                        <div class="b-stock-form-field">
                            <label>Средняя цена</label>
                            <div class="b-stock-form-input"><i class="fas fa-hryvnia-sign"></i><input type="number" step="0.01" min="0" name="avg_price" value="{{ $isEdit ? $regularPurchase->avg_price : '' }}"></div>
                        </div>
                        <div class="b-stock-form-field">
                            <label>Последняя цена</label>
                            <div class="b-stock-form-input"><i class="fas fa-hryvnia-sign"></i><input type="number" step="0.01" min="0" name="last_price" value="{{ $isEdit ? $regularPurchase->last_price : '' }}"></div>
                        </div>
                    </div>
                    <div class="b-stock-form-row">
                        <div class="b-stock-form-field">
                            <label>Дата последней покупки</label>
                            <div class="b-stock-form-input"><i class="far fa-calendar-alt"></i><input type="date" name="last_purchased_at" value="{{ $isEdit ? optional($regularPurchase->last_purchased_at)->format('Y-m-d') : '' }}"></div>
                        </div>
                        <div class="b-stock-form-field">
                            <label>Частота покупки, дней</label>
                            <div class="b-stock-form-input"><i class="fas fa-redo"></i><input type="number" min="1" name="frequency_days" value="{{ $isEdit ? $regularPurchase->frequency_days : '' }}"></div>
                        </div>
                    </div>
                    <div class="b-stock-form-field">
                        <label>Уровень предпочтения <span class="text-danger">*</span></label>
                        <div class="b-stock-form-input">
                            <i class="fas fa-heart"></i>
                            <select name="preference" required>
                                @foreach (\App\Models\RegularPurchase::PREFERENCES as $value => $label)
                                    <option value="{{ $value }}" @selected(($regularPurchase->preference ?? 'normal') === $value)>{{ $label }}</option>
                                @endforeach
                            </select>
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
