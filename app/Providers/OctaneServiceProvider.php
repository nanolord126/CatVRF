<?php

declare(strict_types=1);

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use App\Octane\Services\SwooleTableService;

final class OctaneServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(SwooleTableService::class, fn() => new SwooleTableService());
    }

    public function boot(): void
    {
        // Register Swoole table service facade if needed
        if ($this->app->runningInConsole()) {
            $this->commands([
                // Octane-related commands can be registered here
            ]);
        }
    }
}
