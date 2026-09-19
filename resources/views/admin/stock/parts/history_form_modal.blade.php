<div class="modal fade" id="modal-create-history" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered" role="document">
        <div class="modal-content b-stock-form-modal">
            <div class="modal-header b-stock-form-modal__header">
                <div class="b-stock-form-modal__icon"><i class="fas fa-receipt"></i></div>
                <div class="b-stock-form-modal__heading"><h5 class="modal-title">Записать покупку</h5><div class="b-stock-form-modal__subtitle">Для покупок не через заказы (напр. офлайн)</div></div>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
            </div>
            <form data-stock-ajax-form data-method="POST" action="{{ route('stock.history.store') }}">
                <div class="modal-body">
                    <div class="b-stock-form-row">
                        <div class="b-stock-form-field">
                            <label>Дата <span class="text-danger">*</span></label>
                            <div class="b-stock-form-input"><i class="far fa-calendar-alt"></i><input type="date" name="purchased_at" required value="{{ now()->format('Y-m-d') }}"></div>
                        </div>
                        <div class="b-stock-form-field">
                            <label>Сумма <span class="text-danger">*</span></label>
                            <div class="b-stock-form-input"><i class="fas fa-hryvnia-sign"></i><input type="number" name="total_amount" step="0.01" min="0" required></div>
                        </div>
                    </div>
                    <div class="b-stock-form-row">
                        <div class="b-stock-form-field">
                            <label>Магазин</label>
                            <div class="b-stock-form-input">
                                <i class="fas fa-store"></i>
                                <select name="store_id">
                                    <option value="">—</option>
                                    @foreach ($stores as $store)
                                        <option value="{{ $store->id }}">{{ $store->name }}</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>
                        <div class="b-stock-form-field">
                            <label>Способ оплаты</label>
                            <div class="b-stock-form-input"><i class="fas fa-credit-card"></i><input type="text" name="payment_method" maxlength="50" placeholder="Картой"></div>
                        </div>
                    </div>
                    <div class="b-stock-form-field">
                        <label>Категория</label>
                        <div class="b-stock-form-input">
                            <i class="fas fa-th-large"></i>
                            <select name="category">
                                <option value="">—</option>
                                @foreach (\App\Models\Product::CATEGORIES as $slug => $label)
                                    <option value="{{ $slug }}">{{ $label }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                    <div class="b-stock-form-field">
                        <label>Товары (по одному в строке)</label>
                        <div class="b-stock-form-input">
                            <i class="fas fa-list"></i>
                            <textarea name="items_text" rows="3" placeholder="Молоко&#10;Хлеб"></textarea>
                        </div>
                    </div>
                </div>
                <div class="modal-footer b-stock-form-modal__footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Отмена</button>
                    <button type="submit" class="btn btn-primary">Сохранить</button>
                </div>
            </form>
        </div>
    </div>
</div>
