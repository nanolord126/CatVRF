<?php

declare(strict_types=1);

namespace App\Domains\Bonuses;

use Illuminate\Support\ServiceProvider;
use App\Domains\Bonuses\Services\BonusService;
use App\Domains\Bonuses\Services\BonusCalculationService;
use App\Domains\Bonuses\Services\BonusEligibilityService;
use App\Domains\Bonuses\Services\BonusWithdrawalService;
use App\Domains\Bonuses\Facades\Bonus;

/**
 * BonusesServiceProvider - Service provider for Bonus domain
 * 
 * Registers all Bonus domain services and binds the facade.
 * Provides dependency injection for the 9-layer architecture.
 */
final class BonusesServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        // Bind BonusService as singleton (application service)
        $this->app->singleton('bonus.service', function ($app) {
            return new BonusService(
                db: $app->make(\Illuminate\Database\DatabaseManager::class),
                calculation: $app->make(BonusCalculationService::class),
                eligibility: $app->make(BonusEligibilityService::class),
                withdrawal: $app->make(BonusWithdrawalService::class),
                fraud: $app->make(\App\Services\FraudControlService::class),
                audit: $app->make(\App\Services\AuditService::class),
                eventDispatcher: $app->make(\Illuminate\Contracts\Events\Dispatcher::class),
                logger: $app->make(\Psr\Log\LoggerInterface::class),
            );
        });

        // Bind domain services
        $this->app->singleton(BonusCalculationService::class, function ($app) {
            return new BonusCalculationService(
                cache: $app->make('cache'),
                db: $app->make(\Illuminate\Database\DatabaseManager::class),
                logger: $app->make(\Psr\Log\LoggerInterface::class),
            );
        });

        $this->app->singleton(BonusEligibilityService::class, function ($app) {
            return new BonusEligibilityService(
                cache: $app->make('cache'),
                logger: $app->make(\Psr\Log\LoggerInterface::class),
            );
        });

        $this->app->singleton(BonusWithdrawalService::class, function ($app) {
            return new BonusWithdrawalService(
                db: $app->make(\Illuminate\Database\DatabaseManager::class),
                fraud: $app->make(\App\Services\FraudControlService::class),
                audit: $app->make(\App\Services\AuditService::class),
                walletService: $app->make(\App\Domains\Wallet\Services\WalletService::class),
                logger: $app->make(\Psr\Log\LoggerInterface::class),
            );
        });
    }

    public function boot(): void
    {
        // Load routes
        $this->loadRoutesFrom(__DIR__ . '/../routes/bonuses.php');

        // Load migrations
        $this->loadMigrationsFrom(__DIR__ . '/../../database/migrations');

        // Load views if any
        $this->loadViewsFrom(__DIR__ . '/../resources/views', 'bonuses');

        // Publish configuration
        $this->publishes([
            __DIR__ . '/../../config/bonuses.php' => config_path('bonuses.php'),
        ], 'bonuses-config');

        // Publish migrations
        $this->publishes([
            __DIR__ . '/../../database/migrations' => database_path('migrations'),
        ], 'bonuses-migrations');

        // Register event listeners
        $this->registerEventListeners();
    }

    private function registerEventListeners(): void
    {
        // BonusAwarded event listeners
        \Illuminate\Support\Facades\Event::listen(
            \App\Domains\Bonuses\Events\BonusAwarded::class,
            [
                \App\Domains\Bonuses\Listeners\TrackBonusInBigData::class,
                \App\Domains\Bonuses\Listeners\UpdateRFMScore::class,
                \App\Domains\Bonuses\Listeners\SendBonusNotification::class,
            ]
        );

        // BonusSpent event listeners
        \Illuminate\Support\Facades\Event::listen(
            \App\Domains\Bonuses\Events\BonusSpent::class,
            [
                \App\Domains\Bonuses\Listeners\TrackBonusSpendInBigData::class,
                \App\Domains\Bonuses\Listeners\UpdateRFMScore::class,
            ]
        );

        // BonusWithdrawn event listeners
        \Illuminate\Support\Facades\Event::listen(
            \App\Domains\Bonuses\Events\BonusWithdrawn::class,
            [
                \App\Domains\Bonuses\Listeners\TrackWithdrawalInBigData::class,
                \App\Domains\Bonuses\Listeners\SendWithdrawalNotification::class,
            ]
        );

        // BonusUnlocked event listeners
        \Illuminate\Support\Facades\Event::listen(
            \App\Domains\Bonuses\Events\BonusUnlocked::class,
            [
                \App\Domains\Bonuses\Listeners\SendBonusUnlockedNotification::class,
            ]
        );
    }
}
