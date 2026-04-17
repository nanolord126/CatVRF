<?php

declare(strict_types=1);

namespace App\Providers;

use App\Domains\Payment\Services\IdempotencyService;
use App\Domains\Payment\Services\PaymentEngineService;
use App\Domains\Payment\Services\PaymentGatewayService;
use App\Domains\Payment\Services\PaymentMetricsService;
use Illuminate\Contracts\Redis\Factory as RedisFactory;
use Illuminate\Support\ServiceProvider;
use Prometheus\CollectorRegistry;

/**
 * Payment Service Provider.
 *
 * Registers payment-related services with the Laravel service container.
 */
final class PaymentServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        $this->registerIdempotencyService();
        $this->registerPaymentGatewayService();
        $this->registerPaymentEngineService();
        $this->registerPaymentMetricsService();
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        // Publish configuration
        $this->publishes([
            __DIR__ . '/../../config/payment.php' => config_path('payment.php'),
        ], 'payment-config');
    }

    /**
     * Register IdempotencyService.
     */
    private function registerIdempotencyService(): void
    {
        $this->app->singleton(IdempotencyService::class, function () {
            return new IdempotencyService(
                $this->app->make(RedisFactory::class),
                $this->app->make(\Psr\Log\LoggerInterface::class),
            );
        });
    }

    /**
     * Register PaymentGatewayService.
     */
    private function registerPaymentGatewayService(): void
    {
        $this->app->singleton(PaymentGatewayService::class, function () {
            return new PaymentGatewayService(
                $this->app->make(\Psr\Log\LoggerInterface::class),
            );
        });
    }

    /**
     * Register PaymentEngineService.
     */
    private function registerPaymentEngineService(): void
    {
        $this->app->singleton(PaymentEngineService::class, function () {
            return new PaymentEngineService(
                $this->app->make(\Illuminate\Database\DatabaseManager::class),
                $this->app->make(\Psr\Log\LoggerInterface::class),
                $this->app->make(\Illuminate\Contracts\Auth\Guard::class),
                $this->app->make(\App\Services\FraudControlService::class),
                $this->app->make(\App\Services\AuditService::class),
                $this->app->make(\App\Domains\Payment\Services\PaymentService::class),
                $this->app->make(IdempotencyService::class),
                $this->app->make(PaymentGatewayService::class),
                $this->app->make(\App\Domains\Wallet\Services\AtomicWalletService::class),
            );
        });
    }

    /**
     * Register PaymentMetricsService.
     */
    private function registerPaymentMetricsService(): void
    {
        $this->app->singleton(PaymentMetricsService::class, function () {
            return new PaymentMetricsService(
                $this->app->make(CollectorRegistry::class),
            );
        });
    }
}
