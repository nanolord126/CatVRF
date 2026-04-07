<?php declare(strict_types=1);

/**
 * TravelDomainServiceProvider — CatVRF 2026 Component.
 *
 * Part of the CatVRF multi-vertical marketplace platform.
 * Implements tenant-aware, fraud-checked business logic
 * with full correlation_id tracing and audit logging.
 *
 * @package CatVRF
 * @version 2026.1
 * @author CatVRF Team
 * @license Proprietary

 * @see https://catvrf.ru/docs/traveldomainserviceprovider
 */


namespace App\Domains\Travel\Providers;

use Illuminate\Support\ServiceProvider;

final class TravelDomainServiceProvider extends ServiceProvider
{

    /**
         * Регистрация всех зависимостей вертикали.
         */
        public function register(): void
        {
            // 1. Регистрация BookingService (Слой 3)
            $this->app->singleton(BookingService::class, function ($app) {
                return new BookingService(
                    $app->make(WalletService::class),
                    $app->make(FraudControlService::class)
                );
            });

            // 2. Регистрация AITripPlannerService (Слой 5)
            $this->app->singleton(AITripPlannerService::class, function ($app) {
                return new AITripPlannerService(
                    $app->make(RecommendationService::class),
                    $app->make(AIAgentFramework::class)
                );
            });

            // 3. Регистрация TravelFraudService (Слой 6)
            $this->app->singleton(TravelFraudService::class, function ($app) {
                return new TravelFraudService(
                    $app->make(FraudControlService::class),
                    $app->make(RateLimiterService::class)
                );
            });
        }

        /**
         * Загрузка ресурсов домена.
         */
        public function boot(): void
        {
            // Регистрация миграций (если нужно разделить, но Канон 2026 — плоско в database/migrations)
            // Регистрация маршрутов (API Layer 8)
            $this->loadRoutesFrom(__DIR__ . '/../Routes/api.php');
        }
}
