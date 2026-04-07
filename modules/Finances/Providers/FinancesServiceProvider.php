<?php

declare(strict_types=1);

namespace Modules\Finances\Providers;

use App\Services\FraudControlService;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Support\ServiceProvider;
use Modules\Finances\Interfaces\PaymentGatewayInterface;
use Modules\Finances\Interfaces\WalletServiceInterface;
use Modules\Finances\Services\TinkoffPaymentGateway;
use Modules\Finances\Services\WalletService;

final class FinancesServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(WalletServiceInterface::class, WalletService::class);

        $this->app->bind(PaymentGatewayInterface::class, function (Application $app) {
            // Here we can add logic to switch between different payment gateways
            // based on config or tenant settings.
            return new TinkoffPaymentGateway(
                $app->make(FraudControlService::class),
                $app->make(WalletService::class)
            );
        });
    }

    public function boot(): void
    {
        //
    }
}
