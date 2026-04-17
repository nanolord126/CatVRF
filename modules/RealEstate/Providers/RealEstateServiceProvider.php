<?php declare(strict_types=1);

namespace Modules\RealEstate\Providers;

use Illuminate\Support\ServiceProvider;
use Modules\RealEstate\Models\PropertyBooking;
use Modules\RealEstate\Observers\PropertyBookingObserver;

final class RealEstateServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(
            __DIR__ . '/../config/real_estate.php',
            'real_estate'
        );
    }

    public function boot(): void
    {
        $this->loadMigrationsFrom(__DIR__ . '/../../database/migrations/2026_01_01_000003_create_real_estate_bookings_table.php');
        $this->loadRoutesFrom(__DIR__ . '/../routes/api.php');
        $this->loadViewsFrom(__DIR__ . '/../resources/views', 'real_estate');

        PropertyBooking::observe(PropertyBookingObserver::class);
        Property::observe(\Modules\RealEstate\Observers\PropertyObserver::class);

        if ($this->app->runningInConsole()) {
            $this->publishes([
                __DIR__ . '/../config/real_estate.php' => config_path('real_estate.php'),
            ], 'real_estate-config');
        }
    }
}
