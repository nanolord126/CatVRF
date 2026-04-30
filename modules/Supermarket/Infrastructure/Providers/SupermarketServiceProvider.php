<?php

declare(strict_types=1);

namespace Modules\Supermarket\Infrastructure\Providers;

use Illuminate\Support\ServiceProvider;
use Modules\Supermarket\Domain\Repositories\ReturnRepositoryInterface;
use Modules\Supermarket\Infrastructure\Repositories\ReturnRepository;

final class SupermarketServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        // Repository bindings
        $this->app->bind(ReturnRepositoryInterface::class, ReturnRepository::class);
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        // Load routes
        $this->loadRoutesFrom(base_path('routes/api/supermarket-crm.php'));
        
        // Load migrations
        $this->loadMigrationsFrom(base_path('modules/Supermarket/Database/Migrations'));
    }
}
