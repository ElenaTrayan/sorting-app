<div class="modal fade" id="modal-create-promotion" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered" role="document">
        <div class="modal-content b-stock-form-modal">
            <div class="modal-header b-stock-form-modal__header">
                <div class="b-stock-form-modal__icon"><i class="fas fa-percent"></i></div>
                <div class="b-stock-form-modal__heading"><h5 class="modal-title">Добавить акцию вручную</h5></div>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
            </div>
            <form data-stock-ajax-form data-method="POST" action="{{ route('stock.promotions.store') }}">
                <div class="modal-body">
                    <div class="b-stock-form-field">
                        <label>Товар <span class="text-danger">*</span></label>
                        <div class="b-stock-form-input"><i class="fas fa-tag"></i><input type="text" name="title" required maxlength="255" placeholder="Напр. Persil Color 4.5 кг"></div>
                    </div>
                    <div class="b-stock-form-field">
                        <label>Магазин <span class="text-danger">*</span></label>
                        <div class="b-stock-form-input">
                            <i class="fas fa-store"></i>
                            <select name="store_id" required>
                                @foreach ($stores as $store)
                                    <option value="{{ $store->id }}">{{ $store->name }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                    <div class="b-stock-form-row">
                        <div class="b-stock-form-field">
                            <label>Цена <span class="text-danger">*</span></label>
                            <div class="b-stock-form-input"><i class="fas fa-hryvnia-sign"></i><input type="number" name="price" step="0.01" min="0" required></div>
                        </div>
                        <div class="b-stock-form-field">
                            <label>Старая цена</label>
                            <div class="b-stock-form-input"><i class="fas fa-hryvnia-sign"></i><input type="number" name="old_price" step="0.01" min="0"></div>
                        </div>
                    </div>
                    <div class="b-stock-form-row">
                        <div class="b-stock-form-field">
                            <label>Наличие <span class="text-danger">*</span></label>
                            <div class="b-stock-form-input">
                                <i class="fas fa-box"></i>
                                <select name="availability" required>
                                    @foreach (\App\Models\Promotion::AVAILABILITY as $value => $label)
                                        <option value="{{ $value }}">{{ $label }}</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>
                        <div class="b-stock-form-field">
                            <label>Действует до</label>
                            <div class="b-stock-form-input"><i class="far fa-calendar-alt"></i><input type="date" name="valid_until"></div>
                        </div>
                    </div>
                    <div class="b-stock-form-row">
                        <div class="b-stock-form-field">
                            <label>Вес/объём</label>
                            <div class="b-stock-form-input"><i class="fas fa-weight-hanging"></i><input type="text" name="weight_volume" maxlength="50" placeholder="4.5 кг"></div>
                        </div>
                        <div class="b-stock-form-field">
                            <label>Ссылка на магазин</label>
                            <div class="b-stock-form-input"><i class="fas fa-link"></i><input type="text" name="url" maxlength="500" placeholder="https://"></div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer b-stock-form-modal__footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Отмена</button>
                    <button type="submit" class="btn btn-primary">Добавить</button>
                </div>
            </form>
        </div>
    </div>
</div>
