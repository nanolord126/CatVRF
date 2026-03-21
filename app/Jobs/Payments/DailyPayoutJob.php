<?php declare(strict_types=1);

namespace App\Jobs\Payments;

use App\Domains\Payments\Services\PayoutService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Carbon\Carbon;

final class DailyPayoutJob implements ShouldQueue
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
        return ['payout', 'daily', 'payment'];
    }

    public function retryUntil(): \DateTime
    {
        return now()->addHours(8);
    }

    public function handle(PayoutService $payoutService): void
    {
        try {
            DB::transaction(function () use ($payoutService) {
                $cutoffDate = now()->subDays(1)->startOfDay();
                $pendingPayouts = $payoutService->getPendingPayouts($cutoffDate);

                foreach ($pendingPayouts as $payout) {
                    $payoutService->processPayout(
                        $payout->id,
                        $this->correlationId
                    );

                    Log::channel('audit')->info('Payout processed', [
                        'correlation_id' => $this->correlationId,
                        'payout_id' => $payout->id,
                        'tenant_id' => $payout->tenant_id,
                        'amount' => $payout->amount,
                    ]);
                }
            });

            Log::channel('audit')->info('Daily payout batch completed', [
                'correlation_id' => $this->correlationId,
                'processed_date' => Carbon::now()->toDateString(),
            ]);
        } catch (\Exception $e) {
            Log::channel('audit')->error('Daily payout job failed', [
                'correlation_id' => $this->correlationId,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            throw $e;
        }
    }
}
