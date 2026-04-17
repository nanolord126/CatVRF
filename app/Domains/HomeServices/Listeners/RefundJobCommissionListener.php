<?php declare(strict_types=1);

namespace App\Domains\HomeServices\Listeners;
use Illuminate\Bus\Queueable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Foundation\Bus\Dispatchable;

use Carbon\Carbon;


use Psr\Log\LoggerInterface;
final class RefundJobCommissionListener
{
    public function __construct(
        private readonly \Illuminate\Database\DatabaseManager $db, private readonly LoggerInterface $logger) {}



        public function handle(ServiceJobCompleted $event): void
        {
            try {
                $job = $event->job;

                if ($job->status !== 'cancelled') {
                    return;
                }

                $this->db->transaction(function () use ($job, $event) {
                    $wallet = \App\Models\Wallet::where('tenant_id', $job->tenant_id)->lockForUpdate()->firstOrFail();
                    $commissionAmount = (int)($job->commission_amount * 100);

                    $wallet->increment('balance', $commissionAmount);

                    $this->db->table('balance_transactions')->insert([
                        'wallet_id' => $wallet->id,
                        'type' => 'refund',
                        'amount' => $commissionAmount,
                        'description' => "Service job commission refund #{$job->id}",
                        'correlation_id' => $event->correlationId,
                        'created_at' => Carbon::now(),
                    ]);
                });

                $this->logger->info('Job commission refunded', [
                    'job_id' => $job->id,
                    'commission_amount' => $job->commission_amount,
                    'correlation_id' => $event->correlationId,
                ]);
            } catch (\Throwable $e) {
                $this->logger->error('Failed to refund job commission', [
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
        return static::class;
    }

    /**
     * Get debug information for this instance.
     *
     * @return array<string, mixed> Debug data including class name and state
     */
    public function toDebugArray(): array
    {
        return [
            'class' => static::class,
            'timestamp' => Carbon::now()->toIso8601String(),
        ];
    }
}

