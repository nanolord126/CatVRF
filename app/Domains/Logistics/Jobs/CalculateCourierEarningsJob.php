<?php

declare(strict_types=1);

namespace App\Domains\Logistics\Jobs;




use Psr\Log\LoggerInterface;
use Illuminate\Http\Request;
use App\Domains\Logistics\Models\Courier;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
final class CalculateCourierEarningsJob implements ShouldQueue
{
    use \Illuminate\Foundation\Bus\Dispatchable, \Illuminate\Queue\InteractsWithQueue, \Illuminate\Bus\Queueable, \Illuminate\Queue\SerializesModels;

    public function __construct(
        private string $correlationId = '', private readonly Request $request, private readonly LoggerInterface $logger) {
        $this->onQueue('default');
    }

    public function handle(): void
    {
        try {
            $month = now()->month;
            $year = now()->year;

            Courier::where('is_active', true)
                ->chunk(50, function ($couriers) use ($month, $year) {
                    foreach ($couriers as $courier) {
                        $this->calculateEarnings($courier, $month, $year);
                    }
                });

            $this->logger->info('Courier earnings calculated', [
                'month' => $month,
                'year' => $year,
                'correlation_id' => $this->correlationId,
            ]);
        } catch (\Throwable $e) {
            $this->logger->error('Failed to calculate courier earnings', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'correlation_id' => $this->request?->header('X-Correlation-ID', \Illuminate\Support\Str::uuid()->toString()),
            ]);
        }
    }

    private function calculateEarnings(Courier $courier, int $month, int $year): void
    {
        $startDate = now()->setMonth($month)->setYear($year)->startOfMonth();
        $endDate = now()->setMonth($month)->setYear($year)->endOfMonth();

        $deliveredShipments = $courier->shipments()
            ->whereBetween('delivered_at', [$startDate, $endDate])
            ->get();

        $totalEarnings = $deliveredShipments->sum('shipping_cost');

        $this->logger->info('Earnings calculated for courier', [
            'courier_id' => $courier->id,
            'total_earnings' => $totalEarnings,
            'shipment_count' => $deliveredShipments->count(),
            'correlation_id' => $this->correlationId,
        ]);
    }

    public function retryUntil(): \DateTime
    {
        return now()->addHours(6);
    }
}

