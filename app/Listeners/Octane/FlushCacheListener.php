<?php declare(strict_types=1);

namespace App\Listeners\Octane;

use Laravel\Octane\Events\RequestHandled;

final class FlushCacheListener
{
    public function handle(RequestHandled $event): void
    {
        // Flush non-persistent caches to avoid memory leaks
        if (config('octane.flush_views', false)) {
            view()->flushViewsCache();
        }

        // Reset stateful services
        if (config('octane.isolation', false)) {
            app('cache')->flush();
        }
    }
}
