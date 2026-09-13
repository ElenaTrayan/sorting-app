<div class="modal fade" id="modal-wishlist-item-detail" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg" role="document">
        <div class="modal-content b-item-detail-modal">
            <button type="button" class="close b-item-detail-modal__close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>

            <div class="modal-body b-item-detail-modal__body">
                <div class="b-item-detail js-item-detail-view">
                    <a class="b-item-detail__thumb js-item-detail-thumb" data-fancybox="wishlist-item-image"></a>

                    <div class="b-item-detail__info">
                        <div class="b-item-detail__head">
                            <div class="b-item-detail__title js-item-detail-title"></div>

                            @if ($isOwner)
                                <div class="b-item-detail__more js-item-detail-more">
                                    <button type="button" class="b-item-detail__more-btn js-item-detail-more-toggle" title="Ещё">
                                        <i class="fas fa-ellipsis-v"></i>
                                    </button>
                                    <div class="b-item-detail__more-menu js-item-detail-more-menu">
                                        <button type="button" class="b-item-detail__more-menu-item js-item-detail-edit-toggle">
                                            <i class="fas fa-pencil-alt"></i> Редактировать
                                        </button>
                                    </div>
                                </div>
                            @endif
                        </div>

                        <div class="b-item-detail__subtitle js-item-detail-subtitle"></div>
                        <div class="b-item-detail__price js-item-detail-price"></div>

                        <div class="b-item-detail__details js-item-detail-details" style="display: none;">
                            <div class="b-item-detail__details-title">Подробности</div>
                            <p class="b-item-detail__description js-item-detail-description"></p>
                            <button type="button" class="b-item-detail__description-toggle js-item-detail-description-toggle" style="display: none;">Показать больше</button>
                        </div>

                        @if ($isOwner)
                            <div class="b-item-detail__fulfill-box js-item-detail-fulfill-box">
                                <div class="b-item-detail__fulfill-question">Исполнили это желание?</div>
                                <button type="button" class="b-item-detail__fulfill-link js-item-detail-fulfill-toggle">Отметить как выполненное</button>
                            </div>
                        @endif
                    </div>
                </div>

                @if ($isOwner)
                    <div class="js-item-detail-edit-fields" style="display: none;">
                        <div class="form-group">
                            <label>Название</label>
                            <input type="text" id="item-detail-edit-title" class="form-control" maxlength="255">
                        </div>
                        <div class="form-group">
                            <label>Описание</label>
                            <textarea id="item-detail-edit-description" class="form-control" rows="3" maxlength="2000"></textarea>
                        </div>
                        <div class="form-row">
                            <div class="form-group col-6">
                                <label>Цена, грн</label>
                                <input type="number" id="item-detail-edit-price" class="form-control" min="0" step="1">
                            </div>
                            <div class="form-group col-6">
                                <label>Ссылка на товар</label>
                                <input type="text" id="item-detail-edit-url" class="form-control">
                            </div>
                        </div>
                        <div class="form-group">
                            <label>Приоритет желания</label>
                            <select id="item-detail-edit-priority" class="form-control">
                                <option value="">Приоритет не указан</option>
                                @foreach ($priorities as $priority)
                                    <option value="{{ $priority['slug'] }}">{{ $priority['title'] }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                @endif
            </div>

            <div class="modal-footer b-item-detail-modal__footer">
                <a href="#" target="_blank" rel="noopener" class="btn btn-primary btn-block b-item-detail__buy-btn js-item-detail-open-store" style="display: none;">
                    <span class="js-item-detail-store-label">Открыть в магазине</span> <i class="fas fa-shopping-bag ml-2"></i>
                </a>
                @if ($isOwner)
                    <button type="button" class="btn btn-primary btn-block js-item-detail-save" style="display: none;">Сохранить</button>
                @endif
            </div>
        </div>
    </div>
</div>
