<?php

declare(strict_types=1);

namespace App\Domains\Logistics\Jobs;

use Carbon\CarbonImmutable;

use Psr\Log\LoggerInterface;
use Illuminate\Http\Request;
use App\Domains\Logistics\Models\Courier;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Str;

final class CalculateCourierEarningsJob implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public function __construct(
        private readonly string $correlationId,
        private readonly Request $request,
        private readonly LoggerInterface $logger
    ) {
        $this->onQueue('default');
    }

    public function tags(): array
    {
        return ['logistics', 'job'];
    }

    public function handle(): void
    {
        try {
            $month = CarbonImmutable::now()->month;
            $year = CarbonImmutable::now()->year;

            Courier::where('is_active', true)
                ->chunk(50, function ($couriers) use ($month, $year) {
                    foreach ($couriers as $courier) {
                        $this->calculateEarnings($courier, $month, $year);
                    }
                });

            $this->logger->$this->logger->info('Courier earnings calculated', [
                'month' => $month,
                'year' => $year,
                'correlation_id' => $this->correlationId,
            ]);
        } catch (Exception $e) {
            $this->logger->error('Failed to calculate courier earnings', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'correlation_id' => $this->request?->header('X-Correlation-ID', Str::uuid()->toString()),
            ]);
        }
    }

    public function retryUntil(): \DateTime
    {
        return CarbonImmutable::now()->addHours(6);
    }

    public function failed(Exception $exception): void
    {
        $this->logger->error('logistics job failed', [
            'error' => $exception->getMessage(),
        ]);
    }

    private function calculateEarnings(Courier $courier, int $month, int $year): void
    {
        $startDate = CarbonImmutable::now()->setMonth($month)->setYear($year)->startOfMonth();
        $endDate = CarbonImmutable::now()->setMonth($month)->setYear($year)->endOfMonth();

        $deliveredShipments = $courier->shipments()
            ->whereBetween('delivered_at', [$startDate, $endDate])
            ->get();

        $totalEarnings = $deliveredShipments->sum('shipping_cost');

        $this->logger->$this->logger->info('Earnings calculated for courier', [
            'courier_id' => $courier->id,
            'total_earnings' => $totalEarnings,
            'shipment_count' => $deliveredShipments->count(),
            'correlation_id' => $this->correlationId,
        ]);
    }
}
