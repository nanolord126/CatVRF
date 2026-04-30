<?php

declare(strict_types=1);

namespace App\Providers;

use App\Services\Cache\WMSCacheService;
use App\Services\Integrations\EGISZIntegrationService;
use App\Services\Integrations\OneCIntegrationService;
use App\Services\Inventory\BarcodeScanningService;
use App\Services\Inventory\BulkStockMovementService;
use App\Services\Reporting\WMSReportingService;
use App\Services\Validation\WMSDomainValidator;
use Illuminate\Support\ServiceProvider;

/**
 * WMS Service Provider
 *
 * Registers WMS compliance services with the container
 *
 * @author CatVRF Team
 * @version 2026.04.28
 */
final class WMSServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(WMSDomainValidator::class, function ($app) {
            return new WMSDomainValidator(
                logger: $app->make('log'),
            );
        });

        $this->app->singleton(EGISZIntegrationService::class, function ($app) {
            return new EGISZIntegrationService(
                apiUrl: config('wms.egisz.api_url'),
                apiKey: config('wms.egisz.api_key'),
                certificatePath: config('wms.egisz.certificate_path'),
                logger: $app->make('log'),
            );
        });

        $this->app->singleton(OneCIntegrationService::class, function ($app) {
            return new OneCIntegrationService(
                apiUrl: config('wms.onec.api_url'),
                username: config('wms.onec.username'),
                password: config('wms.onec.password'),
                logger: $app->make('log'),
            );
        });

        $this->app->singleton(WMSReportingService::class, function ($app) {
            return new WMSReportingService(
                db: $app->make('db'),
                logger: $app->make('log'),
            );
        });

        $this->app->singleton(WMSCacheService::class, function ($app) {
            return new WMSCacheService(
                logger: $app->make('log'),
            );
        });

        $this->app->singleton(BulkStockMovementService::class, function ($app) {
            return new BulkStockMovementService(
                db: $app->make('db'),
                logger: $app->make('log'),
                auditService: $app->make(App\Services\Security\AuditService::class),
            );
        });

        $this->app->singleton(BarcodeScanningService::class, function ($app) {
            return new BarcodeScanningService(
                db: $app->make('db'),
                logger: $app->make('log'),
            );
        });
    }

    public function boot(): void
    {
        //
    }
}
