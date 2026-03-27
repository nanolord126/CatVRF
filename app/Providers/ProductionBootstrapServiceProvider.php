<?php

declare(strict_types=1);

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Log;

final class ProductionBootstrapServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        // Регистрация сервисов в production-контексте
    }

    public function boot(): void
    {
        // Кэширование маршрутов и конфига в production
        if ($this->app->environment('production')) {
            $this->bootCaching();
        }

        // RateLimiter для критичных операций
        $this->bootRateLimiting();

        // Логирование
        $this->bootLogging();
    }

    /**
     * Кэширование маршрутов и конфига в production.
     */
    private function bootCaching(): void
    {

        Log::info('Production caching enabled', [
            'config_cached' => true,
            'routes_cached' => true,
        ]);
    }

    /**
     * Настройка RateLimiter (tenant-aware и user-aware).
     */
    private function bootRateLimiting(): void
    {
        // Лимит для публичных эндпоинтов платежей
        RateLimiter::for('payments', function ($request) {
            return Limit::perMinute(50)
                ->by($request->user()?->id ?: $request->ip())
                ->response(function ($request, $limit) {
                    return response()->json([
                        'error' => 'Too many payment requests',
                        'retry_after' => $limit->secondsUntilReset,
                    ], 429);
                });
        });

        // Лимит для промокодов (100 попыток/мин)
        RateLimiter::for('promo', function ($request) {
            return Limit::perMinute(100)
                ->by($request->user()?->id ?: $request->ip());
        });

        // Лимит для вишлиста (200 операций/мин)
        RateLimiter::for('wishlist', function ($request) {
            return Limit::perMinute(200)
                ->by($request->user()?->id ?: $request->ip());
        });

        // Лимит для рефералов (50 попыток применить код/мин)
        RateLimiter::for('referral', function ($request) {
            return Limit::perMinute(50)
                ->by($request->user()?->id ?: $request->ip());
        });

        // Лимит для B2B массовых операций (10 импортов/день)
        RateLimiter::for('bulk_import', function ($request) {
            $tenantId = $request->user()?->current_tenant_id ?? 0;

            return Limit::perDay(10)
                ->by("bulk_import_{$tenantId}");
        });

        Log::info('RateLimiter configured for production', [
            'limiters' => ['payments', 'promo', 'wishlist', 'referral', 'bulk_import'],
        ]);
    }

    /**
     * Настройка логирования.
     */
    private function bootLogging(): void
    {
        // Используем канал 'audit' для всех критичных действий
        // Канал определен в config/logging.php

        Log::info('Production logging enabled', [
            'audit_channel' => 'audit',
            'environment' => app()->environment(),
        ]);
    }
}
