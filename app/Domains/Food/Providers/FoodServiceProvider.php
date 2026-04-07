<?php

declare(strict_types=1);

/**
 * FoodServiceProvider — CatVRF 2026 Component.
 *
 * Part of the CatVRF multi-vertical marketplace platform.
 * Implements tenant-aware, fraud-checked business logic
 * with full correlation_id tracing and audit logging.
 *
 * @package CatVRF
 * @version 2026.1
 * @author CatVRF Team
 * @license Proprietary

 * @see https://catvrf.ru/docs/foodserviceprovider
 */


namespace App\Domains\Food\Providers;

use App\Domains\Food\Domain\Repositories\OrderRepositoryInterface;
use App\Domains\Food\Domain\Repositories\RestaurantRepositoryInterface;
use App\Domains\Food\Infrastructure\Persistence\Eloquent\Repositories\EloquentOrderRepository;
use App\Domains\Food\Infrastructure\Persistence\Eloquent\Repositories\EloquentRestaurantRepository;
use Illuminate\Support\ServiceProvider;

/**
 * Class FoodServiceProvider
 *
 * Part of the Food vertical domain.
 * Follows CatVRF 9-layer architecture.
 *
 * Service layer following CatVRF canon:
 * - Constructor injection only (no Facades)
 * - FraudControlService::check() before mutations
 * - $this->db->transaction() wrapping all write operations
 * - Audit logging with correlation_id
 * - Tenant and BusinessGroup scoping
 *
 * @see \App\Services\FraudControlService
 * @see \App\Services\AuditService
 * @package App\Domains\Food\Providers
 */
final class FoodServiceProvider extends ServiceProvider
{
    public array $bindings = [
        RestaurantRepositoryInterface::class => EloquentRestaurantRepository::class,
        OrderRepositoryInterface::class => EloquentOrderRepository::class,
    ];

    /**
     * Register services.
     */
    public function register(): void
    {
        // Bind interfaces to implementations
        foreach ($this->bindings as $interface => $implementation) {
            $this->app->bind($interface, $implementation);
        }
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        // You can add any booting logic here, like loading migrations, routes, etc.
        // For example:
        // $this->loadMigrationsFrom(__DIR__.'/../../database/migrations/food');
    }
}
