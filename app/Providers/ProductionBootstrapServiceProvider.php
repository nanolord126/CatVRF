<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use App\Services\Infrastructure\RateLimiterService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class ProductionBootstrapServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        // 100k req/min: Оптимизация провайдеров
        if ($this->app->environment('production')) {
            // Отключаем лишнее логирование или дебаг-сервисы здесь если нужно
        }
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // 1. Инициализация продвинутых лимитов
        RateLimiterService::configure();

        // 2. High Load: Database Optimization
        if ($this->app->environment('production')) {
            // Логируем только медленные запросы (> 1сек)
            DB::listen(function ($query) {
                if ($query->time > 1000) {
                    Log::warning('Slow query detected', [
                        'sql' => $query->sql,
                        'time' => $query->time,
                    ]);
                }
            });
        }
    }
}
