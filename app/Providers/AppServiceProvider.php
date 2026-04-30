<?php declare(strict_types=1);

namespace App\Providers;


use Illuminate\Contracts\Config\Repository as ConfigRepository;
use Illuminate\Support\Facades\Notification;
use App\Domains\Content\Channels\Services\ChannelService;
use App\Domains\Content\Channels\Services\ChannelSubscriptionService;
use App\Domains\Content\Channels\Services\ChannelTariffService;
use App\Domains\Content\Channels\Services\PostService;
use App\Domains\Content\Channels\Services\ReactionService;
use App\Services\SearchRankingService;
use App\Services\Security\RateLimiterService;
use App\Services\Security\TenantAwareRateLimiter;
use App\Services\Security\WishlistAntiFraudService;
use App\Services\Tenancy\TenantQuotaNotificationService;
use App\Services\Tenancy\TenantQuotaPersistenceService;
use App\Services\Tenancy\TenantQuotaPlanService;
use App\Services\Webhook\WebhookSignatureValidator;
use Illuminate\Http\Client\PendingRequest;
use App\Domains\FraudML\Services\PaymentFraudMLService;
use App\Domains\FraudML\Services\PaymentFraudMLHelper;
use App\Domains\FraudML\Services\PaymentFraudMLShadowService;
use App\Providers\Prometheus\PaymentFraudMLMetricsCollector;
use App\Domains\Shared\Medical\Psychology\Services\AITherapyConstructorService;
use App\Domains\Shared\Medical\Psychology\Services\PsychologicalPricingService;
use App\Domains\Shared\Medical\Psychology\Services\PsychologicalService;
use App\Domains\Shared\Realtime\Services\RealtimeScalingService;
use App\Services\FraudControlService;
use App\Services\Geo\GeoTerritoryService;
use App\Services\Payment\Gateways\SberGateway;
use App\Services\Payment\Gateways\TinkoffGateway;
use App\Services\Payment\Gateways\TochkaGateway;
use App\Services\Payment\PaymentGatewayService;
use App\Services\Payment\PaymentIdempotencyService;
use App\Services\Tenancy\TenantCacheService;
use App\Services\Tenancy\TenantResourceLimiterService;
use Illuminate\Support\ServiceProvider;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Log\LogManager;
use Livewire\Livewire;

final class AppServiceProvider extends ServiceProvider
{

    public function register(): void
    {
        // PSR LoggerInterface — глобальный fallback на audit-канал
        // (допустимо в ServiceProvider как infrastructure config)
        $this->app->bind(\Psr\Log\LoggerInterface::class, fn () => $this->app->make('log')->channel('audit'));

        // Multi-Tenant Services (singleton)
        $this->app->singleton(TenantCacheService::class);
        $this->app->singleton(TenantResourceLimiterService::class);

        // Payment Security Services
        $this->app->singleton(PaymentIdempotencyService::class);
        $this->app->singleton(WebhookSignatureValidator::class);

        // Rate Limiting Services
        $this->app->singleton(RateLimiterService::class);
        $this->app->singleton(TenantAwareRateLimiter::class);

        // Tenant Quota Services
        $this->app->singleton(TenantQuotaPlanService::class);
        $this->app->singleton(TenantQuotaPersistenceService::class);
        $this->app->singleton(TenantQuotaNotificationService::class);

        // ML & Recommendation Services
        $this->app->singleton(SearchRankingService::class);

        // Channels Domain Services (singleton)
        $this->app->singleton(ChannelService::class);
        $this->app->singleton(PostService::class);
        $this->app->singleton(ReactionService::class);
        $this->app->singleton(ChannelTariffService::class);
        $this->app->singleton(ChannelSubscriptionService::class);

        // Psychology Domain Services
        $this->app->singleton(PsychologicalService::class);
        $this->app->singleton(AITherapyConstructorService::class);
        $this->app->singleton(PsychologicalPricingService::class);

        // Fraud DeFraud ML Services
        $this->app->singleton(PaymentFraudMLService::class);
        $this->app->singleton(PaymentFraudMLHelper::class);
        $this->app->singleton(PaymentFraudMLShadowService::class);
        $this->app->singleton(PaymentFraudMLMetricsCollector::class);

        // Payment tection
        $this->app->singleton(FraudControlService::class);
        $this->app->singleton(WishlistAntiFraudService::class);

        // Geo Territory Service
        $this->app->singleton(GeoTerritoryService::class);

        // Payment Gateway: bind concrete gateway classes
        $this->app->bind(TinkoffGateway::class, fn ($app) => new TinkoffGateway(
            terminalKey: (string) $app->make(ConfigRepository::class)->get('services.tinkoff.terminal_key', 'test_terminal'),
            secretKey:   (string) $app->make(ConfigRepository::class)->get('services.tinkoff.secret_key', 'test_secret'),
            http: $app->make(PendingRequest::class),
            log: $app->make(LogManager::class),
            fraud: $app->make(FraudControlService::class)
        ));

        $this->app->bind(TochkaGateway::class, fn ($app) => new TochkaGateway(
            clientId:     (string) $app->make(ConfigRepository::class)->get('services.tochka.client_id', 'test_client'),
            clientSecret: (string) $app->make(ConfigRepository::class)->get('services.tochka.client_secret', 'test_secret'),
            apiKey:       (string) $app->make(ConfigRepository::class)->get('services.tochka.api_key', 'test_key'),
            http: $app->make(PendingRequest::class),
            log: $app->make(LogManager::class),
            fraud: $app->make(FraudControlService::class)
        ));

        $this->app->bind(SberGateway::class, fn ($app) => new SberGateway(
            username:   (string) $app->make(ConfigRepository::class)->get('services.sber.username', 'test_user'),
            password:   (string) $app->make(ConfigRepository::class)->get('services.sber.password', 'test_pass'),
            merchantId: (string) $app->make(ConfigRepository::class)->get('services.sber.merchant_id', 'test_merchant'),
            http: $app->make(PendingRequest::class),
            log: $app->make(LogManager::class),
            fraud: $app->make(FraudControlService::class)
        ));

        $this->app->bind(PaymentGatewayService::class, fn ($app) => new PaymentGatewayService(
            tinkoff: $app->make(TinkoffGateway::class),
            tochka:  $app->make(TochkaGateway::class),
            sber:    $app->make(SberGateway::class),
        ));
    }

    public function boot(): void
    {
        Model::preventLazyLoading(! app()->isProduction());
        Model::preventSilentlyDiscardingAttributes(! app()->isProduction());

        JsonResource::withoutWrapping();

        // Register Geo facade alias
        $this->app->alias(GeoTerritoryService::class, 'geo');

        // Register Telegram notification channel
        Notification::extend('telegram', function ($app) {
            return new TelegramChannel($app->make(TelegramBotService::class));
        });

        // Register Job Middleware for Horizon queues
        // Note: Queue middleware is registered in config/horizon.php

        // Livewire — Channels
        Livewire::component('channels.business-news-feed', \App\Livewire\Channels\BusinessNewsFeed::class);
        Livewire::component('channels.post-card',          \App\Livewire\Channels\PostCard::class);
        Livewire::component('channels.reaction-picker',    \App\Livewire\Channels\ReactionPicker::class);
        Livewire::component('channels.follow-button',      \App\Livewire\Channels\FollowButton::class);

        // Register WebSocket Scaling Prometheus Metrics
        // $this->registerWebSocketMetrics(); // Temporarily disabled for migration
    }

    /**
     * Register Prometheus metrics for WebSocket scaling across 28 verticals
     */
    private function registerWebSocketMetrics(): void
    {
        if (!config('broadcasting.vertical_scaling.monitoring.enabled', false)) {
            return;
        }

        $prometheus = app('prometheus');
        if (!$prometheus) {
            return;
        }

        $namespace = config('broadcasting.vertical_scaling.monitoring.metrics_prefix', 'broadcast');

        // Global WebSocket metrics
        $prometheus->registerGauge(
            "{$namespace}_concurrent_connections_total",
            'Total concurrent WebSocket connections across all Reverb instances'
        );

        $prometheus->registerCounter(
            "{$namespace}_messages_total",
            'Total broadcast messages sent'
        );

        $prometheus->registerGauge(
            "{$namespace}_messages_per_second",
            'Messages per second rate'
        );

        $prometheus->registerHistogram(
            "{$namespace}_message_latency_ms",
            'Message delivery latency in milliseconds',
            [0.1, 0.5, 1, 5, 10, 50, 100, 500, 1000]
        );

        // Per-vertical metrics (only for active verticals)
        $verticals = config('verticals.verticals', []);
        foreach ($verticals as $slug => $config) {
            if (!($config['active'] ?? false)) {
                continue;
            }

            $prometheus->registerCounter(
                "{$namespace}_vertical_{$slug}_messages_total",
                "Total messages for {$slug} vertical"
            );

            $prometheus->registerGauge(
                "{$namespace}_vertical_{$slug}_connections_total",
                "Concurrent connections for {$slug} vertical"
            );

            $prometheus->registerCounter(
                "{$namespace}_vertical_{$slug}_rate_limit_exceeded_total",
                "Rate limit breaches for {$slug} vertical"
            );
        }

        // Redis metrics
        $prometheus->registerHistogram(
            "{$namespace}_redis_latency_ms",
            'Redis pub/sub latency in milliseconds',
            [0.1, 0.5, 1, 2, 5, 10, 20, 50, 100]
        );

        // Reverb instance metrics
        $prometheus->registerGauge(
            "{$namespace}_reverb_instance_memory_bytes",
            'Memory usage per Reverb instance'
        );

        $prometheus->registerGauge(
            "{$namespace}_reverb_instance_connections",
            'Connections per Reverb instance'
        );
    }
}
