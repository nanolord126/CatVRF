<?php declare(strict_types=1);

namespace App\Providers;

use App\Services\Infrastructure\DopplerService;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;

use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Str;
use Illuminate\Log\LogManager;

final class ProductionBootstrapServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        if ($this->app->environment('production')) {
            $this->bootProductionFeatures();
        }
    }

    /**
     * Boot production-specific features.
     */
    private function bootProductionFeatures(): void
    {
        $this->bootDoppler();
        $this->bootCaching();
        $this->bootRateLimiting();
        $this->bootLogging();
    }

    /**
     * Initialize Doppler for secrets management.
     */
    private function bootDoppler(): void
    {
        /** @var \Illuminate\Log\LogManager $logger */
        $logger = $this->app->make('log');
        try {
            DopplerService::initialize();
            $logger->info('Doppler service initialized successfully.');
        } catch (\Throwable $e) {
            $logger->critical('Failed to initialize Doppler service.', [
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Configure production caching.
     */
    private function bootCaching(): void
    {
        /** @var \Illuminate\Log\LogManager $logger */
        $logger = $this->app->make('log');
        if (app()->configurationIsCached() && app()->routesAreCached()) {
            $logger->info('Production caching is active.');
        } else {
            $logger->warning('Production environment is running without cached config or routes.');
        }
    }

    /**
     * Configure rate limiters.
     */
    private function bootRateLimiting(): void
    {
        RateLimiter::for('api', function (Request $request) {
            return Limit::perMinute(120)->by($request->user()?->id ?: $request->ip());
        });

        RateLimiter::for('payments', function (Request $request) {
            return Limit::perMinute(60)->by($request->ip());
        });

        RateLimiter::for('promo', function (Request $request) {
            return Limit::perMinute(100)->by($request->user()?->id ?: $request->ip());
        });

        RateLimiter::for('wishlist', function (Request $request) {
            return Limit::perMinute(200)->by($request->user()?->id ?: $request->ip());
        });

        RateLimiter::for('referral', function (Request $request) {
            return Limit::perMinute(50)->by($request->user()?->id ?: $request->ip());
        });

        RateLimiter::for('bulk_import', function (Request $request) {
            $tenantId = $request->user()?->current_tenant_id ?? 0;
            return Limit::perDay(10)
                ->by("bulk_import_{$tenantId}");
        });
    }

    /**
     * Configure production logging.
     */
    private function bootLogging(): void
    {
        /** @var \Illuminate\Log\LogManager $logger */
        $logger = $this->app->make('log');
        /** @var \Illuminate\Http\Request $request */
        $request = $this->app->make('request');
        $logger->shareContext([
            'correlation_id' => $request->header('X-Correlation-ID') ?? Str::uuid()->toString(),
        ]);
    }
}
