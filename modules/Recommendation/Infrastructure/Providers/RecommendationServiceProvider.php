<?php

declare(strict_types=1);

namespace Modules\Recommendation\Infrastructure\Providers;

use Illuminate\Support\ServiceProvider;
use Modules\Recommendation\Domain\Repositories\RecommendationRepositoryInterface;
use Modules\Recommendation\Domain\Interfaces\MLInferenceInterface;
use Modules\Recommendation\Domain\Interfaces\FairnessEvaluatorInterface;
use Modules\Recommendation\Infrastructure\ClickHouse\ClickHouseRecommendationRepository;
use Modules\Recommendation\Infrastructure\ML\MLInferenceClient;
use Modules\Recommendation\Infrastructure\Redis\RedisFeatureCache;
use Modules\Recommendation\Application\Services\RecommendationOrchestrator;
use Modules\Recommendation\Application\Services\RuleBasedFallbackService;
use Modules\Recommendation\Application\Services\FairnessEvaluatorService;
use Modules\Recommendation\Application\Services\SellerRecommendationService;
use Modules\Recommendation\Application\Services\ImpressionTrackingService;
use Modules\BigData\Infrastructure\ClickHouse\ClickHouseClient;

class RecommendationServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(RecommendationRepositoryInterface::class, function ($app) {
            return new ClickHouseRecommendationRepository(
                $app->make(ClickHouseClient::class)
            );
        });

        $this->app->bind(MLInferenceInterface::class, function ($app) {
            return new MLInferenceClient(
                baseUrl: config('recommendation.ml_service_url', 'http://localhost:8000'),
                apiKey: config('recommendation.ml_service_api_key', ''),
            );
        });

        $this->app->bind(FairnessEvaluatorInterface::class, FairnessEvaluatorService::class);

        $this->app->singleton(RedisFeatureCache::class);

        $this->app->singleton(RecommendationOrchestrator::class, function ($app) {
            return new RecommendationOrchestrator(
                repository: $app->make(RecommendationRepositoryInterface::class),
                mlInference: $app->make(MLInferenceInterface::class),
                fairnessEvaluator: $app->make(FairnessEvaluatorInterface::class),
                fallbackService: $app->make(RuleBasedFallbackService::class),
                audit: $app->make(\App\Services\Audit\AuditService::class),
            );
        });

        $this->app->singleton(RuleBasedFallbackService::class, function ($app) {
            return new RuleBasedFallbackService(
                repository: $app->make(RecommendationRepositoryInterface::class),
            );
        });

        $this->app->singleton(SellerRecommendationService::class, function ($app) {
            return new SellerRecommendationService(
                repository: $app->make(RecommendationRepositoryInterface::class),
                audit: $app->make(\App\Services\Audit\AuditService::class),
            );
        });

        $this->app->singleton(ImpressionTrackingService::class, function ($app) {
            return new ImpressionTrackingService(
                repository: $app->make(RecommendationRepositoryInterface::class),
                audit: $app->make(\App\Services\Audit\AuditService::class),
            );
        });
    }

    public function boot(): void
    {
        $this->loadRoutesFrom(__DIR__ . '/../../Presentation/Routes/api.php');

        $this->publishes([
            __DIR__ . '/../../../config/recommendation.php' => config_path('recommendation.php'),
        ], 'recommendation-config');

        $this->publishes([
            __DIR__ . '/../../database/clickhouse' => database_path('clickhouse/recommendation'),
        ], 'recommendation-migrations');
    }
}
