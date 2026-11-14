<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;

/**
 * Данные товара для пина типа "Товар" (1:1 с posts, как PostFilm / PostClothes): что за товар, где и почём,
 * статус, сроки (годности, хранения, гарантии), характеристики со страницы магазина (вес, условия хранения,
 * страна бренда — App\Parsers\ProductSpecsParser), моё мнение и сводка отзывов людей. Первый шаг к сервису
 * "Инвентаризация вещей" — поля потом переносятся в вещь, как пин "Одежда" в "Мой гардероб".
 * Кнопки на странице поста отправляют товар в вишлист, список покупок и на склад
 * (User\PostProductController) — id созданных записей хранятся здесь, чтобы показать "уже добавлено".
 */
class PostProduct extends Model
{
    protected $table = 'post_products';

    public const STATUSES = [
        'want' => 'Хочу',
        'bought' => 'Куплено',
        'using' => 'Пользуюсь',
        'finished' => 'Закончилось',
        'disliked' => 'Не понравилось',
        'gone' => 'Отдала / продала',
    ];

    // иконки статусов (Font Awesome) — значок в карточке
    public const STATUS_ICONS = [
        'want' => 'fa-heart',
        'bought' => 'fa-shopping-bag',
        'using' => 'fa-check-circle',
        'finished' => 'fa-hourglass-end',
        'disliked' => 'fa-thumbs-down',
        'gone' => 'fa-hand-holding-heart',
    ];

    // подсказки к полю "Категория" — можно ввести и свою
    public const CATEGORIES = [
        'Косметика', 'Уход за телом', 'Парфюмерия', 'Здоровье', 'Техника', 'Электроника', 'Гаджеты и аксессуары',
        'Дом', 'Кухня', 'Посуда', 'Текстиль', 'Мебель', 'Декор', 'Бытовая химия', 'Сад и дача', 'Инструменты',
        'Еда', 'Напитки', 'Книги', 'Канцелярия', 'Хобби и творчество', 'Спорт', 'Детское', 'Игрушки',
        'Зоотовары', 'Авто', 'Украшения', 'Сумки', 'Подарки',
    ];

    public const CURRENCIES = [
        'UAH' => 'грн',
        'USD' => '$',
        'EUR' => '€',
        'PLN' => 'zł',
        'RUB' => '₽',
    ];

    // подсказки к полю "Страна производства"
    public const COUNTRIES = [
        'Украина', 'Польша', 'Германия', 'Франция', 'Италия', 'Испания', 'Великобритания', 'Чехия', 'Турция',
        'США', 'Канада', 'Китай', 'Япония', 'Южная Корея', 'Тайвань', 'Вьетнам', 'Индия', 'Таиланд',
        'Израиль', 'Швейцария', 'Швеция', 'Финляндия', 'Нидерланды', 'Бельгия', 'Австрия',
    ];

    protected $fillable = [
        'post_id',
        'category',
        'brand',
        'manufacturer',
        'country',
        'brand_country',
        'variant',
        'weight',
        'article',
        'status',
        'price',
        'currency',
        'store',
        'shop_url',
        'promo',
        'purchased_at',
        'expires_at',
        'shelf_life',
        'storage_conditions',
        'warranty_months',
        'my_rating',
        'my_opinion',
        'my_pros',
        'my_cons',
        'reviews_rating',
        'reviews_count',
        'reviews_pros',
        'reviews_cons',
        'reviews_url',
        'wishlist_item_id',
        'shopping_list_item_id',
        'stock_item_id',
    ];

    protected $casts = [
        'price' => 'decimal:2',
        'purchased_at' => 'date',
        'expires_at' => 'date',
        'warranty_months' => 'integer',
        'my_rating' => 'integer',
        'reviews_rating' => 'float',
        'reviews_count' => 'integer',
    ];

    public function post()
    {
        return $this->belongsTo(Post::class, 'post_id');
    }

    public function wishlistItem()
    {
        return $this->belongsTo(WishlistItem::class, 'wishlist_item_id');
    }

    public function shoppingListItem()
    {
        return $this->belongsTo(ShoppingListItem::class, 'shopping_list_item_id');
    }

    public function stockItem()
    {
        return $this->belongsTo(StockItem::class, 'stock_item_id');
    }

    public function statusLabel(): ?string
    {
        return self::STATUSES[$this->status] ?? null;
    }

    // "1 299 грн"; null — цена не указана
    public function priceLabel(): ?string
    {
        if ($this->price === null) {
            return null;
        }
        $value = (float)$this->price;
        $number = number_format($value, floor($value) == $value ? 0 : 2, ',', ' ');

        return $number . ' ' . (self::CURRENCIES[$this->currency] ?? self::CURRENCIES[self::defaultCurrency()]);
    }

    // гарантия до: дата покупки + месяцы гарантии; без даты покупки — null (покажем просто срок)
    public function warrantyUntil(): ?Carbon
    {
        return $this->warranty_months && $this->purchased_at ? $this->purchased_at->copy()->addMonths($this->warranty_months) : null;
    }

    // "12 мес." / "2 года"
    public function warrantyLabel(): ?string
    {
        $months = (int)$this->warranty_months;
        if ($months <= 0) {
            return null;
        }
        if ($months % 12 === 0) {
            $years = intdiv($months, 12);
            return $years . ' ' . self::plural($years, 'год', 'года', 'лет');
        }

        return $months . ' мес.';
    }

    // срок годности истёк / истекает в ближайшие 30 дней — подсветка в карточке
    public function expiryState(): ?string
    {
        if (!$this->expires_at) {
            return null;
        }
        if ($this->expires_at->isPast() && !$this->expires_at->isToday()) {
            return 'expired';
        }

        return $this->expires_at->lte(now()->addDays(30)) ? 'soon' : 'ok';
    }

    public static function plural(int $n, string $one, string $few, string $many): string
    {
        $mod10 = $n % 10;
        $mod100 = $n % 100;
        if ($mod10 === 1 && $mod100 !== 11) {
            return $one;
        }
        if ($mod10 >= 2 && $mod10 <= 4 && ($mod100 < 10 || $mod100 >= 20)) {
            return $few;
        }

        return $many;
    }

    // валюта по умолчанию — из настроек "Запасов" пользователя (stock_settings.currency), иначе гривна
    public static function defaultCurrency(?int $userId = null): string
    {
        $userId = $userId ?? auth()->id();
        $currency = $userId ? StockSetting::where('user_id', $userId)->value('currency') : null;

        return array_key_exists((string)$currency, self::CURRENCIES) ? $currency : 'UAH';
    }

    /**
     * Поля формы из запроса (имена инпутов — как в parts/product_fields.blade.php) -> атрибуты модели.
     * Неверные значения (цена "abc", оценка 9) не валят сохранение поста, а просто не сохраняются.
     *
     * @param Request $request
     * @return array|null - null, если ни одно поле не заполнено (валюта сама по себе не считается)
     */
    public static function attributesFromRequest(Request $request): ?array
    {
        $text = function (string $name, int $maxLength) use ($request): ?string {
            $value = trim((string)$request->input($name, ''));

            return $value === '' ? null : mb_substr($value, 0, $maxLength);
        };

        $longText = function (string $name) use ($text): ?string {
            return $text($name, 5000);
        };

        $url = function (string $name) use ($text): ?string {
            $value = $text($name, 1000);
            if ($value !== null && !preg_match('~^https?://~i', $value)) {
                $value = 'https://' . $value;
            }

            return $value;
        };

        // "1 299,50" / "1299.5" -> 1299.50
        $number = function (string $name, float $max) use ($text): ?float {
            $value = $text($name, 30);
            if ($value === null) {
                return null;
            }
            $value = str_replace([' ', "\u{00A0}", ','], ['', '', '.'], $value);

            return is_numeric($value) && (float)$value >= 0 && (float)$value <= $max ? round((float)$value, 2) : null;
        };

        $date = function (string $name) use ($text): ?string {
            $value = $text($name, 20);
            if ($value === null) {
                return null;
            }
            try {
                return Carbon::parse($value)->format('Y-m-d');
            } catch (\Throwable $e) {
                return null;
            }
        };

        $integer = function (string $name, int $min, int $max) use ($text): ?int {
            $value = $text($name, 10);

            return $value !== null && preg_match('~^\d+$~', $value) && (int)$value >= $min && (int)$value <= $max ? (int)$value : null;
        };

        $status = $text('product-status', 20);
        $currency = $text('product-currency', 3);
        $reviewsRating = $number('product-reviews-rating', 5);

        $attributes = [
            'category' => $text('product-category', 100),
            'brand' => $text('product-brand', 150),
            'manufacturer' => $text('product-manufacturer', 150),
            'country' => $text('product-country', 100),
            'brand_country' => $text('product-brand-country', 100),
            'variant' => $text('product-variant', 150),
            'weight' => $text('product-weight', 50),
            'article' => $text('product-article', 100),
            'status' => array_key_exists((string)$status, self::STATUSES) ? $status : null,
            'price' => $number('product-price', 9999999999),
            'store' => $text('product-store', 150),
            'shop_url' => $url('product-shop-url'),
            'promo' => $text('product-promo', 255),
            'purchased_at' => $date('product-purchased-at'),
            'expires_at' => $date('product-expires-at'),
            'shelf_life' => $text('product-shelf-life', 100),
            'storage_conditions' => $longText('product-storage-conditions'),
            'warranty_months' => $integer('product-warranty-months', 1, 600),
            'my_rating' => $integer('product-my-rating', 1, 5),
            'my_opinion' => $longText('product-my-opinion'),
            'my_pros' => $longText('product-my-pros'),
            'my_cons' => $longText('product-my-cons'),
            'reviews_rating' => $reviewsRating !== null ? round($reviewsRating, 1) : null,
            'reviews_count' => $integer('product-reviews-count', 0, 100000000),
            'reviews_pros' => $longText('product-reviews-pros'),
            'reviews_cons' => $longText('product-reviews-cons'),
            'reviews_url' => $url('product-reviews-url'),
        ];

        $filled = array_filter($attributes, function ($value) {
            return $value !== null;
        });
        if (empty($filled)) {
            return null;
        }

        $attributes['currency'] = array_key_exists((string)$currency, self::CURRENCIES) ? $currency : self::defaultCurrency();

        return $attributes;
    }

    /**
     * Сохраняет/обновляет данные товара поста; если все поля пустые — удаляет запись.
     * Ссылки на вишлист/список покупок/склад (*_item_id) форма не присылает — их не трогаем
     *
     * @param Post $post
     * @param array|null $attributes - результат attributesFromRequest()
     * @return void
     */
    public static function saveForPost(Post $post, ?array $attributes): void
    {
        if ($attributes === null) {
            self::where('post_id', $post->id)->delete();
            return;
        }

        self::updateOrCreate(['post_id' => $post->id], $attributes);
    }
}
