<?php declare(strict_types=1);

namespace App\Domains\Education\Channels\Services;

use App\Domains\Education\Channels\Models\BusinessChannel;
use App\Domains\Education\Channels\Models\ChannelSubscriptionPlan;
use App\Domains\Education\Channels\Models\ChannelSubscriptionUsage;
use App\Services\FraudControlService;
use App\Services\WalletService;
use Carbon\Carbon;
use Illuminate\Config\Repository as ConfigRepository;
use Illuminate\Contracts\Auth\Guard;
use Illuminate\Database\DatabaseManager;
use Illuminate\Support\Str;
use Psr\Log\LoggerInterface;

final readonly class ChannelTariffService
{
    private const RATE_LIMIT_MAX = 5;
    private const RATE_LIMIT_DECAY = 60;

    public function __construct(
        private WalletService $walletService,
        private FraudControlService $fraud,
        private DatabaseManager $db,
        private ConfigRepository $config,
        private LoggerInterface $logger,
        private Guard $guard,
    ) {}

    /**
     * Подписать бизнес на тарифный план (списать с кошелька).
     */
    public function subscribe(
        BusinessChannel $channel,
        string $planSlug,
        string $correlationId = '',
    ): ChannelSubscriptionUsage {
        $correlationId = $correlationId ?: Str::uuid()->toString();

        $plan = ChannelSubscriptionPlan::where('slug', $planSlug)
            ->where('is_active', true)
            ->firstOrFail();

        $fraudResult = $this->fraud->check(
            userId: (int) $this->guard->id(),
            operationType: 'channel_subscription',
            amount: $plan->price_kopecks,
            correlationId: $correlationId,
        );

        return $this->db->transaction(function () use ($channel, $plan, $correlationId): ChannelSubscriptionUsage {
            ChannelSubscriptionUsage::where('channel_id', $channel->id)
                ->where('status', 'active')
                ->update([
                    'status' => 'cancelled',
                    'cancelled_at' => Carbon::now(),
                ]);

            $this->walletService->debit(
                tenantId: $channel->tenant_id,
                amount: $plan->price_kopecks,
                type: 'channel_subscription',
                sourceId: $channel->id,
                correlationId: $correlationId,
                reason: "Подписка на тариф «{$plan->name}» (канал #{$channel->id})",
                sourceType: 'channel',
            );

            $usage = ChannelSubscriptionUsage::create([
                'tenant_id' => $channel->tenant_id,
                'channel_id' => $channel->id,
                'plan_id' => $plan->id,
                'status' => 'active',
                'starts_at' => Carbon::now(),
                'expires_at' => Carbon::now()->addDays($plan->duration_days ?? 30),
                'correlation_id' => $correlationId,
            ]);

            $this->logger->info('Channel subscription created', [
                'correlation_id' => $correlationId,
                'tenant_id' => $channel->tenant_id,
                'channel_id' => $channel->id,
                'plan' => $plan->slug,
                'expires_at' => $usage->expires_at->toIso8601String(),
                'amount' => $plan->price_kopecks,
            ]);

            return $usage;
        });
    }

    /**
     * Получить активный план канала.
     */
    public function getActivePlan(BusinessChannel $channel): ?ChannelSubscriptionPlan
    {
        return cache()->remember(
            "channel_plan:{$channel->id}",
            $this->config->get('channels.cache.stats_ttl', 600),
            fn () => $channel->activeSubscription()->with('plan')->first()?->plan,
        );
    }

    /**
     * Проверить, не превышен ли лимит постов на сегодня.
     */
    public function assertPostsLimitNotExceeded(BusinessChannel $channel): void
    {
        $plan = $this->getActivePlan($channel);

        if ($plan === null) {
            throw new \RuntimeException(
                'У канала нет активной подписки. Оформите тарифный план для публикации постов.',
            );
        }

        $todayCount = $channel->posts()
            ->where('status', '!=', 'draft')
            ->whereDate('created_at', today())
            ->count();

        if ($todayCount >= $plan->posts_per_day) {
            throw new \RuntimeException(
                "Лимит публикаций на сегодня исчерпан ({$plan->posts_per_day} постов/день по тарифу «{$plan->name}»).",
            );
        }
    }

    /**
     * Проверить, не превышен ли лимит медиафайлов поста.
     */
    public function assertMediaLimitNotExceeded(BusinessChannel $channel, int $mediaCount): void
    {
        $plan = $this->getActivePlan($channel);
        $max = $plan?->photos_per_post ?? 5;
        $planName = $plan?->name ?? 'базовый';

        if ($mediaCount > $max) {
            throw new \RuntimeException(
                "Превышен лимит медиафайлов ({$max} на пост по тарифу «{$planName}»).",
            );
        }
    }

    /**
     * Проверить доступность функции для плана канала.
     */
    public function assertFeatureEnabled(BusinessChannel $channel, string $feature): void
    {
        $plan = $this->getActivePlan($channel);

        if ($plan === null || !$plan->$feature) {
            $featureNames = [
                'polls_enabled' => 'Опросы',
                'promo_enabled' => 'Промо-материалы',
                'advanced_stats' => 'Расширенная статистика',
                'scheduled_posts' => 'Отложенный постинг',
                'shorts_enabled' => 'Shorts/видео',
            ];

            $featureName = $featureNames[$feature] ?? $feature;

            throw new \RuntimeException(
                "«{$featureName}» доступны только в тарифе «Расширенный» (199₽/мес).",
            );
        }
    }

    /**
     * Продлить подписку (авто-платёж).
     */
    public function renew(ChannelSubscriptionUsage $usage, string $correlationId = ''): ChannelSubscriptionUsage
    {
        return $this->subscribe($usage->channel, $usage->plan->slug, $correlationId);
    }

    /**
     * Отменить подписку.
     */
    public function cancel(BusinessChannel $channel, string $correlationId = ''): void
    {
        $correlationId = $correlationId ?: Str::uuid()->toString();

        $this->db->transaction(function () use ($channel, $correlationId): void {
            ChannelSubscriptionUsage::where('channel_id', $channel->id)
                ->where('status', 'active')
                ->update([
                    'status' => 'cancelled',
                    'cancelled_at' => Carbon::now(),
                ]);

            $this->logger->info('Channel subscription cancelled', [
                'correlation_id' => $correlationId,
                'tenant_id' => $channel->tenant_id,
                'channel_id' => $channel->id,
            ]);
        });
    }

    /**
     * Получить список тарифов из БД (с кэшированием).
     */
    public function getPlans(): \Illuminate\Database\Eloquent\Collection
    {
        return cache()->remember(
            'channel_subscription_plans',
            3600,
            fn () => ChannelSubscriptionPlan::where('is_active', true)->orderBy('price_kopecks')->get(),
        );
    }
}
