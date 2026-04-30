<?php

declare(strict_types=1);

namespace App\Domains\Bonuses\Listeners;

use App\Domains\Bonuses\Events\BonusSpent;
use App\Services\AuditService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Psr\Log\LoggerInterface;

/**
 * TrackBonusSpendInBigData - Listener to track bonus spending in BigData
 * 
 * Sends bonus spend data to BigData for analytics.
 * Runs asynchronously via queue.
 */
final class TrackBonusSpendInBigData implements ShouldQueue
{
    public string $queue = 'bigdata';

    public function __construct(
        private readonly LoggerInterface $logger,
        private readonly AuditService $audit,
    ) {}

    public function handle(BonusSpent $event): void
    {
        try {
            $this->logger->info('Tracking bonus spend in BigData', [
                'transaction_id' => $event->getTransactionId(),
                'user_id' => $event->getUserId(),
                'amount' => $event->getAmount(),
                'correlation_id' => $event->getCorrelationId(),
            ]);

            // TODO: Implement actual BigData integration
            // BigData::track('bonus_spent', [
            //     'user_id' => $event->getUserId(),
            //     'amount' => $event->getAmount(),
            //     'order_id' => $event->getOrderId(),
            // ]);
        } catch (\Exception $e) {
            $this->logger->error('Failed to track bonus spend in BigData', [
                'transaction_id' => $event->getTransactionId(),
                'error' => $e->getMessage(),
                'correlation_id' => $event->getCorrelationId(),
            ]);

            $this->audit->record(
                'bonus_spend_bigdata_tracking_failed',
                \App\Domains\Bonuses\Models\BonusTransaction::class,
                null,
                [],
                [
                    'transaction_id' => $event->getTransactionId(),
                    'error' => $e->getMessage(),
                ],
                $event->getCorrelationId(),
            );
        }
    }
}
