<?php declare(strict_types=1);

namespace App\Domains\Content\Channels\Services;

use App\Domains\Content\Channels\Models\BusinessChannel;
use App\Domains\Content\Channels\Models\ChannelSubscriptionPlan;
use App\Domains\Content\Channels\Models\ChannelSubscriptionUsage;
use App\Services\Wallet\WalletService;
use App\Services\FraudControlService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Carbon\Carbon;

/**
 * Тарифная система бизнес-каналов.
 *
 * Планы:
 *   basic    → 49₽/мес  (2 поста/день, 5 фото/пост, базовая статистика)
 *   extended → 199₽/мес (5 постов/день, опросы, промо, расширенная статистика)
 *
 * Оплата через WalletService::debit().
 * Всегда DB::transaction() + FraudControlService::check().
 */
final class ChannelTariffService
{
    public function __construct(
        private readonly WalletService $walletService,
        private readonly FraudControlService $fraudControlService,
    ) {}

    /**
     * Подписать бизнес на тарифный план (списать с кошелька).
     *
     * @throws \App\Exceptions\InsufficientFundsException
     * @throws \App\Exceptions\FraudBlockedException
     * @throws \RuntimeException
     */
    public function subscribe(
        BusinessChannel $channel,
        string $planSlug,
        string $correlationId = '',
    ): ChannelSubscriptionUsage {
        $correlationId = $correlationId ?: Str::uuid()->toString();

        // Rate limit: не более 5 попыток оплаты в минуту
        $rateLimitKey = "channel_subscribe:{$channel->tenant_id}";
        if (RateLimiter::tooManyAttempts($rateLimitKey, 5)) {
            throw new \RuntimeException('Слишком много попыток оформления подписки. Попробуйте позже.');
        }
        RateLimiter::hit($rateLimitKey, 60);

        $plan = ChannelSubscriptionPlan::where('slug', $planSlug)
            ->where('is_active', true)
            ->firstOrFail();

        // Fraud check
        $fraudResult = $this->fraudControlService->check(
            userId: (int) auth()->id(),
            operationType: 'channel_subscription',
            amount: $plan->price_kopecks,
            correlationId: $correlationId,
        );

        if ($fraudResult['decision'] === 'block') {
            Log::channel('audit')->warning('Channel subscription blocked by fraud', [
                'correlation_id' => $correlationId,
                'tenant_id'      => $channel->tenant_id,
                'plan'           => $planSlug,
                'fraud_score'    => $fraudResult['score'],
            ]);

            throw new \RuntimeException('Операция заблокирована системой безопасности.');
        }

        return DB::transaction(function () use ($channel, $plan, $correlationId): ChannelSubscriptionUsage {

            // Отменить существующую активную подписку (если есть)
            ChannelSubscriptionUsage::where('channel_id', $channel->id)
                ->where('status', 'active')
                ->update([
                    'status'       => 'cancelled',
                    'cancelled_at' => now(),
                ]);

            // Списать с кошелька
            $transaction = $this->walletService->debit(
                tenantId:      $channel->tenant_id,
                amount:        $plan->price_kopecks,
                type:          'channel_subscription',
                sourceId:      $channel->id,
                correlationId: $correlationId,
                reason:        "Подписка на тариф «{$plan->name}» (канал #{$channel->id})",
                sourceType:    'channel',
            );

            // Создать запись об использовании
            $usage = ChannelSubscriptionUsage::create([
                'tenant_id'              => $channel->tenant_id,
                'channel_id'             => $channel->id,
                'plan_id'                => $plan->id,
                'status'                 => 'active',
                'starts_at'              => now(),
                'expires_at'             => now()->addMonth(),
                'amount_paid_kopecks'    => $plan->price_kopecks,
                'balance_transaction_id' => $transaction?->id,
                'correlation_id'         => $correlationId,
                'tags'                   => ['source' => 'channel_subscription'],
            ]);

            // Обновить канал
            $channel->update([
                'plan_id'         => $plan->id,
                'plan_expires_at' => now()->addMonth(),
                'status'          => 'active',
            ]);

            // Инвалидировать кэш
            Cache::forget("channel_plan:{$channel->id}");

            Log::channel('audit')->info('Channel subscription created', [
                'correlation_id' => $correlationId,
                'tenant_id'      => $channel->tenant_id,
                'channel_id'     => $channel->id,
                'plan'           => $plan->slug,
                'expires_at'     => $usage->expires_at->toIso8601String(),
                'amount'         => $plan->price_kopecks,
            ]);

            return $usage;
        });
    }

    /**
     * Получить активный план канала.
     */
    public function getActivePlan(BusinessChannel $channel): ?ChannelSubscriptionPlan
    {
        return Cache::remember(
            "channel_plan:{$channel->id}",
            config('channels.cache.stats_ttl', 600),
            fn () => $channel->activeSubscription()->with('plan')->first()?->plan
        );
    }

    /**
     * Проверить, не превышен ли лимит постов на сегодня.
     *
     * @throws \RuntimeException Если лимит превышен
     */
    public function assertPostsLimitNotExceeded(BusinessChannel $channel): void
    {
        $plan = $this->getActivePlan($channel);

        if ($plan === null) {
            // Без тарифа — нет публикации
            throw new \RuntimeException(
                'У канала нет активной подписки. Оформите тарифный план для публикации постов.'
            );
        }

        $todayCount = $channel->posts()
            ->where('status', '!=', 'draft')
            ->whereDate('created_at', today())
            ->count();

        if ($todayCount >= $plan->posts_per_day) {
            throw new \RuntimeException(
                "Лимит публикаций на сегодня исчерпан ({$plan->posts_per_day} постов/день по тарифу «{$plan->name}»)."
            );
        }
    }

    /**
     * Проверить, не превышен ли лимит медиафайлов поста.
     *
     * @throws \RuntimeException Если лимит превышен
     */
    public function assertMediaLimitNotExceeded(BusinessChannel $channel, int $mediaCount): void
    {
        $plan     = $this->getActivePlan($channel);
        $max      = $plan?->photos_per_post ?? 5;
        $planName = $plan?->name ?? 'базовый';

        if ($mediaCount > $max) {
            throw new \RuntimeException(
                "Превышен лимит медиафайлов ({$max} на пост по тарифу «{$planName}»)."
            );
        }
    }

    /**
     * Проверить доступность функции для плана канала.
     *
     * @throws \RuntimeException
     */
    public function assertFeatureEnabled(BusinessChannel $channel, string $feature): void
    {
        $plan = $this->getActivePlan($channel);

        if ($plan === null || !$plan->$feature) {
            $featureNames = [
                'polls_enabled'   => 'Опросы',
                'promo_enabled'   => 'Промо-материалы',
                'advanced_stats'  => 'Расширенная статистика',
                'scheduled_posts' => 'Отложенный постинг',
                'shorts_enabled'  => 'Shorts/видео',
            ];

            $featureName = $featureNames[$feature] ?? $feature;

            throw new \RuntimeException(
                "«{$featureName}» доступны только в тарифе «Расширенный» (199₽/мес)."
            );
        }
    }

    /**
     * Продлить подписку (авто-платёж через SubscriptionRenewalJob).
     */
    public function renew(ChannelSubscriptionUsage $usage, string $correlationId = ''): ChannelSubscriptionUsage
    {
        return $this->subscribe($usage->channel, $usage->plan->slug, $correlationId);
    }

    /**
     * Отменить подписку (без возврата средств по договору оферты).
     */
    public function cancel(BusinessChannel $channel, string $correlationId = ''): void
    {
        $correlationId = $correlationId ?: Str::uuid()->toString();

        DB::transaction(function () use ($channel, $correlationId): void {
            ChannelSubscriptionUsage::where('channel_id', $channel->id)
                ->where('status', 'active')
                ->update([
                    'status'         => 'cancelled',
                    'cancelled_at'   => now(),
                ]);

            Log::channel('audit')->info('Channel subscription cancelled', [
                'correlation_id' => $correlationId,
                'tenant_id'      => $channel->tenant_id,
                'channel_id'     => $channel->id,
            ]);
        });
    }

    /**
     * Получить список тарифов из БД (с кэшированием).
     */
    public function getPlans(): \Illuminate\Database\Eloquent\Collection
    {
        return Cache::remember(
            'channel_subscription_plans',
            3600,
            fn () => ChannelSubscriptionPlan::where('is_active', true)->orderBy('price_kopecks')->get()
        );
    }
}
