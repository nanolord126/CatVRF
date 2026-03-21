<?php declare(strict_types=1);

namespace App\Domains\Logistics\Jobs;

use App\Domains\Logistics\Models\CourierService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

final class CalculateCourierEarningsJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(
        private readonly string $correlationId,
    ) {
        $this->onQueue('default');
        $this->withTags(['logistics', 'earnings', 'daily']);
    }

    public function handle(): void
    {
        try {
            $month = now()->month;
            $year = now()->year;

            CourierService::where('is_active', true)
                ->chunk(50, function ($couriers) use ($month, $year) {
                    foreach ($couriers as $courier) {
                        $this->calculateEarnings($courier, $month, $year);
                    }
                });

            Log::channel('audit')->info('Courier earnings calculated', [
                'month' => $month,
                'year' => $year,
                'correlation_id' => $this->correlationId,
            ]);
        } catch (\Throwable $e) {
            Log::channel('audit')->error('Failed to calculate courier earnings', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
        }
    }

    private function calculateEarnings(CourierService $courier, int $month, int $year): void
    {
        $startDate = now()->setMonth($month)->setYear($year)->startOfMonth();
        $endDate = now()->setMonth($month)->setYear($year)->endOfMonth();

        $deliveredShipments = $courier->shipments()
            ->whereBetween('delivered_at', [$startDate, $endDate])
            ->get();

        $totalEarnings = $deliveredShipments->sum('shipping_cost');

        Log::channel('audit')->info('Earnings calculated for courier', [
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
