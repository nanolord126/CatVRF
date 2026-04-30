<?php

declare(strict_types=1);

namespace App\Jobs\Payments;

use Psr\Log\LoggerInterface;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Log\LogManager;
use Illuminate\Database\DatabaseManager;
use Carbon\CarbonImmutable;

final class DailyPayoutJob implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    private readonly string $correlationId;

    public function __construct(private readonly LoggerInterface $logger,
        private readonly LogManager $logger,
        private readonly DatabaseManager $db,) {
        $this->correlationId = Str::uuid()->toString();
        $this->onQueue('payment');
    }

    public function tags(): array
    {
        return ['payout', 'daily', 'payment'];
    }

    public function retryUntil(): \DateTime
    {
        return CarbonImmutable::now()->addHours(8);
    }

    public function handle(PayoutService $payoutService): void
    {
        try {
            $this->db->transaction(function () use ($payoutService) {
                $cutoffDate = CarbonImmutable::now()->subDays(1)->startOfDay();
                $pendingPayouts = $payoutService->getPendingPayouts($cutoffDate);

                foreach ($pendingPayouts as $payout) {
                    $payoutService->processPayout(
                        $payout->id,
                        $this->correlationId
                    );

                    $this->logger->channel('audit')->$this->logger->info('Payout processed', [
                        'correlation_id' => $this->correlationId,
                        'payout_id' => $payout->id,
                        'tenant_id' => $payout->tenant_id,
                        'amount' => $payout->amount,
                    ]);
                }
            });

            $this->logger->channel('audit')->$this->logger->info('Daily payout batch completed', [
                'correlation_id' => $this->correlationId,
                'processed_date' => new DateTime()->toDateString(),
            ]);
        } catch (\Exception $e) {
            $this->logger->channel('audit')->error($e->getMessage(), [
                'exception' => $e::class,
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'correlation_id' => $this->correlationId,
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
