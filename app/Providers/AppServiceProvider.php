<?php

declare(strict_types=1);

namespace App\Providers;

use App\Services\Payment\Gateways\SberGateway;
use App\Services\Payment\Gateways\TinkoffGateway;
use App\Services\Payment\Gateways\TochkaGateway;
use App\Services\Payment\PaymentGatewayService;
use Illuminate\Support\ServiceProvider;

final class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        // Core Security Services (singleton)
        $this->app->singleton(\App\Services\Security\IdempotencyService::class);
        $this->app->singleton(\App\Services\Security\WebhookSignatureService::class);
        $this->app->singleton(\App\Services\Security\RateLimiterService::class);
        $this->app->singleton(\App\Services\Security\TenantAwareRateLimiter::class);

        // Payment Security Services
        $this->app->singleton(\App\Services\Payment\PaymentIdempotencyService::class);
        $this->app->singleton(\App\Services\Webhook\WebhookSignatureValidator::class);

        // ML & Recommendation Services
        $this->app->singleton(\App\Services\SearchRankingService::class);

        // Fraud Detection
        $this->app->singleton(\App\Services\FraudControlService::class);
        $this->app->singleton(\App\Services\Wishlist\WishlistAntiFraudService::class);

        // Payment Gateway: bind concrete gateway classes
        $this->app->bind(TinkoffGateway::class, fn () => new TinkoffGateway(
            terminalKey: (string) config('services.tinkoff.terminal_key', 'test_terminal'),
            secretKey:   (string) config('services.tinkoff.secret_key', 'test_secret'),
        ));

        $this->app->bind(TochkaGateway::class, fn () => new TochkaGateway(
            clientId:     (string) config('services.tochka.client_id', 'test_client'),
            clientSecret: (string) config('services.tochka.client_secret', 'test_secret'),
            apiKey:       (string) config('services.tochka.api_key', 'test_key'),
        ));

        $this->app->bind(SberGateway::class, fn () => new SberGateway(
            username:   (string) config('services.sber.username', 'test_user'),
            password:   (string) config('services.sber.password', 'test_pass'),
            merchantId: (string) config('services.sber.merchant_id', 'test_merchant'),
        ));

        $this->app->bind(PaymentGatewayService::class, fn ($app) => new PaymentGatewayService(
            tinkoff: $app->make(TinkoffGateway::class),
            tochka:  $app->make(TochkaGateway::class),
            sber:    $app->make(SberGateway::class),
        ));
    }

    public function boot(): void
    {
        //
    }
}

