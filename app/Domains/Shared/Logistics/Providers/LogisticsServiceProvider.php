<?php

declare(strict_types=1);

namespace App\Domains\Logistics\Providers;

use Illuminate\Support\ServiceProvider;
use App\Domains\Logistics\Services\UnifiedFleetService;
use App\Domains\Logistics\Services\PvzAssignmentService;
use App\Domains\Logistics\Services\RouteOptimizationService;
use App\Services\FraudControlService;
use App\Services\Geo\GeoService;
use Illuminate\Contracts\Auth\Guard;
use Illuminate\Database\DatabaseManager;
use Psr\Log\LoggerInterface;

/**
 * Logistics Domain Service Provider
 *
 * Registers logistics domain services and configurations.
 * Integrates unified fleet (courier + taxi) and PVZ assignment services.
 */
final class LogisticsServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        // Register Unified Fleet Service
        $this->app->singleton(UnifiedFleetService::class, function ($app) {
            return new UnifiedFleetService(
                fraudControlService: $app->make(FraudControlService::class),
                geoService: $app->make(GeoService::class),
                db: $app->make(DatabaseManager::class),
                logger: $app->make(LoggerInterface::class),
                guard: $app->make(Guard::class),
            );
        });

        // Register PVZ Assignment Service
        $this->app->singleton(PvzAssignmentService::class, function ($app) {
            return new PvzAssignmentService(
                fraudControlService: $app->make(FraudControlService::class),
                db: $app->make(DatabaseManager::class),
                logger: $app->make(LoggerInterface::class),
                guard: $app->make(Guard::class),
            );
        });

        // Register Route Optimization Service (if exists)
        if (class_exists(RouteOptimizationService::class)) {
            $this->app->singleton(RouteOptimizationService::class, function ($app) {
                return new RouteOptimizationService(
                    $app->make(GeoService::class),
                    $app->make(LoggerInterface::class),
                );
            });
        }
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Routes are already loaded via require in routes/api.php
        // No additional boot logic needed

        // Publish configuration (optional)
        if ($this->app->runningInConsole()) {
            $this->publishes([
                __DIR__.'/../config/logistics.php' => config_path('logistics.php'),
            ], 'logistics-config');
        }
    }
}
