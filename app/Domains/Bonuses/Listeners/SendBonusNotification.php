<?php

declare(strict_types=1);

namespace App\Domains\Bonuses\Listeners;

use App\Domains\Bonuses\Events\BonusAwarded;
use App\Services\AuditService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Psr\Log\LoggerInterface;

/**
 * SendBonusNotification - Listener to send bonus award notifications
 * 
 * Sends push/email notifications to users when they receive bonuses.
 * Runs asynchronously via queue.
 */
final class SendBonusNotification implements ShouldQueue
{
    public string $queue = 'notifications';

    public function __construct(
        private readonly LoggerInterface $logger,
        private readonly AuditService $audit,
    ) {}

    public function handle(BonusAwarded $event): void
    {
        try {
            $userId = $event->getUserId();
            $amount = $event->getAmount();

            // Send notification to user
            $this->logger->info('Sending bonus notification', [
                'user_id' => $userId,
                'amount' => $amount,
                'correlation_id' => $event->getCorrelationId(),
            ]);

            // TODO: Implement actual notification sending
            // Notification::send($userId, new BonusAwardedNotification($amount));
        } catch (\Exception $e) {
            $this->logger->error('Failed to send bonus notification', [
                'user_id' => $userId,
                'error' => $e->getMessage(),
                'correlation_id' => $event->getCorrelationId(),
            ]);

            $this->audit->record(
                'bonus_notification_failed',
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
