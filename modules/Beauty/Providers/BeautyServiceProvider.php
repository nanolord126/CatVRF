<?php

namespace Modules\Beauty\Providers;

use Illuminate\Support\ServiceProvider;
use Modules\Beauty\Models\BeautySalon;
use Modules\Beauty\Observers\BeautySalonObserver;
use Modules\Beauty\Services\BookingService;
use Modules\Beauty\Services\PaymentService;
use Modules\Payments\Gateways\TinkoffGateway;

class BeautyServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        // Register services as singletons
        $this->app->singleton(BookingService::class, function ($app) {
            return new BookingService(
                $app['db'],
                $app['log']
            );
        });

        $this->app->singleton(PaymentService::class, function ($app) {
            return new PaymentService(
                $app['db'],
                new TinkoffGateway(),
                $app['log']
            );
        });

        $this->app->singleton(TinkoffGateway::class, function ($app) {
            return new TinkoffGateway();
        });
    }

    public function boot(): void
    {
        // Register observer for auto wallet creation
        BeautySalon::observe(BeautySalonObserver::class);

        // Publish migrations
        $this->publishes([
            __DIR__ . '/../Migrations' => base_path('database/migrations'),
        ], 'beauty-migrations');

        // Load routes if they exist
        if (file_exists(__DIR__ . '/../routes.php')) {
            $this->loadRoutesFrom(__DIR__ . '/../routes.php');
        }
    }
}
