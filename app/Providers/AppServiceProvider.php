<?php declare(strict_types=1);

namespace App\Providers;


use Illuminate\Contracts\Config\Repository as ConfigRepository;
use App\Domains\Content\Channels\Services\ChannelService;
use App\Domains\Content\Channels\Services\ChannelSubscriptionService;
use App\Domains\Content\Channels\Services\ChannelTariffService;
use App\Domains\Content\Channels\Services\PostService;
use App\Domains\Content\Channels\Services\ReactionService;
use App\Domains\FraudML\Services\PaymentFraudMLService;
use App\Domains\FraudML\Services\PaymentFraudMLHelper;
use App\Domains\FraudML\Services\PaymentFraudMLShadowService;
use App\Providers\Prometheus\PaymentFraudMLMetricsCollector;
use App\Domains\Medical\Psychology\Services\AITherapyConstructorService;
use App\Domains\Medical\Psychology\Services\PsychologicalPricingService;
use App\Domains\Medical\Psychology\Services\PsychologicalService;
use App\Services\FraudControlService;
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
        $this->app->singleton(TenantQuotaPlanService::class);
        $this->app->singleton(TenantQuotaPersistenceService::class);
        $this->app->singleton(TenantQuotaNotificationService::class);
        $this->app->singleton(RateLimiterService::class);
        $this->app->singleton(TenantAwareRateLimiter::class);

        // Payment Security Services
        $this->app->singleton(PaymentIdempotencyService::class);
        $this->app->singleton(WebhookSignatureValidator::class);

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

        // Livewire — Channels
        Livewire::component('channels.business-news-feed', \App\Livewire\Channels\BusinessNewsFeed::class);
        Livewire::component('channels.post-card',          \App\Livewire\Channels\PostCard::class);
        Livewire::component('channels.reaction-picker',    \App\Livewire\Channels\ReactionPicker::class);
        Livewire::component('channels.follow-button',      \App\Livewire\Channels\FollowButton::class);
    }
}
