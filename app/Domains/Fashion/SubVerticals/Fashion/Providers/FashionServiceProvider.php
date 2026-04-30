<?php

declare(strict_types=1);

namespace Modules\Fashion\Providers;

use Illuminate\Support\ServiceProvider;
use App\Domains\Fashion\Models\FashionStore;
use App\Domains\Fashion\Models\FashionProduct;
use App\Domains\Fashion\Models\FashionOrder;
use Modules\Fashion\Observers\FashionStoreObserver;
use Modules\Fashion\Observers\FashionProductObserver;
use Modules\Fashion\Observers\FashionOrderObserver;
use App\Domains\Fashion\Services\FashionService;
use App\Services\FraudControlService;
use Modules\Fashion\Services\FashionAnalyticsService;
use Modules\Fashion\Services\FashionBrandServiceEnhanced;
use Modules\Fashion\Services\FashionDiscountService;
use Modules\Fashion\Services\FashionInventoryManagementService;
use Modules\Fashion\Services\FashionNotificationService;
use Modules\Fashion\Services\FashionRecommendationEngineService;
use Modules\Fashion\Services\FashionReturnProcessingService;
use Modules\Fashion\Services\FashionReviewAggregationService;
use Modules\Fashion\Services\FashionSearchService;
use Modules\Fashion\Services\FashionSizeRecommendationService;
use Modules\Fashion\Services\FashionSocialMediaIntegrationService;
use Modules\Fashion\Services\FashionTrendingProductsService;
use Modules\Fashion\Services\ML\FashionColorHarmonyService;
use Modules\Fashion\Services\ML\FashionCrossVerticalRecommendationService;
use Modules\Fashion\Services\ML\FashionMannequinSizeAlgorithmService;

final class FashionServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        // Register services as singletons
        $this->app->singleton(FashionService::class, function ($app) {
            return new FashionService(
                $app->make(FraudControlService::class),
                $app['db'],
                $app['request'],
                $app['log'],
                $app['auth']
            );
        });

        $this->app->singleton(FashionAnalyticsService::class, function ($app) {
            return new FashionAnalyticsService();
        });

        $this->app->singleton(FashionNotificationService::class, function ($app) {
            return new FashionNotificationService();
        });

        $this->app->singleton(FashionRecommendationEngineService::class, function ($app) {
            return new FashionRecommendationEngineService();
        });

        $this->app->singleton(FashionSizeRecommendationService::class, function ($app) {
            return new FashionSizeRecommendationService();
        });

        $this->app->singleton(FashionTrendingProductsService::class, function ($app) {
            return new FashionTrendingProductsService();
        });

        $this->app->singleton(FashionInventoryManagementService::class, function ($app) {
            return new FashionInventoryManagementService();
        });

        $this->app->singleton(FashionDiscountService::class, function ($app) {
            return new FashionDiscountService();
        });

        $this->app->singleton(FashionSearchService::class, function ($app) {
            return new FashionSearchService();
        });

        $this->app->singleton(FashionSocialMediaIntegrationService::class, function ($app) {
            return new FashionSocialMediaIntegrationService();
        });

        $this->app->singleton(FashionBrandServiceEnhanced::class, function ($app) {
            return new FashionBrandServiceEnhanced();
        });

        $this->app->singleton(FashionReviewAggregationService::class, function ($app) {
            return new FashionReviewAggregationService();
        });

        $this->app->singleton(FashionReturnProcessingService::class, function ($app) {
            return new FashionReturnProcessingService();
        });

        // ML Services
        $this->app->singleton(FashionColorHarmonyService::class, function ($app) {
            return new FashionColorHarmonyService();
        });

        $this->app->singleton(FashionMannequinSizeAlgorithmService::class, function ($app) {
            return new FashionMannequinSizeAlgorithmService();
        });

        $this->app->singleton(FashionCrossVerticalRecommendationService::class, function ($app) {
            return new FashionCrossVerticalRecommendationService();
        });

        // Note: FashionStyleConstructorService has complex dependencies that should be resolved via container
        // The service will be registered with its actual dependencies when available
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        // Register observers for auto wallet creation and audit logging
        FashionStore::observe(FashionStoreObserver::class);
        FashionProduct::observe(FashionProductObserver::class);
        FashionOrder::observe(FashionOrderObserver::class);

        // Publish migrations if they exist
        if (file_exists(__DIR__.'/../database/migrations')) {
            $this->publishes([
                __DIR__.'/../database/migrations' => base_path('database/migrations'),
            ], 'fashion-migrations');
        }

        // Load routes if they exist
        if (file_exists(__DIR__.'/../routes.php')) {
            $this->loadRoutesFrom(__DIR__.'/../routes.php');
        }
    }
}
