@php
    $isEdit = $mode === 'edit';
    $modalId = $isEdit ? 'modal-edit-order' : 'modal-create-order';
    $action = $isEdit ? route('stock.orders.update', $order) : route('stock.orders.store');
@endphp
<div class="modal fade" id="{{ $modalId }}" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg" role="document">
        <div class="modal-content b-stock-form-modal">
            <div class="modal-header b-stock-form-modal__header">
                <div class="b-stock-form-modal__icon"><i class="fas fa-truck"></i></div>
                <div class="b-stock-form-modal__heading"><h5 class="modal-title">{{ $isEdit ? 'Редактировать заказ' : 'Новый заказ' }}</h5></div>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
            </div>
            <form data-stock-ajax-form data-method="{{ $isEdit ? 'PUT' : 'POST' }}" action="{{ $action }}">
                <div class="modal-body">
                    <div class="b-stock-form-row">
                        <div class="b-stock-form-field">
                            <label>Магазин</label>
                            <div class="b-stock-form-input">
                                <i class="fas fa-store"></i>
                                <select name="store_id">
                                    <option value="">—</option>
                                    @foreach ($stores as $store)
                                        <option value="{{ $store->id }}" @selected($isEdit && $order->store_id === $store->id)>{{ $store->name }}</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>
                        <div class="b-stock-form-field">
                            <label>Номер заказа</label>
                            <div class="b-stock-form-input"><i class="fas fa-hashtag"></i><input type="text" name="order_number" maxlength="100" value="{{ $isEdit ? $order->order_number : '' }}"></div>
                        </div>
                        <div class="b-stock-form-field">
                            <label>Дата заказа</label>
                            <div class="b-stock-form-input"><i class="far fa-calendar-alt"></i><input type="date" name="ordered_at" value="{{ $isEdit ? optional($order->ordered_at)->format('Y-m-d') : now()->format('Y-m-d') }}"></div>
                        </div>
                    </div>

                    <div class="b-stock-form-row">
                        <div class="b-stock-form-field">
                            <label>Сумма заказа <span class="text-danger">*</span></label>
                            <div class="b-stock-form-input"><i class="fas fa-hryvnia-sign"></i><input type="number" name="total_amount" step="0.01" min="0" required value="{{ $isEdit ? $order->total_amount : '' }}"></div>
                        </div>
                        <div class="b-stock-form-field">
                            <label>Статус оплаты <span class="text-danger">*</span></label>
                            <div class="b-stock-form-input">
                                <i class="fas fa-wallet"></i>
                                <select name="payment_status" required>
                                    @foreach (\App\Models\Order::PAYMENT_STATUSES as $value => $label)
                                        <option value="{{ $value }}" @selected(($order->payment_status ?? 'paid') === $value)>{{ $label }}</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>
                        <div class="b-stock-form-field">
                            <label>Способ оплаты</label>
                            <div class="b-stock-form-input"><i class="fas fa-credit-card"></i><input type="text" name="payment_method" maxlength="50" placeholder="Картой" value="{{ $isEdit ? $order->payment_method : '' }}"></div>
                        </div>
                    </div>

                    <div class="b-stock-form-row">
                        <div class="b-stock-form-field">
                            <label>Способ получения <span class="text-danger">*</span></label>
                            <div class="b-stock-form-input">
                                <i class="fas fa-route"></i>
                                <select name="fulfillment_method" required>
                                    @foreach (\App\Models\Order::FULFILLMENT_METHODS as $value => $label)
                                        <option value="{{ $value }}" @selected(($order->fulfillment_method ?? 'delivery') === $value)>{{ $label }}</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>
                        <div class="b-stock-form-field">
                            <label>Статус доставки <span class="text-danger">*</span></label>
                            <div class="b-stock-form-input">
                                <i class="fas fa-shipping-fast"></i>
                                <select name="delivery_status" required>
                                    @foreach (\App\Models\Order::DELIVERY_STATUSES as $value => $label)
                                        <option value="{{ $value }}" @selected(($order->delivery_status ?? 'pending') === $value)>{{ $label }}</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>
                    </div>

                    <div class="b-stock-form-row">
                        <div class="b-stock-form-field">
                            <label>Место получения / отделение</label>
                            <div class="b-stock-form-input"><i class="fas fa-map-marker-alt"></i><input type="text" name="pickup_location" maxlength="255" value="{{ $isEdit ? $order->pickup_location : '' }}"></div>
                        </div>
                        <div class="b-stock-form-field">
                            <label>Окно доставки</label>
                            <div class="b-stock-form-input"><i class="far fa-clock"></i><input type="text" name="delivery_window" maxlength="100" placeholder="Сегодня, 18:00–20:00" value="{{ $isEdit ? $order->delivery_window : '' }}"></div>
                        </div>
                    </div>

                    <div class="b-stock-form-row">
                        <div class="b-stock-form-field">
                            <label>Перевозчик</label>
                            <div class="b-stock-form-input"><i class="fas fa-truck"></i><input type="text" name="carrier" maxlength="100" value="{{ $isEdit ? $order->carrier : '' }}"></div>
                        </div>
                        <div class="b-stock-form-field">
                            <label>Номер отправления (ТТН)</label>
                            <div class="b-stock-form-input"><i class="fas fa-barcode"></i><input type="text" name="tracking_number" maxlength="100" value="{{ $isEdit ? $order->tracking_number : '' }}"></div>
                        </div>
                    </div>

                    <div class="b-stock-form-row">
                        <div class="b-stock-form-field">
                            <label>Хранение до (можно забрать до)</label>
                            <div class="b-stock-form-input"><i class="far fa-calendar-alt"></i><input type="date" name="ready_until" value="{{ $isEdit ? optional($order->ready_until)->format('Y-m-d') : '' }}"></div>
                        </div>
                        <div class="b-stock-form-field">
                            <label>Срок возврата до</label>
                            <div class="b-stock-form-input"><i class="far fa-calendar-times"></i><input type="date" name="return_deadline" value="{{ $isEdit ? optional($order->return_deadline)->format('Y-m-d') : '' }}"></div>
                        </div>
                    </div>

                    <div class="b-stock-form-field">
                        <label>Товары в заказе</label>
                        <div class="js-order-items">
                            @if ($isEdit)
                                @foreach ($order->items as $i => $item)
                                    <div class="b-stock-form-row js-order-item-row" style="align-items:center;">
                                        <div class="b-stock-form-field"><div class="b-stock-form-input"><input type="text" name="items[{{ $i }}][name]" placeholder="Название" value="{{ $item->name }}"></div></div>
                                        <div class="b-stock-form-field"><div class="b-stock-form-input"><input type="text" name="items[{{ $i }}][brand]" placeholder="Бренд" value="{{ $item->brand }}"></div></div>
                                        <div class="b-stock-form-field" style="max-width:90px;"><div class="b-stock-form-input"><input type="number" name="items[{{ $i }}][quantity]" step="0.01" min="0" placeholder="Кол-во" value="{{ $item->quantity }}"></div></div>
                                        <div class="b-stock-form-field" style="max-width:90px;"><div class="b-stock-form-input"><input type="text" name="items[{{ $i }}][unit]" placeholder="Ед." value="{{ $item->unit }}"></div></div>
                                        <div class="b-stock-form-field" style="max-width:110px;"><div class="b-stock-form-input"><input type="number" name="items[{{ $i }}][price]" step="0.01" min="0" placeholder="Цена" value="{{ $item->price }}"></div></div>
                                        <button type="button" class="js-order-remove-item b-stock-more__btn" style="flex-shrink:0;"><i class="fas fa-times"></i></button>
                                    </div>
                                @endforeach
                            @endif
                        </div>
                        <div class="js-order-item-template" style="display:none;">
                            <div class="b-stock-form-row js-order-item-row" style="align-items:center;">
                                <div class="b-stock-form-field"><div class="b-stock-form-input"><input type="text" name="items[__INDEX__][name]" placeholder="Название"></div></div>
                                <div class="b-stock-form-field"><div class="b-stock-form-input"><input type="text" name="items[__INDEX__][brand]" placeholder="Бренд"></div></div>
                                <div class="b-stock-form-field" style="max-width:90px;"><div class="b-stock-form-input"><input type="number" name="items[__INDEX__][quantity]" step="0.01" min="0" placeholder="Кол-во" value="1"></div></div>
                                <div class="b-stock-form-field" style="max-width:90px;"><div class="b-stock-form-input"><input type="text" name="items[__INDEX__][unit]" placeholder="Ед." value="шт"></div></div>
                                <div class="b-stock-form-field" style="max-width:110px;"><div class="b-stock-form-input"><input type="number" name="items[__INDEX__][price]" step="0.01" min="0" placeholder="Цена"></div></div>
                                <button type="button" class="js-order-remove-item b-stock-more__btn" style="flex-shrink:0;"><i class="fas fa-times"></i></button>
                            </div>
                        </div>
                        <button type="button" class="b-stock-btn b-stock-btn--outline b-stock-btn--sm js-order-add-item" style="margin-top:8px;"><i class="fas fa-plus"></i> Добавить товар</button>
                    </div>

                    <div class="b-stock-form-field">
                        <label>Заметка</label>
                        <div class="b-stock-form-input"><i class="fas fa-sticky-note"></i><textarea name="notes" rows="2">{{ $isEdit ? $order->notes : '' }}</textarea></div>
                    </div>
                </div>
                <div class="modal-footer b-stock-form-modal__footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Отмена</button>
                    <button type="submit" class="btn btn-primary">{{ $isEdit ? 'Сохранить' : 'Создать заказ' }}</button>
                </div>
            </form>
        </div>
    </div>
</div>
