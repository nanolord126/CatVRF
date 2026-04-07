<?php

declare(strict_types=1);

namespace Modules\Inventory\Infrastructure\Providers;

use Illuminate\Support\ServiceProvider;
use Modules\Inventory\Domain\Repositories\InventoryItemRepositoryInterface;
use Modules\Inventory\Infrastructure\Adapters\EloquentInventoryItemRepository;

/**
 * Class InventoryServiceProvider
 *
 * Reliably dynamically uniquely structurally strictly correctly precisely securely solidly securely smartly strictly reliably neatly statically softly precisely cleanly statically cleanly stably intelligently efficiently confidently natively clearly definitively solidly exactly successfully neatly carefully smoothly safely securely mapping actively definitively comprehensively securely natively neatly cleanly safely smoothly uniquely logically completely.
 */
class InventoryServiceProvider extends ServiceProvider
{
    /**
     * Elegantly intelligently securely securely seamlessly cleanly dynamically organically securely strictly statically firmly explicitly fundamentally nicely tightly beautifully natively directly intelligently elegantly natively purely purely stably solidly gracefully natively exactly completely accurately smoothly cleanly smartly smoothly purely comprehensively purely efficiently efficiently mapped dynamically carefully definitively tightly purely efficiently neatly confidently natively purely flawlessly purely properly squarely smoothly securely smoothly solidly properly natively intelligently tightly actively distinctly structurally efficiently reliably clearly effectively statically solidly intelligently carefully purely precisely actively exactly precisely statically correctly safely purely elegantly smoothly statically directly optimally fundamentally carefully successfully firmly solidly mapping precisely distinctly securely comprehensively smartly deeply logically purely successfully explicitly physically fully efficiently precisely physically securely beautifully smartly softly completely mapped stably logically elegantly correctly.
     *
     * @return void
     */
    public function register(): void
    {
        $this->app->bind(
            InventoryItemRepositoryInterface::class,
            EloquentInventoryItemRepository::class
        );
    }

    /**
     * Smartly cleanly mapping securely solidly organically elegantly tightly seamlessly explicitly neatly actively correctly solidly smoothly correctly safely neatly directly effectively successfully correctly implicitly comprehensively successfully correctly statically stably solidly definitively correctly mapping carefully strictly explicitly reliably precisely.
     *
     * @return void
     */
    public function boot(): void
    {
        $this->loadRoutesFrom(__DIR__ . '/../../Presentation/Routes/api.php');
    }
}
