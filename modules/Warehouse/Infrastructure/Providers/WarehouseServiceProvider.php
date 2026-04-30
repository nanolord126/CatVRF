<?php

declare(strict_types=1);

namespace Modules\Warehouse\Infrastructure\Providers;

use Illuminate\Support\ServiceProvider;
use Modules\Warehouse\Domain\Repositories\WarehouseRepositoryInterface;
use Modules\Warehouse\Domain\Repositories\ZoneRepositoryInterface;
use Modules\Warehouse\Domain\Repositories\BinRepositoryInterface;
use Modules\Warehouse\Domain\Repositories\ProductRepositoryInterface;
use Modules\Warehouse\Domain\Repositories\BatchRepositoryInterface;
use Modules\Warehouse\Domain\Repositories\InventoryItemRepositoryInterface;
use Modules\Warehouse\Domain\Repositories\StockMovementRepositoryInterface;
use Modules\Warehouse\Domain\Repositories\InventoryCountRepositoryInterface;
use Modules\Warehouse\Infrastructure\Repositories\WarehouseRepository;
use Modules\Warehouse\Infrastructure\Repositories\ZoneRepository;
use Modules\Warehouse\Infrastructure\Repositories\BinRepository;
use Modules\Warehouse\Infrastructure\Repositories\ProductRepository;
use Modules\Warehouse\Infrastructure\Repositories\BatchRepository;
use Modules\Warehouse\Infrastructure\Repositories\InventoryItemRepository;
use Modules\Warehouse\Infrastructure\Repositories\StockMovementRepository;
use Modules\Warehouse\Infrastructure\Repositories\InventoryCountRepository;
use Modules\Warehouse\Application\Services\WarehouseApplicationService;
use Modules\Warehouse\Application\Services\ZoneApplicationService;
use Modules\Warehouse\Application\Services\BinApplicationService;
use Modules\Warehouse\Application\Services\ProductApplicationService;
use Modules\Warehouse\Application\Services\MovementApplicationService;
use Modules\Warehouse\Application\Services\InventoryManagementService;
use Modules\Warehouse\Application\Services\BatchTrackingApplicationService;
use Modules\Warehouse\Application\Services\InventoryCycleCountingApplicationService;
use Modules\Warehouse\Application\Services\WarehouseOrderTypeSwitchingService;
use App\Services\Security\AuditService;
use Psr\Log\LoggerInterface;

/**
 * Warehouse Service Provider
 *
 * Binds repositories and services for Warehouse vertical
 *
 * @author CatVRF Team
 * @version 2026.04.28
 */
class WarehouseServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        // Repository bindings
        $this->app->bind(WarehouseRepositoryInterface::class, WarehouseRepository::class);
        $this->app->bind(ZoneRepositoryInterface::class, ZoneRepository::class);
        $this->app->bind(BinRepositoryInterface::class, BinRepository::class);
        $this->app->bind(ProductRepositoryInterface::class, ProductRepository::class);
        $this->app->bind(BatchRepositoryInterface::class, BatchRepository::class);
        $this->app->bind(InventoryItemRepositoryInterface::class, InventoryItemRepository::class);
        $this->app->bind(StockMovementRepositoryInterface::class, StockMovementRepository::class);
        $this->app->bind(InventoryCountRepositoryInterface::class, InventoryCountRepository::class);

        // Application Service bindings
        $this->app->singleton(WarehouseApplicationService::class, function ($app) {
            return new WarehouseApplicationService(
                $app->make(WarehouseRepositoryInterface::class),
                $app->make(LoggerInterface::class),
                $app->make(AuditService::class)
            );
        });

        $this->app->singleton(ZoneApplicationService::class, function ($app) {
            return new ZoneApplicationService(
                $app->make(ZoneRepositoryInterface::class),
                $app->make(WarehouseRepositoryInterface::class),
                $app->make(LoggerInterface::class),
                $app->make(AuditService::class)
            );
        });

        $this->app->singleton(BinApplicationService::class, function ($app) {
            return new BinApplicationService(
                $app->make(BinRepositoryInterface::class),
                $app->make(ZoneRepositoryInterface::class),
                $app->make(LoggerInterface::class),
                $app->make(AuditService::class)
            );
        });

        $this->app->singleton(ProductApplicationService::class, function ($app) {
            return new ProductApplicationService(
                $app->make(ProductRepositoryInterface::class),
                $app->make(LoggerInterface::class),
                $app->make(AuditService::class)
            );
        });

        $this->app->singleton(MovementApplicationService::class, function ($app) {
            return new MovementApplicationService(
                $app->make(StockMovementRepositoryInterface::class),
                $app->make(InventoryItemRepositoryInterface::class),
                $app->make(LoggerInterface::class),
                $app->make(AuditService::class)
            );
        });

        $this->app->singleton(InventoryManagementService::class, function ($app) {
            return new InventoryManagementService(
                $app->make(InventoryItemRepositoryInterface::class),
                $app->make(StockMovementRepositoryInterface::class),
                $app->make(LoggerInterface::class),
                $app->make(AuditService::class)
            );
        });

        $this->app->singleton(BatchTrackingApplicationService::class, function ($app) {
            return new BatchTrackingApplicationService(
                $app->make(BatchRepositoryInterface::class),
                $app->make(LoggerInterface::class),
                $app->make(AuditService::class)
            );
        });

        $this->app->singleton(InventoryCycleCountingApplicationService::class, function ($app) {
            return new InventoryCycleCountingApplicationService(
                $app->make(InventoryCountRepositoryInterface::class),
                $app->make(InventoryItemRepositoryInterface::class),
                $app->make(LoggerInterface::class),
                $app->make(AuditService::class)
            );
        });

        $this->app->singleton(WarehouseOrderTypeSwitchingService::class, function ($app) {
            return new WarehouseOrderTypeSwitchingService(
                $app->make(InventoryItemRepositoryInterface::class),
                $app->make(StockMovementRepositoryInterface::class),
                $app->make(AuditService::class),
                $app->make('cache'),
                $app->make(LoggerInterface::class)
            );
        });
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        // Load migrations
        $this->loadMigrationsFrom(__DIR__ . '/../../Database/migrations');

        // Load routes if they exist
        if (file_exists(__DIR__ . '/../../Interface/routes.php')) {
            $this->loadRoutesFrom(__DIR__ . '/../../Interface/routes.php');
        }
    }
}
