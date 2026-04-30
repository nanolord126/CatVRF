<?php

declare(strict_types=1);

namespace App\Domains\Bonuses\Listeners;

use App\Domains\Bonuses\Events\BonusAwarded;
use App\Services\AuditService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Psr\Log\LoggerInterface;

/**
 * TrackBonusInBigData - Listener to track bonus awards in BigData
 * 
 * Sends bonus award data to BigData for analytics and CLV calculation.
 * Runs asynchronously via queue.
 */
final class TrackBonusInBigData implements ShouldQueue
{
    public string $queue = 'bigdata';

    public function __construct(
        private readonly LoggerInterface $logger,
        private readonly AuditService $audit,
    ) {}

    public function handle(BonusAwarded $event): void
    {
        try {
            // Send to BigData for tracking
            // This would typically call BigData service or send to Kafka
            $this->logger->info('Tracking bonus award in BigData', [
                'transaction_id' => $event->getTransactionId(),
                'user_id' => $event->getUserId(),
                'amount' => $event->getAmount(),
                'correlation_id' => $event->getCorrelationId(),
            ]);

            // TODO: Implement actual BigData integration
            // BigData::track('bonus_awarded', [
            //     'user_id' => $event->getUserId(),
            //     'amount' => $event->getAmount(),
            //     'transaction_id' => $event->getTransactionId(),
            // ]);
        } catch (\Exception $e) {
            $this->logger->error('Failed to track bonus in BigData', [
                'transaction_id' => $event->getTransactionId(),
                'error' => $e->getMessage(),
                'correlation_id' => $event->getCorrelationId(),
            ]);

            // Audit the failure
            $this->audit->record(
                'bonus_bigdata_tracking_failed',
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
