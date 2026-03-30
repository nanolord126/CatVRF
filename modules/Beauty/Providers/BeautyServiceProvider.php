<?php declare(strict_types=1);

namespace Modules\Beauty\Providers;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

final class BeautyServiceProvider extends Model
{
    use HasFactory;

    // TODO: Проверить и восстановить содержимое класса, если оно было утеряно
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
