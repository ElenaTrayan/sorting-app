<?php

namespace App\Services\Stock;

use App\Models\Product;
use App\Models\Store;
use Illuminate\Support\Collection;

/**
 * Контракт для получения актуальных предложений товара в магазинах.
 *
 * Реальный провайдер (парсер/API конкретного магазина) должен реализовать этот
 * интерфейс и вернуть предложения в том же формате — фронт и PriceOfferService
 * ничего не знают о том, как именно предложения были получены, поэтому подключение
 * реального парсера сводится к замене биндинга в контейнере (см. AppServiceProvider
 * или конфиг), без изменений в контроллере/вьюхах.
 *
 * @see MockPriceOfferProvider демо-реализация, используемая пока реальных парсеров нет.
 */
interface PriceOfferProviderInterface
{
    /**
     * @param Product $product товар, для которого ищем предложения
     * @param Collection<int, Store> $stores магазины пользователя, по которым искать
     * @return array<int, array{
     *     store_id: int,
     *     store_name: string,
     *     store_logo: string|null,
     *     title: string,
     *     price: float,
     *     old_price: float|null,
     *     discount_percent: int|null,
     *     availability: string,
     *     weight_volume: string|null,
     *     valid_until: string|null,
     *     url: string|null,
     * }>
     */
    public function findOffers(Product $product, Collection $stores): array;
}
