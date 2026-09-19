<?php

namespace App\Services\Stock;

use App\Models\Product;
use App\Models\Store;
use Illuminate\Support\Collection;

/**
 * Демо-реализация PriceOfferProviderInterface: генерирует правдоподобные
 * предложения по магазинам пользователя, отталкиваясь от последней/средней цены
 * товара (из RegularPurchase), если она известна, иначе от условной базовой цены.
 *
 * Это ЗАГЛУШКА вместо реального парсера/API магазинов — см. описание в интерфейсе.
 * Ничего в этом классе не хардкодит конкретные магазины: они полностью берутся
 * из переданной коллекции $stores (магазины пользователя из раздела "Магазины").
 */
class MockPriceOfferProvider implements PriceOfferProviderInterface
{
    public function findOffers(Product $product, Collection $stores): array
    {
        if ($stores->isEmpty()) {
            return [];
        }

        $basePrice = $this->basePrice($product);
        $sizeLabel = $product->size_label;
        $offers = [];

        // Детерминированный "случайный" разброс — по id магазина и товара, чтобы
        // результаты не прыгали между обновлениями страницы в рамках одной сессии.
        foreach ($stores as $store) {
            $seed = crc32($store->id . ':' . $product->id);
            mt_srand($seed);

            $variance = (mt_rand(-15, 20)) / 100; // -15%..+20% от базовой цены
            $price = round($basePrice * (1 + $variance), 0);

            $hasDiscount = mt_rand(0, 100) <= 40;
            $oldPrice = $hasDiscount ? round($price / (1 - mt_rand(5, 25) / 100), 0) : null;
            $discountPercent = $oldPrice ? (int) round((1 - $price / $oldPrice) * 100) : null;

            $availabilityRoll = mt_rand(0, 100);
            $availability = $availabilityRoll > 90 ? 'out_of_stock' : ($availabilityRoll > 75 ? 'limited' : 'in_stock');

            $offers[] = [
                'store_id' => $store->id,
                'store_name' => $store->name,
                'store_logo' => $store->logo,
                'title' => trim($product->display_name . ($sizeLabel ? ' ' . $sizeLabel : '')),
                'price' => (float) $price,
                'old_price' => $oldPrice ? (float) $oldPrice : null,
                'discount_percent' => $discountPercent,
                'availability' => $availability,
                'weight_volume' => $sizeLabel,
                'valid_until' => $hasDiscount ? now()->addDays(mt_rand(2, 10))->toDateString() : null,
                'url' => $store->website ?: null,
            ];
        }

        mt_srand(); // возвращаем ГПСЧ в обычный режим для остального приложения

        return $offers;
    }

    protected function basePrice(Product $product): float
    {
        $regular = $product->regularPurchase;
        if ($regular && $regular->last_price) {
            return (float) $regular->last_price;
        }
        if ($regular && $regular->avg_price) {
            return (float) $regular->avg_price;
        }

        // условная базовая цена, чтобы демо всегда выглядело правдоподобно
        return 150.0;
    }
}
