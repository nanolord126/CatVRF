<?php declare(strict_types=1);

namespace App\Jobs\Payments;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Log\LogManager;
use Illuminate\Database\DatabaseManager;

final class DailyPayoutJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

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
            return ['payout', 'daily', 'payment'];
        }

        public function retryUntil(): \DateTime
        {
            return now()->addHours(8);
        }

        public function handle(PayoutService $payoutService): void
        {
            try {
                $this->db->transaction(function () use ($payoutService) {
                    $cutoffDate = now()->subDays(1)->startOfDay();
                    $pendingPayouts = $payoutService->getPendingPayouts($cutoffDate);

                    foreach ($pendingPayouts as $payout) {
                        $payoutService->processPayout(
                            $payout->id,
                            $this->correlationId
                        );

                        $this->logger->channel('audit')->info('Payout processed', [
                            'correlation_id' => $this->correlationId,
                            'payout_id' => $payout->id,
                            'tenant_id' => $payout->tenant_id,
                            'amount' => $payout->amount,
                        ]);
                    }
                });

                $this->logger->channel('audit')->info('Daily payout batch completed', [
                    'correlation_id' => $this->correlationId,
                    'processed_date' => Carbon::now()->toDateString(),
                ]);
            } catch (\Exception $e) {
                \Illuminate\Support\Facades\Log::channel('audit')->error($e->getMessage(), [
                    'exception' => $e::class,
                    'file' => $e->getFile(),
                    'line' => $e->getLine(),
                    'correlation_id' => request()->header('X-Correlation-ID'),
                ]);

                $this->logger->channel('audit')->error('Daily payout job failed', [
                    'correlation_id' => $this->correlationId,
                    'error' => $e->getMessage(),
                    'trace' => $e->getTraceAsString(),
                ]);

                throw $e;
            }
        }
}
