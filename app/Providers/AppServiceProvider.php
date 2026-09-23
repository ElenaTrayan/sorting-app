<?php

namespace App\Providers;

use App\Services\Integrations\IntegrationManager;
use App\Services\Stock\MockPriceOfferProvider;
use App\Services\Stock\PriceOfferProviderInterface;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     *
     * @return void
     */
    public function register()
    {
        // "Мои запасы" → "Поиск лучшего предложения": пока нет реального парсера/API
        // магазинов, используем demo-провайдер. Чтобы подключить реальный парсер —
        // достаточно забиндить сюда свою реализацию PriceOfferProviderInterface.
        $this->app->bind(PriceOfferProviderInterface::class, MockPriceOfferProvider::class);

        // включение/выключение внешних API и их ключи из админки
        $this->app->singleton(IntegrationManager::class);
    }

    /**
     * Bootstrap any application services.
     *
     * @return void
     */
    public function boot()
    {
        //
    }
}
