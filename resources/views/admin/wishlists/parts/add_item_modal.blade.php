<div class="modal fade" id="modal-add-wishlist-item" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Добавить желание</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
            </div>
            <div class="modal-body">
                <p class="text-muted">Вставь ссылку или опиши товар — детали заполним мы</p>

                <div class="input-group mb-2">
                    <input type="text" id="wishlist-item-input" class="form-control" placeholder="Ссылка на товар или его название">
                    <div class="input-group-append">
                        <button type="button" class="btn btn-outline-secondary js-wishlist-item-image-pick" title="Загрузить свою картинку">
                            <i class="fas fa-image"></i>
                        </button>
                    </div>
                </div>
                <input type="file" id="wishlist-item-image-input" accept="image/*" hidden>

                <button type="button" class="btn btn-light btn-block js-wishlist-item-find">Найти в магазинах <i class="fas fa-arrow-right ml-1"></i></button>

                <div class="js-wishlist-item-preview" style="display: none;"></div>

                <div class="text-center text-muted my-2" style="font-size: 12px; letter-spacing: .05em;">ИЛИ НАЧНИ С КАТЕГОРИИ</div>

                <div class="b-wishlist-filters__categories justify-content-center">
                    @foreach ($categories as $category)
                        <button type="button" class="b-wishlist-filters__category js-wishlist-item-category-pick" data-category="{{ $category['slug'] }}">{{ $category['title'] }}</button>
                    @endforeach
                </div>

                <hr>

                <div class="form-group">
                    <label>Название</label>
                    <input type="text" id="wishlist-item-title" class="form-control" maxlength="255">
                </div>
                <div class="form-row">
                    <div class="form-group col-6">
                        <label>Цена, грн (необязательно)</label>
                        <input type="number" id="wishlist-item-price" class="form-control" min="0" step="1">
                    </div>
                    <div class="form-group col-6">
                        <label>Категория</label>
                        <select id="wishlist-item-category" class="form-control">
                            <option value="">—</option>
                            @foreach ($categories as $category)
                                <option value="{{ $category['slug'] }}">{{ $category['title'] }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>
                <div class="form-group">
                    <label>Приоритет желания (необязательно)</label>
                    <select id="wishlist-item-priority" class="form-control">
                        <option value="">Приоритет не указан</option>
                        @foreach ($priorities as $priority)
                            <option value="{{ $priority['slug'] }}">{{ $priority['title'] }}</option>
                        @endforeach
                    </select>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-primary js-wishlist-item-save">Добавить</button>
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Отмена</button>
            </div>
        </div>
    </div>
</div>
