{{--
    Модалка "Поиск лучшего предложения" — общая на страницу, наполняется через JS
    (js-search-offer-btn → my-stock.js). Магазины/цены нигде не хардкожены: всё, что
    показывается, приходит из JSON-ответа stock.regular-purchases.search-offers
    (см. PriceOfferService — сейчас мок-провайдер, в будущем реальный парсер/API).
--}}
<div class="modal fade" id="modal-best-offer" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered" role="document">
        <div class="modal-content b-stock-form-modal">
            <div class="modal-header b-stock-form-modal__header">
                <div class="b-stock-form-modal__icon"><i class="fas fa-tags"></i></div>
                <div class="b-stock-form-modal__heading">
                    <h5 class="modal-title">Лучшее предложение</h5>
                    <div class="b-stock-form-modal__subtitle js-best-offer-subtitle"></div>
                </div>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
            </div>
            <div class="modal-body js-best-offer-body">
                <div class="b-stock-offers-loading">
                    <i class="fas fa-spinner fa-spin"></i>
                    Ищем лучшие предложения…
                </div>
            </div>
        </div>
    </div>
</div>
