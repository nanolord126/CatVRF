<?php declare(strict_types=1);

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Psr\Log\LoggerInterface;
use Modules\Taxi\Models\TaxiDriver;
use App\Services\Wallet\WalletService;
use App\Services\AuditService;

/**
 * Job for processing driver payout after ride completion.
 * Runs asynchronously on the payouts queue.
 */
final class ProcessDriverPayoutJob implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public int $tries;
    public int $timeout;

    public function __construct(
        public int $driverId,
        public int $finalPriceKopeki,
        public string $correlationId,
        private readonly LoggerInterface $logger,
    ) {
        $this->tries = 3;
        $this->timeout = 120;
    }

    public function handle(WalletService $wallet, AuditService $audit): void
    {
        $driver = TaxiDriver::where('id', $this->driverId)->firstOrFail();

        $commissionRate = 0.14;
        $driverEarningsKopeki = (int) floor($this->finalPriceKopeki * (1 - $commissionRate));

        $this->logger->channel('audit')->info('Processing driver payout', [
            'driver_id' => $this->driverId,
            'final_price_rubles' => $this->finalPriceKopeki / 100,
            'driver_earnings_rubles' => $driverEarningsKopeki / 100,
            'commission_rate' => $commissionRate,
            'correlation_id' => $this->correlationId,
        ]);

        $wallet->credit(
            tenantId: $driver->tenant_id,
            amount: $driverEarningsKopeki,
            type: 'payout',
            sourceId: $this->driverId,
            sourceType: 'taxi_driver_earnings',
            reason: 'Taxi ride earnings',
            correlationId: $this->correlationId,
        );

        $driver->addEarnings($driverEarningsKopeki);

        $audit->record(
            action: 'taxi_driver_payout_processed',
            subjectType: TaxiDriver::class,
            subjectId: $this->driverId,
            newValues: [
                'earnings_kopeki' => $driverEarningsKopeki,
                'commission_rate' => $commissionRate,
            ],
            correlationId: $this->correlationId,
        );
    }

    public function failed(\Throwable $exception): void
    {
        $this->logger->channel('audit')->error('Driver payout processing failed', [
            'driver_id' => $this->driverId,
            'error' => $exception->getMessage(),
            'correlation_id' => $this->correlationId,
        ]);
    }
}
