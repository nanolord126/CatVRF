<?php declare(strict_types=1);

namespace App\Jobs\Payments;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

final class BatchPayoutJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    private string $correlationId;

    public function __construct()
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
            DB::transaction(function () {
                $pendingBatchPayouts = DB::table('payment_transactions')
                    ->where('status', 'pending_batch')
                    ->where('tenant_id', filament()->getTenant()->id)
                    ->orderBy('created_at')
                    ->limit(500)
                    ->get();

                foreach ($pendingBatchPayouts as $payout) {
                    try {
                        $this->processSinglePayout($payout);

                        Log::channel('audit')->info('Batch payout processed', [
                            'correlation_id' => $this->correlationId,
                            'payment_transaction_id' => $payout->id,
                            'amount' => $payout->amount,
                            'tenant_id' => $payout->tenant_id,
                        ]);
                    } catch (\Exception $e) {
                        if ($payout->retry_count < 3) {
                            DB::table('payment_transactions')
                                ->where('id', $payout->id)
                                ->increment('retry_count');

                            Log::channel('audit')->warning('Batch payout retry scheduled', [
                                'correlation_id' => $this->correlationId,
                                'payment_transaction_id' => $payout->id,
                                'retry_count' => $payout->retry_count + 1,
                                'error' => $e->getMessage(),
                            ]);
                        } else {
                            DB::table('payment_transactions')
                                ->where('id', $payout->id)
                                ->update(['status' => 'failed', 'error_message' => $e->getMessage()]);

                            Log::channel('audit')->error('Batch payout failed after retries', [
                                'correlation_id' => $this->correlationId,
                                'payment_transaction_id' => $payout->id,
                                'error' => $e->getMessage(),
                            ]);
                        }
                    }
                }
            });
        } catch (\Exception $e) {
            Log::channel('audit')->error('Batch payout job failed', [
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
