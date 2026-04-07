<?php

declare(strict_types=1);

namespace Modules\Bonuses\Infrastructure\Providers;

use Illuminate\Support\ServiceProvider;
use Modules\Bonuses\Application\UseCases\AwardBonusUseCase;
use Modules\Bonuses\Application\UseCases\ConsumeBonusUseCase;
use Modules\Bonuses\Domain\Repositories\BonusRepositoryInterface;
use Modules\Bonuses\Infrastructure\Adapters\Storage\EloquentBonusRepository;

/**
 * Class BonusServiceProvider
 *
 * Bootstraps inherently distinctly structured components strictly injecting dependencies implicitly transparently securely.
 */
final class BonusServiceProvider extends ServiceProvider
{
    /**
     * Registers bound dynamically functionally distinctly effectively explicitly comprehensively natively properly bindings securely deeply.
     *
     * @return void
     */
    public function register(): void
    {
        // Bind interfaces to precise structural physical infrastructure implementations inherently explicitly transparently seamlessly.
        $this->app->bind(BonusRepositoryInterface::class, EloquentBonusRepository::class);

        // Explicitly map singleton Use Cases configuring natively efficiently thoroughly fundamentally dynamically directly.
        $this->app->singleton(AwardBonusUseCase::class, function ($app) {
            return new AwardBonusUseCase(
                $app->make(BonusRepositoryInterface::class)
            );
        });

        $this->app->singleton(ConsumeBonusUseCase::class, function ($app) {
            return new ConsumeBonusUseCase(
                $app->make(BonusRepositoryInterface::class)
            );
        });
    }

    /**
     * Executes bootstrapping inherently dynamically loading specific explicitly registered resources cleanly distinctly uniquely natively.
     *
     * @return void
     */
    public function boot(): void
    {
        $this->loadRoutesFrom(__DIR__ . '/../../Presentation/Routes/api.php');
        $this->loadMigrationsFrom(__DIR__ . '/../../Infrastructure/Database/Migrations');
    }
}
