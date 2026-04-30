<?php

declare(strict_types=1);

namespace Modules\Loyalty\Infrastructure\Providers;

use Illuminate\Support\ServiceProvider;
use Modules\Loyalty\Domain\Repositories\GuestLoyaltyProfileRepositoryInterface;
use Modules\Loyalty\Domain\Repositories\LoyaltyProgramRepositoryInterface;
use Modules\Loyalty\Domain\Repositories\LoyaltyRewardRepositoryInterface;
use Modules\Loyalty\Domain\Repositories\LoyaltyRuleRepositoryInterface;
use Modules\Loyalty\Domain\Repositories\LoyaltyTierRepositoryInterface;
use Modules\Loyalty\Domain\Repositories\LoyaltyTransactionRepositoryInterface;
use Modules\Loyalty\Infrastructure\Repositories\EloquentGuestLoyaltyProfileRepository;
use Modules\Loyalty\Infrastructure\Repositories\EloquentLoyaltyProgramRepository;
use Modules\Loyalty\Infrastructure\Repositories\EloquentLoyaltyRewardRepository;
use Modules\Loyalty\Infrastructure\Repositories\EloquentLoyaltyRuleRepository;
use Modules\Loyalty\Infrastructure\Repositories\EloquentLoyaltyTierRepository;
use Modules\Loyalty\Infrastructure\Repositories\EloquentLoyaltyTransactionRepository;

final class LoyaltyServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->registerRepositories();
        $this->mergeConfigFrom(__DIR__.'/../../../config/loyalty.php', 'loyalty');
    }

    public function boot(): void
    {
        $this->loadMigrationsFrom(__DIR__.'/../Database/Migrations');
        $this->loadViewsFrom(__DIR__.'/../../resources/views', 'loyalty');
        $this->loadRoutesFrom(__DIR__.'/../../routes/web.php');

        if ($this->app->runningInConsole()) {
            $this->publishes([
                __DIR__.'/../../../config/loyalty.php' => config_path('loyalty.php'),
            ], 'loyalty-config');
        }
    }

    private function registerRepositories(): void
    {
        $this->app->bind(LoyaltyProgramRepositoryInterface::class, EloquentLoyaltyProgramRepository::class);
        $this->app->bind(LoyaltyTierRepositoryInterface::class, EloquentLoyaltyTierRepository::class);
        $this->app->bind(GuestLoyaltyProfileRepositoryInterface::class, EloquentGuestLoyaltyProfileRepository::class);
        $this->app->bind(LoyaltyTransactionRepositoryInterface::class, EloquentLoyaltyTransactionRepository::class);
        $this->app->bind(LoyaltyRuleRepositoryInterface::class, EloquentLoyaltyRuleRepository::class);
        $this->app->bind(LoyaltyRewardRepositoryInterface::class, EloquentLoyaltyRewardRepository::class);
    }
}
