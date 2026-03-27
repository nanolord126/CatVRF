<?php declare(strict_types=1);

namespace App\Domains\Content\Channels\Jobs;

use App\Domains\Content\Channels\Models\ChannelSubscriptionUsage;
use App\Domains\Content\Channels\Services\ChannelTariffService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Str;

/**
 * Авто-продление подписок бизнес-каналов.
 *
 * Запускается ежедневно в 09:00.
 * За 3 дня до истечения — предупреждение бизнесу.
 * При истечении — попытка авто-продления через WalletService.
 */
final class SubscriptionRenewalJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;

    public function handle(ChannelTariffService $tariffService): void
    {
        $correlationId = Str::uuid()->toString();
        $warnDays      = config('channels.notifications.plan_expiry_warn_days', 3);

        Log::channel('audit')->info('SubscriptionRenewalJob started', [
            'correlation_id' => $correlationId,
        ]);

        // 1. Предупреждения (за N дней до истечения)
        $expiringSoon = ChannelSubscriptionUsage::where('status', 'active')
            ->whereBetween('expires_at', [now(), now()->addDays($warnDays)])
            ->with(['channel', 'plan'])
            ->get();

        foreach ($expiringSoon as $usage) {
            Log::channel('audit')->info('Channel plan expiring soon', [
                'correlation_id' => $correlationId,
                'channel_id'     => $usage->channel_id,
                'tenant_id'      => $usage->tenant_id,
                'expires_at'     => $usage->expires_at->toIso8601String(),
                'plan'           => $usage->plan?->slug,
            ]);

            // Отправка уведомления владельцу канала
            if ($usage->channel && $usage->channel->owner) {
                $usage->channel->owner->notify(
                    new \App\Notifications\ChannelPlanExpiringSoonNotification(
                        $usage,
                        $warnDays,
                        $correlationId
                    )
                );
            }
        }

        // 2. Авто-продление истёкших (попытка списания с кошелька)
        $expired = ChannelSubscriptionUsage::where('status', 'active')
            ->where('expires_at', '<=', now())
            ->with(['channel', 'plan'])
            ->get();

        $renewed = 0;
        $failed  = 0;

        foreach ($expired as $usage) {
            try {
                $tariffService->renew($usage, $correlationId);
                $renewed++;
            } catch (\Throwable $e) {
                $failed++;

                // Если оплата не прошла — деградация до архива
                Log::channel('audit')->warning('Channel subscription renewal failed', [
                    'correlation_id' => $correlationId,
                    'channel_id'     => $usage->channel_id,
                    'tenant_id'      => $usage->tenant_id,
                    'plan'           => $usage->plan?->slug,
                    'error'          => $e->getMessage(),
                ]);

                // Перевести в expired
                $usage->update(['status' => 'expired']);

                // Обнулить план в канале
                $usage->channel?->update([
                    'plan_id'         => null,
                    'plan_expires_at' => null,
                ]);
            }
        }

        Log::channel('audit')->info('SubscriptionRenewalJob completed', [
            'correlation_id' => $correlationId,
            'renewed'        => $renewed,
            'failed'         => $failed,
            'warned'         => $expiringSoon->count(),
        ]);
    }

    public function tags(): array
    {
        return ['channel', 'subscription', 'renewal'];
    }
}
