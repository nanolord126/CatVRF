<?php declare(strict_types=1);

namespace Modules\Fashion\Providers;

use Illuminate\Support\ServiceProvider;
use App\Domains\Fashion\Models\FashionStore;
use App\Domains\Fashion\Models\FashionProduct;
use App\Domains\Fashion\Models\FashionOrder;
use Modules\Fashion\Observers\FashionStoreObserver;
use Modules\Fashion\Observers\FashionProductObserver;
use Modules\Fashion\Observers\FashionOrderObserver;

final class FashionServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        // Register services as singletons
        $this->app->singleton(\App\Domains\Fashion\Services\FashionService::class, function ($app) {
            return new \App\Domains\Fashion\Services\FashionService(
                $app->make(\App\Services\FraudControlService::class),
                $app['db'],
                $app['request'],
                $app['log'],
                $app['auth']
            );
        });

        $this->app->singleton(\Modules\Fashion\Services\FashionAnalyticsService::class, function ($app) {
            return new \Modules\Fashion\Services\FashionAnalyticsService();
        });

        $this->app->singleton(\Modules\Fashion\Services\FashionNotificationService::class, function ($app) {
            return new \Modules\Fashion\Services\FashionNotificationService();
        });

        $this->app->singleton(\Modules\Fashion\Services\FashionRecommendationEngineService::class, function ($app) {
            return new \Modules\Fashion\Services\FashionRecommendationEngineService();
        });

        $this->app->singleton(\Modules\Fashion\Services\FashionSizeRecommendationService::class, function ($app) {
            return new \Modules\Fashion\Services\FashionSizeRecommendationService();
        });

        $this->app->singleton(\Modules\Fashion\Services\FashionTrendingProductsService::class, function ($app) {
            return new \Modules\Fashion\Services\FashionTrendingProductsService();
        });

        $this->app->singleton(\Modules\Fashion\Services\FashionInventoryManagementService::class, function ($app) {
            return new \Modules\Fashion\Services\FashionInventoryManagementService();
        });

        $this->app->singleton(\Modules\Fashion\Services\FashionDiscountService::class, function ($app) {
            return new \Modules\Fashion\Services\FashionDiscountService();
        });

        $this->app->singleton(\Modules\Fashion\Services\FashionSearchService::class, function ($app) {
            return new \Modules\Fashion\Services\FashionSearchService();
        });

        $this->app->singleton(\Modules\Fashion\Services\FashionSocialMediaIntegrationService::class, function ($app) {
            return new \Modules\Fashion\Services\FashionSocialMediaIntegrationService();
        });

        $this->app->singleton(\Modules\Fashion\Services\FashionBrandServiceEnhanced::class, function ($app) {
            return new \Modules\Fashion\Services\FashionBrandServiceEnhanced();
        });

        $this->app->singleton(\Modules\Fashion\Services\FashionReviewAggregationService::class, function ($app) {
            return new \Modules\Fashion\Services\FashionReviewAggregationService();
        });

        $this->app->singleton(\Modules\Fashion\Services\FashionReturnProcessingService::class, function ($app) {
            return new \Modules\Fashion\Services\FashionReturnProcessingService();
        });

        // ML Services
        $this->app->singleton(\Modules\Fashion\Services\ML\FashionColorHarmonyService::class, function ($app) {
            return new \Modules\Fashion\Services\ML\FashionColorHarmonyService();
        });

        $this->app->singleton(\Modules\Fashion\Services\ML\FashionMannequinSizeAlgorithmService::class, function ($app) {
            return new \Modules\Fashion\Services\ML\FashionMannequinSizeAlgorithmService();
        });

        $this->app->singleton(\Modules\Fashion\Services\ML\FashionCrossVerticalRecommendationService::class, function ($app) {
            return new \Modules\Fashion\Services\ML\FashionCrossVerticalRecommendationService();
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
        if (file_exists(__DIR__ . '/../database/migrations')) {
            $this->publishes([
                __DIR__ . '/../database/migrations' => base_path('database/migrations'),
            ], 'fashion-migrations');
        }

        // Load routes if they exist
        if (file_exists(__DIR__ . '/../routes.php')) {
            $this->loadRoutesFrom(__DIR__ . '/../routes.php');
        }
    }
}
