<?php

declare(strict_types=1);

namespace App\Domains\Bonuses\Listeners;

use App\Domains\Bonuses\Events\BonusWithdrawn;
use App\Services\AuditService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Psr\Log\LoggerInterface;

/**
 * TrackWithdrawalInBigData - Listener to track bonus withdrawals in BigData
 * 
 * Sends withdrawal data to BigData for compliance and analytics.
 * Runs asynchronously via queue.
 */
final class TrackWithdrawalInBigData implements ShouldQueue
{
    public string $queue = 'bigdata';

    public function __construct(
        private readonly LoggerInterface $logger,
        private readonly AuditService $audit,
    ) {}

    public function handle(BonusWithdrawn $event): void
    {
        try {
            $this->logger->info('Tracking bonus withdrawal in BigData', [
                'transaction_id' => $event->getTransactionId(),
                'user_id' => $event->getUserId(),
                'amount' => $event->getAmount(),
                'net_amount' => $event->getNetAmount(),
                'correlation_id' => $event->getCorrelationId(),
            ]);

            // TODO: Implement actual BigData integration for compliance tracking
            // BigData::track('bonus_withdrawn', [
            //     'user_id' => $event->getUserId(),
            //     'amount' => $event->getAmount(),
            //     'net_amount' => $event->getNetAmount(),
            // ]);
        } catch (\Exception $e) {
            $this->logger->error('Failed to track withdrawal in BigData', [
                'transaction_id' => $event->getTransactionId(),
                'error' => $e->getMessage(),
                'correlation_id' => $event->getCorrelationId(),
            ]);

            $this->audit->record(
                'bonus_withdrawal_bigdata_tracking_failed',
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
