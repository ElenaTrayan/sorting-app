<?php

namespace App\Services\Stock;

use App\Models\Promotion;
use App\Models\RegularPurchase;
use App\Models\Store;
use Illuminate\Support\Collection;

/**
 * "Мониторинг акций" по магазинам пользователя — заглушка вокруг того же
 * PriceOfferProviderInterface, что и "Поиск лучшего предложения": пока нет
 * реального парсера, генерируем demo-акции (source=mock) на постоянные покупки
 * в магазинах, где включён мониторинг (Store::monitor_promotions). Идемпотентно:
 * одна mock-запись на пару продукт+магазин, обновляется при каждом вызове.
 */
class PromotionSyncService
{
    protected PriceOfferProviderInterface $provider;

    public function __construct(PriceOfferProviderInterface $provider)
    {
        $this->provider = $provider;
    }

    public function syncForUser(int $userId): void
    {
        $stores = Store::where('user_id', $userId)->where('monitor_promotions', true)->get();
        if ($stores->isEmpty()) {
            return;
        }

        $regularPurchases = RegularPurchase::where('user_id', $userId)
            ->whereIn('preference', ['favorite', 'normal'])
            ->with('product')
            ->get();

        foreach ($regularPurchases as $regular) {
            if (!$regular->product) {
                continue;
            }

            $offers = $this->provider->findOffers($regular->product, $stores);

            foreach ($offers as $offer) {
                if (empty($offer['old_price']) || empty($offer['discount_percent'])) {
                    continue; // показываем в "Акциях" только то, что реально по скидке
                }

                Promotion::updateOrCreate(
                    [
                        'user_id' => $userId,
                        'product_id' => $regular->product_id,
                        'store_id' => $offer['store_id'],
                        'source' => 'mock',
                    ],
                    [
                        'title' => $offer['title'],
                        'price' => $offer['price'],
                        'old_price' => $offer['old_price'],
                        'discount_percent' => $offer['discount_percent'],
                        'availability' => $offer['availability'],
                        'weight_volume' => $offer['weight_volume'],
                        'valid_until' => $offer['valid_until'],
                        'url' => $offer['url'],
                    ]
                );
            }
        }
    }
}
