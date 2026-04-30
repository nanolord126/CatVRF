<?php

declare(strict_types=1);

namespace App\Domains\Bonuses\Listeners;

use App\Domains\Bonuses\Events\BonusUnlocked;
use App\Services\AuditService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Psr\Log\LoggerInterface;

/**
 * SendBonusUnlockedNotification - Listener to send unlock notifications
 * 
 * Sends notifications when bonus hold period expires and becomes available.
 * Runs asynchronously via queue.
 */
final class SendBonusUnlockedNotification implements ShouldQueue
{
    public string $queue = 'notifications';

    public function __construct(
        private readonly LoggerInterface $logger,
        private readonly AuditService $audit,
    ) {}

    public function handle(BonusUnlocked $event): void
    {
        try {
            $userId = $event->getUserId();
            $amount = $event->getAmount();

            $this->logger->info('Sending bonus unlocked notification', [
                'user_id' => $userId,
                'amount' => $amount,
                'correlation_id' => $event->getCorrelationId(),
            ]);

            // TODO: Implement actual notification sending
            // Notification::send($userId, new BonusUnlockedNotification($amount));
        } catch (\Exception $e) {
            $this->logger->error('Failed to send bonus unlocked notification', [
                'user_id' => $userId,
                'error' => $e->getMessage(),
                'correlation_id' => $event->getCorrelationId(),
            ]);

            $this->audit->record(
                'bonus_unlocked_notification_failed',
                \App\Domains\Bonuses\Models\BonusTransaction::class,
                null,
                [],
                [
                    'user_id' => $userId,
                    'error' => $e->getMessage(),
                ],
                $event->getCorrelationId(),
            );
        }
    }
}
