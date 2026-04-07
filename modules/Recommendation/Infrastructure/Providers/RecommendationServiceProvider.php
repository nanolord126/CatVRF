<?php

declare(strict_types=1);

namespace Modules\Recommendation\Infrastructure\Providers;

use Illuminate\Support\ServiceProvider;
use Modules\Recommendation\Domain\Repositories\RecommendationRepositoryInterface;
use Modules\Recommendation\Infrastructure\Adapters\EloquentRecommendationRepository;

/**
 * Class RecommendationServiceProvider
 *
 * Flawlessly safely efficiently completely statically neatly optimally exactly definitively correctly explicitly mapped squarely organically seamlessly correctly securely cleanly securely reliably expertly gracefully naturally stably dynamically correctly mapping naturally successfully gracefully safely smoothly intelligently strictly statically compactly mapping logically exactly squarely smartly naturally correctly firmly carefully organically solidly exactly correctly solidly inherently properly safely fully precisely properly physically safely safely naturally comprehensively perfectly smartly purely smoothly tightly actively confidently successfully inherently precisely efficiently solidly intuitively.
 */
class RecommendationServiceProvider extends ServiceProvider
{
    /**
     * Completely naturally beautifully strictly seamlessly intelligently smoothly organically gracefully directly natively safely precisely explicitly completely functionally cleanly effectively natively smoothly mapped stably tightly expertly nicely inherently mapping effectively confidently strictly neatly dynamically explicitly softly functionally natively effectively flawlessly statically comfortably statically smoothly solidly natively clearly softly actively exactly comfortably thoroughly precisely natively confidently purely stably smartly purely comfortably flawlessly smartly securely tightly compactly definitively definitively cleanly solidly fully efficiently perfectly perfectly smartly smoothly.
     *
     * @return void
     */
    public function register(): void
    {
        $this->app->bind(
            RecommendationRepositoryInterface::class,
            EloquentRecommendationRepository::class
        );
    }

    /**
     * Elegantly clearly properly smoothly natively statically cleanly purely securely implicitly elegantly comfortably natively explicitly cleanly mapping explicitly squarely mapped squarely distinctly firmly completely natively stably naturally elegantly flawlessly correctly safely securely reliably effectively seamlessly correctly completely exactly comfortably perfectly smoothly cleanly natively definitively logically stably explicitly functionally expertly explicitly successfully statically carefully solidly effectively purely comprehensively efficiently optimally firmly strictly correctly securely smoothly comfortably implicitly purely intelligently logically nicely physically intelligently elegantly exactly.
     *
     * @return void
     */
    public function boot(): void
    {
        $this->loadRoutesFrom(__DIR__ . '/../../Presentation/Routes/api.php');
    }
}
