<?php

declare(strict_types=1);

namespace App\Domains\Bonuses\Listeners;

use App\Domains\Bonuses\Events\BonusWithdrawn;
use App\Services\AuditService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Psr\Log\LoggerInterface;

/**
 * SendWithdrawalNotification - Listener to send withdrawal notifications
 * 
 * Sends notifications to B2B users when withdrawals are processed.
 * Runs asynchronously via queue.
 */
final class SendWithdrawalNotification implements ShouldQueue
{
    public string $queue = 'notifications';

    public function __construct(
        private readonly LoggerInterface $logger,
        private readonly AuditService $audit,
    ) {}

    public function handle(BonusWithdrawn $event): void
    {
        try {
            $userId = $event->getUserId();
            $amount = $event->getAmount();
            $netAmount = $event->getNetAmount();

            $this->logger->info('Sending withdrawal notification', [
                'user_id' => $userId,
                'amount' => $amount,
                'net_amount' => $netAmount,
                'correlation_id' => $event->getCorrelationId(),
            ]);

            // TODO: Implement actual notification sending
            // Notification::send($userId, new WithdrawalProcessedNotification($amount, $netAmount));
        } catch (\Exception $e) {
            $this->logger->error('Failed to send withdrawal notification', [
                'user_id' => $userId,
                'error' => $e->getMessage(),
                'correlation_id' => $event->getCorrelationId(),
            ]);

            $this->audit->record(
                'withdrawal_notification_failed',
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
