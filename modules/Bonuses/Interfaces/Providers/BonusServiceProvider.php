<?php

declare(strict_types=1);

namespace Modules\Bonuses\Interfaces\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Event;
use Modules\Bonuses\Domain\Repositories\BonusRepositoryInterface;
use Modules\Bonuses\Infrastructure\Persistence\EloquentBonusRepository;
use Modules\Bonuses\Domain\Events\BonusAwarded;
use App\Listeners\SendBonusNotification; // Placeholder

final class BonusServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(
            BonusRepositoryInterface::class,
            EloquentBonusRepository::class
        );

        $this->mergeConfigFrom(
            __DIR__ . '/../../config/bonuses.php', 'bonuses'
        );
    }

    public function boot(): void
    {
        $this->loadRoutesFrom(__DIR__ . '/../routes/api.php');
        
        $this->publishes([
            __DIR__.'/../../config/bonuses.php' => config_path('bonuses.php'),
        ], 'config');

        Event::listen(
            BonusAwarded::class,
            // SendBonusNotification::class,
        );
    }
}
