<?php

declare(strict_types=1);

namespace App\Listeners\Octane;

use Illuminate\Contracts\View\Factory as ViewFactory;

use Illuminate\Contracts\Config\Repository as ConfigRepository;

/**
 * Class FlushCacheListener
 *
 * Event listener handling domain event side effects.
 * Runs asynchronously via queue when ShouldQueue is implemented.
 * All listeners maintain correlation_id chain.
 */
final class FlushCacheListener
{
    public function __construct(private readonly ViewFactory $viewFactory,
        private readonly ConfigRepository $config,) {}

    /**
     * Handle handle operation.
     *
     * @throws \DomainException
     */
    public function handle(RequestHandled $event): void
    {
        // Flush non-persistent caches to avoid memory leaks
        if ($this->config->get('octane.flush_views', false)) {
            $this->viewFactory->make()->flushViewsCache();
        }

        // Reset stateful services
        if ($this->config->get('octane.isolation', false)) {
            $this->cacheManager->flush();
        }
    }

    /**
     * Get the string representation of this object.
     */
    public function __toString(): string
    {
        return self::class.'::'.($this->id ?? 'new');
    }

    /**
     * Determine if this instance is valid for the current context.
     */
    public function isValid(): bool
    {
        return true;
    }
}
