<?php

namespace App\Services\Stock;

use App\Models\Product;
use App\Models\Store;

/**
 * Сервис "Поиск лучшего предложения" (см. раздел ТЗ "Постоянные покупки").
 * Оборачивает PriceOfferProviderInterface: собирает магазины пользователя,
 * запрашивает предложения и сортирует их так, чтобы первым всегда шло лучшее.
 */
class PriceOfferService
{
    protected PriceOfferProviderInterface $provider;

    public function __construct(PriceOfferProviderInterface $provider)
    {
        $this->provider = $provider;
    }

    public function searchBestOffers(Product $product, int $userId): array
    {
        $stores = Store::where('user_id', $userId)->get();

        $offers = $this->provider->findOffers($product, $stores);

        $offers = array_filter($offers, fn ($offer) => $offer['availability'] !== 'out_of_stock')
            ?: $offers; // если вообще ничего нет в наличии — покажем всё как есть

        usort($offers, fn ($a, $b) => $a['price'] <=> $b['price']);

        foreach ($offers as $i => &$offer) {
            $offer['is_best'] = $i === 0;
        }

        return array_values($offers);
    }
}
