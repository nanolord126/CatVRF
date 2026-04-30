<?php

declare(strict_types=1);

namespace App\Domains\Bonuses\Listeners;

use App\Domains\Bonuses\Events\BonusAwarded;
use App\Domains\Bonuses\Events\BonusSpent;
use App\Services\AuditService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Psr\Log\LoggerInterface;

/**
 * UpdateRFMScore - Listener to update RFM score on bonus operations
 * 
 * Updates Recency, Frequency, Monetary scores when bonuses are awarded or spent.
 * Runs asynchronously via queue.
 */
final class UpdateRFMScore implements ShouldQueue
{
    public string $queue = 'analytics';

    public function __construct(
        private readonly LoggerInterface $logger,
        private readonly AuditService $audit,
    ) {}

    public function handle(BonusAwarded|BonusSpent $event): void
    {
        try {
            $userId = $event->getUserId();
            $amount = $event->getAmount();

            // Update RFM score via Analytics service
            $this->logger->info('Updating RFM score for bonus operation', [
                'user_id' => $userId,
                'amount' => $amount,
                'operation' => $event instanceof BonusAwarded ? 'award' : 'spend',
                'correlation_id' => $event->getCorrelationId(),
            ]);

            // TODO: Implement actual RFM update
            // RFMService::updateForBonusOperation($userId, $amount, $eventType);
        } catch (\Exception $e) {
            $this->logger->error('Failed to update RFM score', [
                'user_id' => $event->getUserId(),
                'error' => $e->getMessage(),
                'correlation_id' => $event->getCorrelationId(),
            ]);

            $this->audit->record(
                'bonus_rfm_update_failed',
                \App\Domains\Bonuses\Models\BonusTransaction::class,
                null,
                [],
                [
                    'user_id' => $event->getUserId(),
                    'error' => $e->getMessage(),
                ],
                $event->getCorrelationId(),
            );
        }
    }
}
