<?php declare(strict_types=1);

namespace Modules\Payments\Providers;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

final class PaymentServiceProvider extends Model
{
    use HasFactory;

    // TODO: Проверить и восстановить содержимое класса, если оно было утеряно
    /**
         * Выполнить операцию
         * 
         * @return mixed
         * @throws \Exception
         */
        public function register(): void
        {
            $this->app->singleton(PaymentGatewayInterface::class, function ($app) {
                return match (config('payments.default_gateway')) {
                    'tinkoff' => new TinkoffGateway(
                        config('payments.gateways.tinkoff.terminal_key'),
                        config('payments.gateways.tinkoff.secret_key')
                    ),
                    default => new TinkoffGateway(
                        config('payments.gateways.tinkoff.terminal_key'),
                        config('payments.gateways.tinkoff.secret_key')
                    ),
                };
            });
    
            $this->app->singleton(PaymentService::class, function ($app) {
                return new PaymentService(
                    $app->make(PaymentGatewayInterface::class)
                );
            });
        }
    
        /**
         * Выполнить операцию
         * 
         * @return mixed
         * @throws \Exception
         */
        public function boot(): void
        {
            // Register migrations if they exist
            $this->loadMigrationsFrom(__DIR__.'/../Database/Migrations');
            
            // Register routes if they exist
            if (file_exists(__DIR__.'/../Routes/api.php')) {
                $this->loadRoutesFrom(__DIR__.'/../Routes/api.php');
            }
        }
}
