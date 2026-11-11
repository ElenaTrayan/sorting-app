{{-- Поля товара для пина "Товар" — общие для создания (блок #tab-product) и редактирования поста.
     Сохраняются в post_products (App\Models\PostProduct::attributesFromRequest — имена инпутов должны совпадать).
     "Заполнить по ссылке" — тот же разбор страницы магазина, что в вишлистах (post-product.js →
     PostProductController::linkPreview). На странице поста эти данные показывает parts/product_card.blade.php,
     там же кнопки "В вишлист", "В список покупок", "На склад". Стили — public/admin/pin-create.css.

     @param \App\Models\PostProduct|null $product — сохранённые данные (на странице редактирования) --}}
@php
    use App\Models\PostProduct;

    /** @var PostProduct|null $product */
    $product = $product ?? null;
    $userId = auth()->id();
    $productCurrency = $product->currency ?? PostProduct::defaultCurrency($userId);

    // подсказки: свои бренды и магазины — из уже заполненных товаров, "Запасов" и магазинов "Запасов"
    $productBrandSuggestions = \App\Models\PostProduct::whereHas('post', function ($q) use ($userId) {
            $q->where('user_id', $userId);
        })->whereNotNull('brand')->distinct()->pluck('brand')
        ->merge(\App\Models\Product::where('user_id', $userId)->whereNotNull('brand')->distinct()->pluck('brand'))
        ->unique()->sort()->values();
    $productStoreSuggestions = \App\Models\Store::where('user_id', $userId)->pluck('name')
        ->merge(\App\Models\PostProduct::whereHas('post', function ($q) use ($userId) {
            $q->where('user_id', $userId);
        })->whereNotNull('store')->distinct()->pluck('store'))
        ->unique()->sort()->values();

    $dateValue = function ($date) {
        return $date ? $date->format('Y-m-d') : '';
    };
@endphp
{{-- hidden: поля товара были на форме — PostController::update по нему понимает, что их надо сохранить
     (а все пустые — значит, данные товара удалить) --}}
<input type="hidden" name="product-fields" value="1">
<div class="b-pin-fields js-product-fields" data-preview-action="{{ route('posts.product.link-preview') }}" data-image-action="{{ route('posts.product.image') }}">
    <div class="b-pin-field b-pin-field--wide">
        <label for="product-shop-url">Ссылка на товар</label>
        <div class="b-pin-product__url-row">
            <input type="text" class="b-pin-input js-product-shop-url" name="product-shop-url" id="product-shop-url" maxlength="1000"
                   placeholder="https://…" value="{{ $product->shop_url ?? '' }}">
            {{-- на форме создания кнопка скрыта ($fillFromTop): там ссылку разбирает общий блок "Спарсить информацию"
                 вверху формы — admin.js нажимает эту кнопку за него. На редактировании такого блока нет --}}
            <button type="button" class="b-pin-btn b-pin-btn--ghost js-product-fill-from-url" @if (!empty($fillFromTop)) hidden @endif>
                <span>✦</span> Заполнить по ссылке
            </button>
        </div>
        @if (empty($fillFromTop))
            <span class="b-pin-field__hint">Подставит название, цену, бренд, оценку покупателей и картинку — только в пустые поля</span>
        @endif
    </div>

    <div class="b-pin-product__group">Что за товар</div>
    <div class="b-pin-field">
        <label for="product-category">Категория</label>
        <input type="text" class="b-pin-input" name="product-category" id="product-category" maxlength="100" list="product-category-suggestions"
               placeholder="Косметика, техника, дом…" value="{{ $product->category ?? '' }}" autocomplete="off">
        <datalist id="product-category-suggestions">
            @foreach (PostProduct::CATEGORIES as $category)
                <option value="{{ $category }}"></option>
            @endforeach
        </datalist>
    </div>
    <div class="b-pin-field">
        <label for="product-brand">Бренд</label>
        <input type="text" class="b-pin-input" name="product-brand" id="product-brand" maxlength="150" list="product-brand-suggestions"
               placeholder="Например, La Roche-Posay" value="{{ $product->brand ?? '' }}" autocomplete="off">
        <datalist id="product-brand-suggestions">
            @foreach ($productBrandSuggestions as $brand)
                <option value="{{ $brand }}"></option>
            @endforeach
        </datalist>
    </div>
    <div class="b-pin-field">
        <label for="product-manufacturer">Производитель</label>
        <input type="text" class="b-pin-input" name="product-manufacturer" id="product-manufacturer" maxlength="150"
               placeholder="Компания, если не совпадает с брендом" value="{{ $product->manufacturer ?? '' }}">
    </div>
    <div class="b-pin-field">
        <label for="product-country">Страна производства</label>
        <input type="text" class="b-pin-input" name="product-country" id="product-country" maxlength="100" list="product-country-suggestions"
               placeholder="Франция, Китай…" value="{{ $product->country ?? '' }}" autocomplete="off">
        <datalist id="product-country-suggestions">
            @foreach (PostProduct::COUNTRIES as $country)
                <option value="{{ $country }}"></option>
            @endforeach
        </datalist>
    </div>
    <div class="b-pin-field">
        <label for="product-variant">Вариант</label>
        <input type="text" class="b-pin-input" name="product-variant" id="product-variant" maxlength="150"
               placeholder="Цвет, объём, размер: 50 мл, 128 ГБ" value="{{ $product->variant ?? '' }}">
    </div>
    <div class="b-pin-field">
        <label for="product-article">Артикул / штрихкод</label>
        <input type="text" class="b-pin-input" name="product-article" id="product-article" maxlength="100"
               value="{{ $product->article ?? '' }}">
    </div>

    <div class="b-pin-product__group">Покупка</div>
    <div class="b-pin-field b-pin-field--wide">
        <label>Статус</label>
        <div class="b-pin-chips">
            <label class="b-pin-chip b-pin-chip--check">
                <input type="radio" name="product-status" value="" {{ empty($product->status) ? 'checked' : '' }}>
                Не указан
            </label>
            @foreach (PostProduct::STATUSES as $key => $label)
                <label class="b-pin-chip b-pin-chip--check">
                    <input type="radio" name="product-status" value="{{ $key }}" {{ ($product->status ?? null) === $key ? 'checked' : '' }}>
                    {{ $label }}
                </label>
            @endforeach
        </div>
    </div>
    <div class="b-pin-field">
        <label for="product-price">Цена</label>
        <div class="b-pin-product__price">
            <input type="text" class="b-pin-input" name="product-price" id="product-price" inputmode="decimal" maxlength="20"
                   placeholder="0" value="{{ isset($product->price) ? rtrim(rtrim(number_format((float)$product->price, 2, '.', ''), '0'), '.') : '' }}">
            <select class="b-pin-input" name="product-currency" aria-label="Валюта">
                @foreach (PostProduct::CURRENCIES as $code => $sign)
                    <option value="{{ $code }}" {{ $productCurrency === $code ? 'selected' : '' }}>{{ $sign }}</option>
                @endforeach
            </select>
        </div>
    </div>
    <div class="b-pin-field">
        <label for="product-store">Магазин</label>
        <input type="text" class="b-pin-input" name="product-store" id="product-store" maxlength="150" list="product-store-suggestions"
               placeholder="Rozetka, Makeup, Epicentr…" value="{{ $product->store ?? '' }}" autocomplete="off">
        <datalist id="product-store-suggestions">
            @foreach ($productStoreSuggestions as $store)
                <option value="{{ $store }}"></option>
            @endforeach
        </datalist>
    </div>
    <div class="b-pin-field">
        <label for="product-purchased-at">Дата покупки</label>
        <input type="date" class="b-pin-input" name="product-purchased-at" id="product-purchased-at" value="{{ $dateValue($product->purchased_at ?? null) }}">
    </div>
    <div class="b-pin-field">
        <label for="product-promo">Промокод / скидка</label>
        <input type="text" class="b-pin-input" name="product-promo" id="product-promo" maxlength="255"
               placeholder="SALE20, −30% в чёрную пятницу" value="{{ $product->promo ?? '' }}">
    </div>

    <div class="b-pin-product__group">Сроки</div>
    <div class="b-pin-field">
        <label for="product-expires-at">Срок годности (годен до)</label>
        <input type="date" class="b-pin-input" name="product-expires-at" id="product-expires-at" value="{{ $dateValue($product->expires_at ?? null) }}">
    </div>
    <div class="b-pin-field">
        <label for="product-warranty-months">Гарантия, месяцев</label>
        <input type="number" class="b-pin-input" name="product-warranty-months" id="product-warranty-months" min="1" max="600"
               placeholder="12" value="{{ $product->warranty_months ?? '' }}">
        <span class="b-pin-field__hint">С датой покупки на странице поста будет видно, до какого числа</span>
    </div>

    <div class="b-pin-product__group">Моё мнение</div>
    <div class="b-pin-field b-pin-field--wide">
        <label>Моя оценка</label>
        {{-- звёзды справа налево: так CSS подсвечивает выбранную и все до неё (pin-create.css, .b-pin-stars) --}}
        <div class="b-pin-stars">
            @for ($star = 5; $star >= 1; $star--)
                <input type="radio" name="product-my-rating" id="product-my-rating-{{ $star }}" value="{{ $star }}" {{ ($product->my_rating ?? null) === $star ? 'checked' : '' }}>
                <label for="product-my-rating-{{ $star }}" title="{{ $star }} из 5"><i class="fas fa-star"></i></label>
            @endfor
            <input type="radio" name="product-my-rating" id="product-my-rating-0" value="" {{ empty($product->my_rating) ? 'checked' : '' }}>
            <label for="product-my-rating-0" class="b-pin-stars__reset">без оценки</label>
        </div>
    </div>
    <div class="b-pin-field b-pin-field--wide">
        <label for="product-my-opinion">Моё мнение о товаре</label>
        <textarea class="b-pin-input b-pin-textarea" name="product-my-opinion" id="product-my-opinion" rows="3" maxlength="5000"
                  placeholder="Как себя показал, стоит ли брать ещё раз…">{{ $product->my_opinion ?? '' }}</textarea>
    </div>
    <div class="b-pin-field">
        <label for="product-my-pros">Мне понравилось</label>
        <textarea class="b-pin-input b-pin-textarea" name="product-my-pros" id="product-my-pros" rows="3" maxlength="5000">{{ $product->my_pros ?? '' }}</textarea>
    </div>
    <div class="b-pin-field">
        <label for="product-my-cons">Не понравилось</label>
        <textarea class="b-pin-input b-pin-textarea" name="product-my-cons" id="product-my-cons" rows="3" maxlength="5000">{{ $product->my_cons ?? '' }}</textarea>
    </div>

    <div class="b-pin-product__group">Отзывы людей</div>
    <div class="b-pin-field">
        <label for="product-reviews-rating">Средняя оценка покупателей</label>
        <input type="text" class="b-pin-input" name="product-reviews-rating" id="product-reviews-rating" inputmode="decimal" maxlength="4"
               placeholder="4.7 (из 5)" value="{{ $product->reviews_rating ?? '' }}">
    </div>
    <div class="b-pin-field">
        <label for="product-reviews-count">Сколько отзывов</label>
        <input type="number" class="b-pin-input" name="product-reviews-count" id="product-reviews-count" min="0"
               value="{{ $product->reviews_count ?? '' }}">
    </div>
    <div class="b-pin-field">
        <label for="product-reviews-pros">Что хвалят</label>
        <textarea class="b-pin-input b-pin-textarea" name="product-reviews-pros" id="product-reviews-pros" rows="3" maxlength="5000">{{ $product->reviews_pros ?? '' }}</textarea>
    </div>
    <div class="b-pin-field">
        <label for="product-reviews-cons">На что жалуются</label>
        <textarea class="b-pin-input b-pin-textarea" name="product-reviews-cons" id="product-reviews-cons" rows="3" maxlength="5000">{{ $product->reviews_cons ?? '' }}</textarea>
    </div>
    <div class="b-pin-field b-pin-field--wide">
        <label for="product-reviews-url">Где читать отзывы</label>
        <input type="text" class="b-pin-input" name="product-reviews-url" id="product-reviews-url" maxlength="1000"
               placeholder="https://…" value="{{ $product->reviews_url ?? '' }}">
    </div>
</div>
