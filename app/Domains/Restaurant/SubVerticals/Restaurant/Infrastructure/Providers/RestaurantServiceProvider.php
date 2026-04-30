<?php

declare(strict_types=1);

namespace Modules\Restaurant\Infrastructure\Providers;

use Illuminate\Support\ServiceProvider;
use Modules\Restaurant\Domain\Repositories\KitchenStationRepositoryInterface;
use Modules\Restaurant\Domain\Repositories\OrderKitchenStatusRepositoryInterface;
use Modules\Restaurant\Infrastructure\Repositories\EloquentKitchenStationRepository;
use Modules\Restaurant\Infrastructure\Repositories\EloquentOrderKitchenStatusRepository;

final class RestaurantServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(KitchenStationRepositoryInterface::class, EloquentKitchenStationRepository::class);
        $this->app->bind(OrderKitchenStatusRepositoryInterface::class, EloquentOrderKitchenStatusRepository::class);
    }

    public function boot(): void
    {
        $this->loadMigrationsFrom(__DIR__.'/../../Infrastructure/Database/Migrations');
        $this->loadRoutesFrom(__DIR__.'/../../Presentation/Routes/web.php');
        $this->loadViewsFrom(__DIR__.'/../../Presentation/Views', 'restaurant');
    }
}
