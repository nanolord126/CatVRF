<?php

declare(strict_types=1);

namespace App\Domains\Bonuses\Infrastructure;

use App\Domains\Bonuses\Interfaces\DailyActivityRepositoryInterface;
use App\Domains\Bonuses\Interfaces\FloatYieldRepositoryInterface;
use App\Domains\Bonuses\Interfaces\LockedBonusRepositoryInterface;
use App\Domains\Bonuses\Infrastructure\Repositories\DailyActivityRepository;
use App\Domains\Bonuses\Infrastructure\Repositories\FloatYieldRepository;
use App\Domains\Bonuses\Infrastructure\Repositories\LockedBonusRepository;
use Illuminate\Support\ServiceProvider;

/**
 * CatFloatServiceProvider - Service provider for CatFloat Rewards
 * 
 * Binds repository interfaces to their implementations.
 */
final class CatFloatServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(LockedBonusRepositoryInterface::class, LockedBonusRepository::class);
        $this->app->bind(DailyActivityRepositoryInterface::class, DailyActivityRepository::class);
        $this->app->bind(FloatYieldRepositoryInterface::class, FloatYieldRepository::class);
    }

    public function boot(): void
    {
        // Load routes
        $this->loadRoutesFrom(base_path('routes/api/catfloat.php'));

        // Load migrations
        $this->loadMigrationsFrom(__DIR__ . '/../Database/Migrations');
    }
}
