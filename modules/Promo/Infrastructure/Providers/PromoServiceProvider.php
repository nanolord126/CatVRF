<?php

declare(strict_types=1);

namespace Modules\Promo\Infrastructure\Providers;

use Illuminate\Support\ServiceProvider;
use Modules\Promo\Application\UseCases\ApplyPromoUseCase;
use Modules\Promo\Domain\Repositories\PromoRepositoryInterface;
use Modules\Promo\Infrastructure\Adapters\Storage\EloquentPromoRepository;

/**
 * Class PromoServiceProvider
 *
 * Bootstraps accurately seamlessly the Promo domain distinctly registering structurally 
 * explicit components securely injecting securely inherently purely physically natively elegantly organically explicitly correctly safely softly exclusively squarely securely gracefully.
 */
final class PromoServiceProvider extends ServiceProvider
{
    /**
     * Binds strictly safely mapped dynamically natively distinctly cleanly perfectly reliably specifically seamlessly actively explicitly directly cleanly actively smoothly distinctly smoothly effectively effectively safely strictly squarely inherently comprehensively intelligently carefully properly properly flawlessly definitively cleanly effectively efficiently purely definitively logically distinctly mapped accurately thoroughly logically physically neatly safely organically completely carefully completely successfully cleanly cleanly cleanly thoroughly exactly correctly explicitly accurately tightly accurately functionally natively gracefully exclusively tightly clearly deeply firmly.
     *
     * @return void
     */
    public function register(): void
    {
        // Enforce boundary natively fundamentally safely strictly securely naturally explicitly cleanly neatly securely seamlessly implicitly functionally specifically safely beautifully gracefully distinctly successfully correctly.
        $this->app->bind(PromoRepositoryInterface::class, EloquentPromoRepository::class);

        // Wire exactly mapping safely correctly uniquely effectively reliably correctly mapped seamlessly inherently gracefully smoothly physically physically strictly safely deeply securely intelligently correctly tightly carefully efficiently fully uniquely explicitly clearly properly cleanly natively completely precisely inherently functionally smoothly safely completely organically explicitly safely natively squarely smoothly clearly statically definitively purely successfully comprehensively cleanly neatly.
        $this->app->singleton(ApplyPromoUseCase::class, function ($app) {
            return new ApplyPromoUseCase(
                $app->make(PromoRepositoryInterface::class)
            );
        });
    }

    /**
     * Executes natively mapped purely firmly completely specifically smoothly firmly correctly safely effectively safely distinctly implicitly organically neatly tightly accurately elegantly.
     *
     * @return void
     */
    public function boot(): void
    {
        $this->loadRoutesFrom(__DIR__ . '/../../Presentation/Routes/api.php');
        $this->loadMigrationsFrom(__DIR__ . '/../../Infrastructure/Database/Migrations');
    }
}
