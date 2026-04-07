<?php declare(strict_types=1);

namespace App\Domains\Education\Channels\Jobs;

use Carbon\Carbon;


use Psr\Log\LoggerInterface;
use Illuminate\Config\Repository as ConfigRepository;

final class SubscriptionRenewalJob
{
    public function __construct(
        private readonly LoggerInterface $logger) {}


    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

        public int $tries = 3;

        public function handle(ChannelTariffService $tariffService): void
        {
            $correlationId = Str::uuid()->toString();
            $warnDays      = $this->config->get('channels.notifications.plan_expiry_warn_days', 3);

            $this->logger->info('SubscriptionRenewalJob started', [
                'correlation_id' => $correlationId,
            ]);

            // 1. Предупреждения (за N дней до истечения)
            $expiringSoon = ChannelSubscriptionUsage::where('status', 'active')
                ->whereBetween('expires_at', [Carbon::now(), Carbon::now()->addDays($warnDays)])
                ->with(['channel', 'plan'])
                ->get();

            foreach ($expiringSoon as $usage) {
                $this->logger->info('Channel plan expiring soon', [
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
                ->where('expires_at', '<=', Carbon::now())
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
                    $this->logger->warning('Channel subscription renewal failed', [
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

            $this->logger->info('SubscriptionRenewalJob completed', [
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
