<?php

declare(strict_types=1);

namespace Modules\Payments\Providers;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;
use Modules\Payments\Adapters\FraudCheckAdapter;
use Modules\Payments\Adapters\WalletAdapter;
use Modules\Payments\Domain\Events\PaymentCaptured;
use Modules\Payments\Domain\Events\PaymentFailed;
use Modules\Payments\Domain\Events\PaymentInitiated;
use Modules\Payments\Domain\Repositories\IdempotencyRepositoryInterface;
use Modules\Payments\Domain\Repositories\PaymentRepositoryInterface;
use Modules\Payments\Infrastructure\Gateways\TinkoffBusinessGateway;
use Modules\Payments\Infrastructure\Repositories\EloquentIdempotencyRepository;
use Modules\Payments\Infrastructure\Repositories\EloquentPaymentRepository;
use Modules\Payments\Jobs\CleanupIdempotencyJob;
use Modules\Payments\Listeners\DepositWalletOnPaymentCaptured;
use Modules\Payments\Ports\FraudCheckPort;
use Modules\Payments\Ports\PaymentGatewayPort;
use Modules\Payments\Ports\WalletPort;

/**
 * ServiceProvider: Модуль Payments.
 * Layer 9 — Providers.
 *
 * Регистрирует все биндинги портов, роуты, события.
 */
final class PaymentsServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        // Ports → Implementations (DIP)
        $this->app->bind(PaymentRepositoryInterface::class, EloquentPaymentRepository::class);
        $this->app->bind(IdempotencyRepositoryInterface::class, EloquentIdempotencyRepository::class);
        $this->app->bind(FraudCheckPort::class, FraudCheckAdapter::class);
        $this->app->bind(WalletPort::class, WalletAdapter::class);

        // Gateway — Tinkoff Business по умолчанию
        $this->app->bind(PaymentGatewayPort::class, TinkoffBusinessGateway::class);
    }

    public function boot(): void
    {
        // Routes
        Route::middleware(['api', 'auth:sanctum'])
            ->prefix('api/v1')
            ->group(__DIR__ . '/../Routes/api.php');

        // Webhook routes (без auth — но с IP whitelist middleware)
        Route::middleware(['api', 'ip-whitelist'])
            ->prefix('api/internal/payments')
            ->group(__DIR__ . '/../Routes/webhooks.php');

        // Events
        $this->listenEvents();

        // Scheduler
        $this->callAfterResolving(Schedule::class, function (Schedule $schedule): void {
            $schedule->job(CleanupIdempotencyJob::class)
                ->daily()
                ->withoutOverlapping();
        });
    }

    private function listenEvents(): void
    {
        $this->app['events']->listen(
            PaymentCaptured::class,
            DepositWalletOnPaymentCaptured::class
        );
    }
}
