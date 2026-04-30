<?php

declare(strict_types=1);

namespace Modules\Inventory\Infrastructure\Providers;

use Illuminate\Support\ServiceProvider;
use Modules\Inventory\Application\Services\FIFOShelfLifeService;

final class InventoryServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(FIFOShelfLifeService::class);
    }

    public function boot(): void
    {
        $this->loadMigrationsFrom(__DIR__.'/../../../../../database/migrations');

        $this->publishes([
            __DIR__.'/../../../../../database/migrations' => database_path('migrations'),
        ], 'inventory-migrations');
    }
}
