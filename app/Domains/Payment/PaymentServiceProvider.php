<?php

declare(strict_types=1);

namespace App\Domains\Payment;

use App\Domains\Payment\Facades\Payment;
use App\Domains\Payment\Services\Gateways\TinkoffAcquiringAdapter;
use App\Domains\Payment\Services\Gateways\TochkaBankAdapter;
use App\Domains\Payment\Services\PaymentFacadeService;
use App\Domains\Payment\Services\SmartRoutingService;
use App\Domains\Payment\Services\EscrowService;
use App\Domains\Payment\Services\OutboxService;
use App\Domains\Payment\Services\RecurringPaymentService;
use App\Domains\Payment\Services\PayoutBatchService;
use App\Domains\Payment\Services\SplitPaymentService;
use App\Domains\Payment\Services\PayoutService;
use App\Domains\Wallet\Services\AtomicWalletService;
use App\Services\AuditService;
use App\Services\FraudControlService;
use App\Services\Payment\PaymentEngine;
use App\Services\Payment\PaymentGatewayService;
use Illuminate\Contracts\Redis\Factory as RedisFactory;
use Illuminate\Database\DatabaseManager;
use Illuminate\Http\Client\Factory as HttpClientFactory;
use Illuminate\Support\ServiceProvider;
use Psr\Log\LoggerInterface;

class PaymentServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        // Register Facade
        $this->app->singleton(PaymentFacadeService::class, function ($app) {
            return new PaymentFacadeService(
                paymentEngine: $app->make(PaymentEngine::class),
                gatewayService: $app->make(PaymentGatewayService::class),
                smartRouting: $app->make(SmartRoutingService::class),
                escrowService: $app->make(EscrowService::class),
                splitPayment: $app->make(SplitPaymentService::class),
                payoutService: $app->make(PayoutService::class),
                outboxService: $app->make(OutboxService::class),
                fraudControl: $app->make(FraudControlService::class),
                audit: $app->make(AuditService::class),
                db: $app->make(DatabaseManager::class),
                logger: $app->make(LoggerInterface::class),
                tinkoffAdapter: $app->make(TinkoffAcquiringAdapter::class),
                tochkaAdapter: $app->make(TochkaBankAdapter::class),
            );
        });

        // Register Smart Routing Service
        $this->app->singleton(SmartRoutingService::class, function ($app) {
            return new SmartRoutingService(
                redis: $app->make(RedisFactory::class),
                logger: $app->make(LoggerInterface::class),
            );
        });

        // Register Escrow Service
        $this->app->singleton(EscrowService::class, function ($app) {
            return new EscrowService(
                db: $app->make(DatabaseManager::class),
                walletService: $app->make(AtomicWalletService::class),
                audit: $app->make(AuditService::class),
                logger: $app->make(LoggerInterface::class),
            );
        });

        // Register Outbox Service
        $this->app->singleton(OutboxService::class, function ($app) {
            return new OutboxService(
                db: $app->make(DatabaseManager::class),
                audit: $app->make(AuditService::class),
                logger: $app->make(LoggerInterface::class),
            );
        });

        // Register Recurring Payment Service
        $this->app->singleton(RecurringPaymentService::class, function ($app) {
            return new RecurringPaymentService(
                db: $app->make(DatabaseManager::class),
                audit: $app->make(AuditService::class),
                logger: $app->make(LoggerInterface::class),
            );
        });

        // Register Payout Batch Service
        $this->app->singleton(PayoutBatchService::class, function ($app) {
            return new PayoutBatchService(
                db: $app->make(DatabaseManager::class),
                payoutService: $app->make(PayoutService::class),
                audit: $app->make(AuditService::class),
                logger: $app->make(LoggerInterface::class),
                tinkoffAdapter: $app->make(TinkoffAcquiringAdapter::class),
                tochkaAdapter: $app->make(TochkaBankAdapter::class),
            );
        });

        // Register Tinkoff Adapter
        $this->app->singleton(TinkoffAcquiringAdapter::class, function ($app) {
            return new TinkoffAcquiringAdapter(
                terminalKey: config('payment.tinkoff.terminal_key'),
                secretKey: config('payment.tinkoff.secret_key'),
                http: $app->make(HttpClientFactory::class),
                logger: $app->make(LoggerInterface::class),
            );
        });

        // Register Tochka Adapter
        $this->app->singleton(TochkaBankAdapter::class, function ($app) {
            return new TochkaBankAdapter(
                clientId: config('payment.tochka.client_id'),
                clientSecret: config('payment.tochka.client_secret'),
                apiKey: config('payment.tochka.token'),
                http: $app->make(HttpClientFactory::class),
                logger: $app->make(LoggerInterface::class),
            );
        });
    }

    public function boot(): void
    {
        // Register Facade alias
        $this->app->alias(PaymentFacadeService::class, 'payment.facade');

        // Load migrations
        $this->loadMigrationsFrom(__DIR__.'/../database/migrations');
    }
}
