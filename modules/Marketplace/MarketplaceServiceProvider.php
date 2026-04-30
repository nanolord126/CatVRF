<?php

declare(strict_types=1);

namespace Modules\Marketplace;

use Illuminate\Support\ServiceProvider;
use Modules\Marketplace\Application\DTOs\RankingConfigDTO;
use Modules\Marketplace\Application\Services\MarketplaceAggregatorService;
use Modules\Marketplace\Application\Services\RankingEngineService;
use Modules\Marketplace\Application\Services\RecommendationService;
use Modules\Marketplace\Infrastructure\Adapters\BeautyAdapter;
use Modules\Marketplace\Infrastructure\Adapters\FashionAdapter;
use Modules\Marketplace\Infrastructure\Adapters\FitnessAdapter;
use Modules\Marketplace\Infrastructure\Adapters\FlowersAdapter;
use Modules\Marketplace\Infrastructure\Adapters\HotelsAdapter;
use Modules\Marketplace\Infrastructure\Adapters\RestaurantAdapter;
use Modules\Marketplace\Infrastructure\Adapters\VerticalAdapterFactory;
use Modules\Marketplace\Infrastructure\Cache\MarketplaceCacheService;
use Modules\Marketplace\Infrastructure\Repositories\EloquentAggregationRuleRepository;
use Modules\Marketplace\Infrastructure\Repositories\EloquentCategoryRepository;
use Modules\Marketplace\Infrastructure\Repositories\EloquentListingRepository;
use Modules\Marketplace\Infrastructure\Repositories\EloquentRankingRepository;
use Modules\Marketplace\Domain\Interfaces\AggregationRuleRepositoryInterface;
use Modules\Marketplace\Domain\Interfaces\CategoryRepositoryInterface;
use Modules\Marketplace\Domain\Interfaces\ListingRepositoryInterface;
use Modules\Marketplace\Domain\Interfaces\RankingRepositoryInterface;
use Psr\Log\LoggerInterface;

/**
 * Service Provider для модуля Marketplace
 * Регистрирует все зависимости через DI (без фасадов)
 */
final class MarketplaceServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->registerRepositories();
        $this->registerAdapters();
        $this->registerServices();
        $this->registerConfiguration();
    }

    public function boot(): void
    {
        $this->loadRoutesFrom(__DIR__ . '/Presentation/Routes/marketplace.php');
        $this->loadMigrationsFrom(__DIR__ . '/../database/migrations');

        if ($this->app->runningInConsole()) {
            $this->publishes([
                __DIR__ . '/../config/marketplace.php' => config_path('marketplace.php'),
            ], 'marketplace-config');
        }
    }

    private function registerRepositories(): void
    {
        // Listing Repository
        $this->app->singleton(ListingRepositoryInterface::class, function ($app) {
            return new EloquentListingRepository(
                $app->make('db')->connection(),
                $app->make(LoggerInterface::class),
            );
        });

        // Category Repository
        $this->app->singleton(CategoryRepositoryInterface::class, function ($app) {
            return new EloquentCategoryRepository(
                $app->make('db')->connection(),
                $app->make(LoggerInterface::class),
            );
        });

        // Ranking Repository
        $this->app->singleton(RankingRepositoryInterface::class, function ($app) {
            return new EloquentRankingRepository(
                $app->make('db')->connection(),
                $app->make(LoggerInterface::class),
            );
        });

        // Aggregation Rule Repository
        $this->app->singleton(AggregationRuleRepositoryInterface::class, function ($app) {
            return new EloquentAggregationRuleRepository(
                $app->make('db')->connection(),
                $app->make(LoggerInterface::class),
            );
        });
    }

    private function registerAdapters(): void
    {
        // Vertical Adapter Factory
        $this->app->singleton(VerticalAdapterFactory::class, function ($app) {
            return new VerticalAdapterFactory(
                $app->make(LoggerInterface::class),
            );
        });

        // Register default adapters if their dependencies are available
        $this->app->resolving(VerticalAdapterFactory::class, function (VerticalAdapterFactory $factory, $app) {
            // Beauty Adapter
            if (class_exists(\Modules\BeautyMasters\Domain\Repositories\ServiceRepositoryInterface::class)) {
                try {
                    $beautyAdapter = new BeautyAdapter(
                        $app->make(\Modules\BeautyMasters\Domain\Repositories\ServiceRepositoryInterface::class),
                        $app->make(LoggerInterface::class),
                    );
                    $factory->registerAdapter(\Modules\Marketplace\Domain\ValueObjects\VerticalSource::BEAUTY, $beautyAdapter);
                } catch (\Throwable $e) {
                    $app->make(LoggerInterface::class)->warning('Failed to register Beauty adapter', ['error' => $e->getMessage()]);
                }
            }

            // Restaurant Adapter
            if (class_exists(\Modules\Restaurant\Domain\Repositories\DishRepositoryInterface::class)) {
                try {
                    $restaurantAdapter = new RestaurantAdapter(
                        $app->make(\Modules\Restaurant\Domain\Repositories\DishRepositoryInterface::class),
                        $app->make(LoggerInterface::class),
                    );
                    $factory->registerAdapter(\Modules\Marketplace\Domain\ValueObjects\VerticalSource::RESTAURANT, $restaurantAdapter);
                } catch (\Throwable $e) {
                    $app->make(LoggerInterface::class)->warning('Failed to register Restaurant adapter', ['error' => $e->getMessage()]);
                }
            }

            // Fashion Adapter
            if (class_exists(\Modules\Fashion\Domain\Repositories\ProductRepositoryInterface::class)) {
                try {
                    $fashionAdapter = new FashionAdapter(
                        $app->make(\Modules\Fashion\Domain\Repositories\ProductRepositoryInterface::class),
                        $app->make(LoggerInterface::class),
                    );
                    $factory->registerAdapter(\Modules\Marketplace\Domain\ValueObjects\VerticalSource::FASHION, $fashionAdapter);
                } catch (\Throwable $e) {
                    $app->make(LoggerInterface::class)->warning('Failed to register Fashion adapter', ['error' => $e->getMessage()]);
                }
            }

            // Hotels Adapter
            if (class_exists(\Modules\Hotels\Domain\Repositories\RoomRepositoryInterface::class)) {
                try {
                    $hotelsAdapter = new HotelsAdapter(
                        $app->make(\Modules\Hotels\Domain\Repositories\RoomRepositoryInterface::class),
                        $app->make(LoggerInterface::class),
                    );
                    $factory->registerAdapter(\Modules\Marketplace\Domain\ValueObjects\VerticalSource::HOTELS, $hotelsAdapter);
                } catch (\Throwable $e) {
                    $app->make(LoggerInterface::class)->warning('Failed to register Hotels adapter', ['error' => $e->getMessage()]);
                }
            }

            // Fitness Adapter
            if (class_exists(\Modules\Fitness\Domain\Repositories\MembershipRepositoryInterface::class)) {
                try {
                    $fitnessAdapter = new FitnessAdapter(
                        $app->make(\Modules\Fitness\Domain\Repositories\MembershipRepositoryInterface::class),
                        $app->make(LoggerInterface::class),
                    );
                    $factory->registerAdapter(\Modules\Marketplace\Domain\ValueObjects\VerticalSource::FITNESS, $fitnessAdapter);
                } catch (\Throwable $e) {
                    $app->make(LoggerInterface::class)->warning('Failed to register Fitness adapter', ['error' => $e->getMessage()]);
                }
            }

            // Flowers Adapter
            if (class_exists(\Modules\Flowers\Domain\Repositories\BouquetRepositoryInterface::class)) {
                try {
                    $flowersAdapter = new FlowersAdapter(
                        $app->make(\Modules\Flowers\Domain\Repositories\BouquetRepositoryInterface::class),
                        $app->make(LoggerInterface::class),
                    );
                    $factory->registerAdapter(\Modules\Marketplace\Domain\ValueObjects\VerticalSource::FLOWERS, $flowersAdapter);
                } catch (\Throwable $e) {
                    $app->make(LoggerInterface::class)->warning('Failed to register Flowers adapter', ['error' => $e->getMessage()]);
                }
            }
        });
    }

    private function registerServices(): void
    {
        // Ranking Engine Service
        $this->app->singleton(RankingEngineService::class, function ($app) {
            $config = RankingConfigDTO::fromArray(
                config('marketplace.ranking', [])
            );

            return new RankingEngineService(
                $app->make(ListingRepositoryInterface::class),
                $app->make(RankingRepositoryInterface::class),
                $app->make(LoggerInterface::class),
                $config,
            );
        });

        // Marketplace Aggregator Service
        $this->app->singleton(MarketplaceAggregatorService::class, function ($app) {
            return new MarketplaceAggregatorService(
                $app->make(ListingRepositoryInterface::class),
                $app->make(AggregationRuleRepositoryInterface::class),
                $app->make(VerticalAdapterFactory::class),
                $app->make(LoggerInterface::class),
                $app->make('events'),
            );
        });

        // Recommendation Service
        $this->app->singleton(RecommendationService::class, function ($app) {
            return new RecommendationService(
                $app->make(ListingRepositoryInterface::class),
                $app->make(LoggerInterface::class),
            );
        });
    }

    private function registerConfiguration(): void
    {
        $this->mergeConfigFrom(
            __DIR__ . '/../config/marketplace.php',
            'marketplace'
        );
    }

    /**
     * Get the services provided by the provider.
     *
     * @return array<int, string>
     */
    public function provides(): array
    {
        return [
            ListingRepositoryInterface::class,
            CategoryRepositoryInterface::class,
            RankingRepositoryInterface::class,
            AggregationRuleRepositoryInterface::class,
            VerticalAdapterFactory::class,
            MarketplaceCacheService::class,
            RankingEngineService::class,
            MarketplaceAggregatorService::class,
            RecommendationService::class,
        ];
    }
}
