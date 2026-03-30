<?php declare(strict_types=1);

namespace App\Providers;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

final class AppServiceProvider extends Model
{
    use HasFactory;

    // TODO: Проверить и восстановить содержимое класса, если оно было утеряно
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

            // Channels Domain Services (singleton)
            $this->app->singleton(\App\Domains\Content\Channels\Services\ChannelService::class);
            $this->app->singleton(\App\Domains\Content\Channels\Services\PostService::class);
            $this->app->singleton(\App\Domains\Content\Channels\Services\ReactionService::class);
            $this->app->singleton(\App\Domains\Content\Channels\Services\ChannelTariffService::class);
            $this->app->singleton(\App\Domains\Content\Channels\Services\ChannelSubscriptionService::class);

            // Psychology Domain Services
            $this->app->singleton(\App\Domains\Medical\Psychology\Services\PsychologicalService::class);
            $this->app->singleton(\App\Domains\Medical\Psychology\Services\AITherapyConstructorService::class);
            $this->app->singleton(\App\Domains\Medical\Psychology\Services\PsychologicalPricingService::class);

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
            // Livewire — Channels
            Livewire::component('channels.business-news-feed', \App\Livewire\Channels\BusinessNewsFeed::class);
            Livewire::component('channels.post-card',          \App\Livewire\Channels\PostCard::class);
            Livewire::component('channels.reaction-picker',    \App\Livewire\Channels\ReactionPicker::class);
            Livewire::component('channels.follow-button',      \App\Livewire\Channels\FollowButton::class);
        }
}
