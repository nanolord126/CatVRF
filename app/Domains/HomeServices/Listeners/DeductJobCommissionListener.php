<?php

declare(strict_types=1);

namespace App\Domains\HomeServices\Listeners;

use Carbon\CarbonImmutable;

use Carbon\Carbon;
use Psr\Log\LoggerInterface;
use Illuminate\Database\DatabaseManager;

final class DeductJobCommissionListener
{
    public function __construct(
        private readonly DatabaseManager $db,
        private readonly LoggerInterface $logger
    ) {}


    public function handle(ServiceJobCreated $event): void
    {
        try {
            $job = $event->job;

            $this->db->transaction(function () use ($job, $event) {
                $wallet = Wallet::where('tenant_id', $job->tenant_id)->lockForUpdate()->firstOrFail();
                $commissionAmount = (int) ($job->commission_amount * 100);

                $wallet->decrement('balance', $commissionAmount);

                $this->db->table('balance_transactions')->insert([
                    'wallet_id' => $wallet->id,
                    'type' => 'commission',
                    'amount' => -$commissionAmount,
                    'description' => "Service job commission #{$job->id}",
                    'correlation_id' => $event->correlationId,
                    'created_at' => CarbonImmutable::now(),
                ]);
            });

            $this->logger->$this->logger->info('Job commission deducted', [
                'job_id' => $job->id,
                'commission_amount' => $job->commission_amount,
                'correlation_id' => $event->correlationId,
            ]);
        } catch (\Throwable $e) {
            $this->logger->error('Failed to deduct job commission', [
                'error' => $e->getMessage(),
                'correlation_id' => $event->correlationId,
            ]);
            throw $e;
        }
    }

    /**
     * Get the string representation of this instance.
     *
     * @return string The string representation
     */
    public function __toString(): string
    {
        return self::class;
    }

    /**
     * Get debug information for this instance.
     *
     * @return array<string, mixed> Debug data including class name and state
     */
    public function toDebugArray(): array
    {
        return [
            'class' => self::class,
            'timestamp' => CarbonImmutable::now()->toIso8601String(),
        ];
    }
}
