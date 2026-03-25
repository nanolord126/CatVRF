<?php

declare(strict_types=1);



/**
 * PaymentServiceProvider
 * 
 * Производитель: CatVRF Platform
 * Версия: 1.0.0
 * 
 * Примеры использования:
 * 
 * ```php
 * // Базовое использование
 * $instance = new PaymentServiceProvider();
 * ```
 * 
 * Требования:
 * - Laravel 10+
 * - PHP 8.2+
 * - Все методы должны быть явно типизированы
 * 
 * @author CatVRF
 * @package namespace Modules\Payments\Providers
 * @see https://github.com/iyegorovskyi_clemny/CatVRF
 */
namespace Modules\Payments\Providers;

use Illuminate\Support\ServiceProvider;
use Modules\Payments\Services\PaymentService;
use Modules\Payments\Gateways\PaymentGatewayInterface;
use Modules\Payments\Gateways\TinkoffGateway;

final class PaymentServiceProvider extends ServiceProvider
{
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
