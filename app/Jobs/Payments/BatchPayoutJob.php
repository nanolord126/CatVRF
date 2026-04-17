<?php declare(strict_types=1);

namespace App\Jobs\Payments;


use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;


use Illuminate\Support\Str;
use Illuminate\Log\LogManager;
use Illuminate\Database\DatabaseManager;

final class BatchPayoutJob implements ShouldQueue
{
    use \Illuminate\Foundation\Bus\Dispatchable, \Illuminate\Queue\InteractsWithQueue, \Illuminate\Bus\Queueable, \Illuminate\Queue\SerializesModels;

    private string $correlationId;

    public function __construct(
        private readonly LogManager $logger,
        private readonly DatabaseManager $db,
    )
    {
        $this->correlationId = Str::uuid()->toString();
        $this->onQueue('payouts');
    }

    public function tags(): array
    {
        return ['batch-payout', 'payment', 'mass-withdrawal'];
    }

    public function retryUntil(): \DateTime
    {
        return now()->addHours(12);
    }

    public function handle(): void
    {
        try {
            $this->db->transaction(function () {
                $pendingBatchPayouts = $this->db->table('payment_transactions')
                    ->where('status', 'pending_batch')
                    ->where('tenant_id', filament()->getTenant()->id)
                    ->orderBy('created_at')
                    ->limit(500)
                    ->get();

                foreach ($pendingBatchPayouts as $payout) {
                    try {
                        $this->processSinglePayout($payout);

                        $this->logger->channel('audit')->info('Batch payout processed', [
                            'correlation_id' => $this->correlationId,
                            'payment_transaction_id' => $payout->id,
                            'amount' => $payout->amount,
                            'tenant_id' => $payout->tenant_id,
                        ]);
                    } catch (\Exception $e) {
                        if ($payout->retry_count < 3) {
                            $this->db->table('payment_transactions')
                                ->where('id', $payout->id)
                                ->increment('retry_count');

                            $this->logger->channel('audit')->warning('Batch payout retry scheduled', [
                                'correlation_id' => $this->correlationId,
                                'payment_transaction_id' => $payout->id,
                                'retry_count' => $payout->retry_count + 1,
                                'error' => $e->getMessage(),
                            ]);
                        } else {
                            $this->db->table('payment_transactions')
                                ->where('id', $payout->id)
                                ->update(['status' => 'failed', 'error_message' => $e->getMessage()]);

                            $this->logger->channel('audit')->error('Batch payout failed after retries', [
                                'correlation_id' => $this->correlationId,
                                'payment_transaction_id' => $payout->id,
                                'error' => $e->getMessage(),
                            ]);
                        }
                    }
                }
            });
        } catch (\Exception $e) {
            $this->logger->channel('audit')->error($e->getMessage(), [
                'exception' => $e::class,
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'correlation_id' => $this->correlationId,
            ]);

            $this->logger->channel('audit')->error('Batch payout job failed', [
                'correlation_id' => $this->correlationId,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            throw $e;
        }
    }

    private function processSinglePayout(\stdClass $payout): void
    {
        // $gateway->createPayout(
        //     $payout->merchant_id,
        //     $payout->amount,
        //     $payout->bank_account_id,
        //     $this->correlationId
        // );
    }
}

