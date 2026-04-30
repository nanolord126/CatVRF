<?php

declare(strict_types=1);

namespace Modules\Flowers\Infrastructure\Providers;

use Illuminate\Support\ServiceProvider;
use Modules\Flowers\Domain\Repositories\OrderRepositoryInterface;
use Modules\Flowers\Domain\Repositories\FlowerRepositoryInterface;
use Modules\Flowers\Domain\Repositories\ProductRepositoryInterface;
use Modules\Flowers\Domain\Repositories\ClientRepositoryInterface;
use Modules\Flowers\Domain\Repositories\FloristRepositoryInterface;
use Modules\Flowers\Domain\Repositories\VenueRepositoryInterface;
use Modules\Flowers\Domain\Repositories\DeliverySlotRepositoryInterface;
use Modules\Flowers\Infrastructure\Repositories\EloquentOrderRepository;
use Modules\Flowers\Infrastructure\Repositories\EloquentFlowerRepository;
use Modules\Flowers\Infrastructure\Repositories\EloquentProductRepository;
use Modules\Flowers\Infrastructure\Repositories\EloquentClientRepository;
use Modules\Flowers\Infrastructure\Repositories\EloquentFloristRepository;
use Modules\Flowers\Infrastructure\Repositories\EloquentVenueRepository;
use Modules\Flowers\Infrastructure\Repositories\EloquentDeliverySlotRepository;

final class FlowersServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(OrderRepositoryInterface::class, EloquentOrderRepository::class);
        $this->app->bind(FlowerRepositoryInterface::class, EloquentFlowerRepository::class);
        $this->app->bind(ProductRepositoryInterface::class, EloquentProductRepository::class);
        $this->app->bind(ClientRepositoryInterface::class, EloquentClientRepository::class);
        $this->app->bind(FloristRepositoryInterface::class, EloquentFloristRepository::class);
        $this->app->bind(VenueRepositoryInterface::class, EloquentVenueRepository::class);
        $this->app->bind(DeliverySlotRepositoryInterface::class, EloquentDeliverySlotRepository::class);
    }

    public function boot(): void
    {
        $this->loadMigrationsFrom(__DIR__ . '/../../../../../database/migrations');
        
        $this->loadViewsFrom(__DIR__ . '/../../../../../resources/views/modules/flowers', 'flowers');
        
        $this->loadRoutesFrom(__DIR__ . '/../../../../../routes/api/flowers.php');

        // Register event listeners
        $this->registerEventListeners();
    }

    private function registerEventListeners(): void
    {
        // Order events
        \Illuminate\Support\Facades\Event::listen(
            \Modules\Flowers\Domain\Events\OrderCreated::class,
            \Modules\Flowers\Infrastructure\Listeners\SendOrderConfirmationNotification::class
        );

        \Illuminate\Support\Facades\Event::listen(
            \Modules\Flowers\Domain\Events\OrderStatusChanged::class,
            \Modules\Flowers\Infrastructure\Listeners\SendOrderConfirmationNotification::class
        );

        \Illuminate\Support\Facades\Event::listen(
            \Modules\Flowers\Domain\Events\OrderStatusChanged::class,
            \Modules\Flowers\Infrastructure\Listeners\NotifyFloristOfNewOrder::class
        );

        \Illuminate\Support\Facades\Event::listen(
            \Modules\Flowers\Domain\Events\OrderStatusChanged::class,
            \Modules\Flowers\Infrastructure\Listeners\UpdateClientLoyalty::class
        );

        \Illuminate\Support\Facades\Event::listen(
            \Modules\Flowers\Domain\Events\OrderStatusChanged::class,
            \Modules\Flowers\Infrastructure\Listeners\ProcessLoyaltyPoints::class
        );

        // Freshness events
        \Illuminate\Support\Facades\Event::listen(
            \Modules\Flowers\Domain\Events\FreshnessStatusChanged::class,
            \Modules\Flowers\Infrastructure\Listeners\SendFreshnessAlert::class
        );

        \Illuminate\Support\Facades\Event::listen(
            \Modules\Flowers\Domain\Events\LowStockAlert::class,
            \Modules\Flowers\Infrastructure\Listeners\SendFreshnessAlert::class
        );

        \Illuminate\Support\Facades\Event::listen(
            \Modules\Flowers\Domain\Events\ExpiryWarning::class,
            \Modules\Flowers\Infrastructure\Listeners\SendFreshnessAlert::class
        );
    }
}
