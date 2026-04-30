<?php

declare(strict_types=1);

namespace Modules\Bonuses;

use Illuminate\Support\ServiceProvider;
use Modules\Bonuses\Application\Jobs\ExpireBonusesJob;
use Modules\Bonuses\Application\Jobs\ProcessBonusAwardJob;
use Modules\Bonuses\Application\Jobs\ResetMonthlyLoyaltyTrackingJob;
use Modules\Bonuses\Application\Listeners\HandleBonusAwarded;
use Modules\Bonuses\Application\Listeners\HandleLoyaltyLevelChanged;
use Modules\Bonuses\Application\Listeners\SendBonusNotification;
use Modules\Bonuses\Application\Services\BonusesFacadeService;
use Modules\Bonuses\Domain\Events\BonusAwarded;
use Modules\Bonuses\Domain\Events\LoyaltyLevelChanged;
use Modules\Bonuses\Domain\Interfaces\BonusRuleEngineInterface;
use Modules\Bonuses\Domain\Interfaces\LoyaltyCalculatorInterface;
use Modules\Bonuses\Domain\Repositories\BonusProgramRepositoryInterface;
use Modules\Bonuses\Domain\Repositories\BonusRepositoryInterface;
use Modules\Bonuses\Domain\Repositories\LoyaltyStatusRepositoryInterface;
use Modules\Bonuses\Infrastructure\Repositories\EloquentBonusRepository;
use Modules\Bonuses\Infrastructure\Repositories\EloquentBonusProgramRepository;
use Modules\Bonuses\Infrastructure\Repositories\EloquentLoyaltyStatusRepository;
use Modules\Bonuses\Infrastructure\Services\BonusRuleEngine;
use Modules\Bonuses\Infrastructure\Services\LoyaltyCalculator;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Schedule;

/**
 * Class BonusesServiceProvider
 *
 * Service provider for the Bonuses vertical.
 * Binds interfaces to implementations, registers event listeners,
 * configures scheduled jobs, and loads routes.
 */
final class BonusesServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        // Bind Domain interfaces to Infrastructure implementations
        $this->app->bind(BonusRepositoryInterface::class, function () {
            return new EloquentBonusRepository(
                $this->app->make('db'),
                $this->app->make('db.connection')
            );
        });

        $this->app->bind(BonusProgramRepositoryInterface::class, function () {
            return new EloquentBonusProgramRepository();
        });

        $this->app->bind(LoyaltyStatusRepositoryInterface::class, function () {
            return new EloquentLoyaltyStatusRepository();
        });

        $this->app->bind(BonusRuleEngineInterface::class, function () {
            return new BonusRuleEngine(
                $this->app->make('config'),
                $this->app->make(BonusRepositoryInterface::class)
            );
        });

        $this->app->bind(LoyaltyCalculatorInterface::class, function () {
            return new LoyaltyCalculator(
                $this->app->make('config')
            );
        });

        // Bind Application facade service as singleton
        $this->app->singleton(BonusesFacadeService::class, function () {
            return new BonusesFacadeService(
                $this->app->make(BonusRepositoryInterface::class),
                $this->app->make(BonusProgramRepositoryInterface::class),
                $this->app->make(LoyaltyStatusRepositoryInterface::class),
                $this->app->make(BonusRuleEngineInterface::class),
                $this->app->make(LoyaltyCalculatorInterface::class),
                $this->app->make('cache'),
                $this->app->make('log'),
                $this->app->make(\App\Services\Security\AuditService::class),
                $this->app->has('fraud.control') ? $this->app->make('fraud.control') : null
            );
        });
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        // Register event listeners
        Event::listen(
            BonusAwarded::class,
            [HandleBonusAwarded::class, 'handle']
        );

        Event::listen(
            BonusAwarded::class,
            [SendBonusNotification::class, 'handle']
        );

        Event::listen(
            LoyaltyLevelChanged::class,
            [HandleLoyaltyLevelChanged::class, 'handle']
        );

        // Register scheduled jobs
        $this->registerScheduledJobs();

        // Load routes
        $this->loadRoutes();

        // Load migrations
        $this->loadMigrationsFrom(__DIR__ . '/../database/migrations');

        // Load config
        $this->mergeConfigFrom(
            __DIR__ . '/config/bonuses.php',
            'bonuses'
        );
    }

    /**
     * Register scheduled jobs for bonus maintenance.
     */
    private function registerScheduledJobs(): void
    {
        // Expire bonuses daily at midnight
        Schedule::command('bonuses:expire')->daily();

        // Reset monthly tracking on the first day of each month
        Schedule::command('bonuses:reset-monthly-tracking')->monthlyOn(1, '00:00');

        // Cleanup consumed/expired bonuses weekly
        Schedule::command('bonuses:cleanup')->weekly();
    }

    /**
     * Load the module routes.
     */
    private function loadRoutes(): void
    {
        $this->loadRoutesFrom(__DIR__ . '/Presentation/Routes/api.php');
    }
}
