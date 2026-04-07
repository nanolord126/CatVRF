<?php

declare(strict_types=1);

namespace Modules\Common\Providers;

use Illuminate\Support\ServiceProvider;
use Modules\Payments\Gateways\PaymentGatewayInterface;
use Modules\Payments\Gateways\TinkoffGateway;
use Modules\Payments\Services\IdempotencyService;
use Modules\Payments\Services\PaymentsService;
use Modules\Wallet\Services\WalletService;

final class CommonServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        // Bind the gateway interface to a concrete implementation
        $this->app->bind(PaymentGatewayInterface::class, TinkoffGateway::class);

        // Register PaymentsService
        $this->app->singleton(PaymentsService::class, function ($app) {
            return new PaymentsService(
                $app->make(PaymentGatewayInterface::class),
                $app->make(IdempotencyService::class)
            );
        });

        // Register WalletService
        $this->app->singleton(WalletService::class, function () {
            return new WalletService();
        });
    }

    public function provides(): array
    {
        return [
            PaymentGatewayInterface::class,
            PaymentsService::class,
            WalletService::class,
        ];
    }
}
