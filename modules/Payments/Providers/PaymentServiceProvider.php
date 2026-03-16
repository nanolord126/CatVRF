<?php

declare(strict_types=1);

namespace Modules\Payments\Providers;

use Illuminate\Support\ServiceProvider;
use Modules\Payments\Services\PaymentService;
use Modules\Payments\Gateways\PaymentGatewayInterface;
use Modules\Payments\Gateways\TinkoffGateway;

final class PaymentServiceProvider extends ServiceProvider
{
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
