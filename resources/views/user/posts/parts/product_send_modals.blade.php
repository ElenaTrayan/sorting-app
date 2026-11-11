{{-- Окна выбора для кнопок карточки товара (parts/product_card.blade.php): в какой вишлист, на какое место
     "Запасов" и сколько. Списки подгружает post-product.js (posts.product.options) при первом открытии.

     @param \App\Models\Post $post --}}
<div class="modal fade" id="modal-product-to-wishlist" tabindex="-1" role="dialog" aria-hidden="true"
     data-options-action="{{ route('posts.product.options', $post->id) }}">
    <div class="modal-dialog modal-dialog-centered" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">В вишлист</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
            </div>
            <div class="modal-body">
                <div class="form-group mb-0 js-product-wishlist-select-group">
                    <label for="product-wishlist-select">Вишлист</label>
                    <select class="form-control js-product-wishlist-select" id="product-wishlist-select"></select>
                </div>
                {{-- вишлистов ещё нет — новый с этим названием --}}
                <div class="form-group mb-0 js-product-wishlist-new-group" hidden>
                    <label for="product-wishlist-new">Вишлистов пока нет — создать новый</label>
                    <input type="text" class="form-control js-product-wishlist-new" id="product-wishlist-new" maxlength="255" value="Мои желания">
                </div>
                <div class="text-muted small mt-2">Уйдут название, цена, ссылка, категория, первая картинка поста и моё мнение.</div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Отмена</button>
                <button type="button" class="btn btn-primary js-product-wishlist-save">Добавить</button>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="modal-product-to-stock" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">На склад</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
            </div>
            <div class="modal-body">
                <div class="form-row">
                    <div class="form-group col-8 mb-0">
                        <label for="product-stock-location">Где хранится</label>
                        <select class="form-control js-product-stock-location" id="product-stock-location"></select>
                    </div>
                    <div class="form-group col-4 mb-0">
                        <label for="product-stock-quantity">Сколько, шт</label>
                        <input type="number" class="form-control js-product-stock-quantity" id="product-stock-quantity" min="0" step="any" value="1">
                    </div>
                </div>
                <div class="text-muted small mt-2">Уйдут название, бренд, цена, магазин, дата покупки и срок годности. Статус «Хочу» сменится на «Куплено».</div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Отмена</button>
                <button type="button" class="btn btn-primary js-product-stock-save">Добавить</button>
            </div>
        </div>
    </div>
</div>
