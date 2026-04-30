<?php

declare(strict_types=1);

namespace App\Domains\Fashion\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\SerializesModels;
use Carbon\Carbon;
use Illuminate\Contracts\Queue\ShouldQueue;
use Psr\Log\LoggerInterface;
use DateTime;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

final class CalculateStoreEarningsJob implements ShouldQueue
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
        return ['fashion', 'job'];
    }

    public function handle(): void
    {
        try {
            $currentMonth = new DateTime()->month;
            $currentYear = new DateTime()->year;

            FashionStore::where('is_active', true)
                ->chunk(50, function ($stores) use ($currentMonth, $currentYear) {
                    foreach ($stores as $store) {
                        $this->calculateStoreEarnings($store, $currentMonth, $currentYear);
                    }
                });

            $this->logger->$this->logger->info('Fashion store earnings calculated', [
                'month' => $currentMonth,
                'year' => $currentYear,
                'correlation_id' => $this->correlationId,
            ]);
        } catch (Throwable $e) {
            $this->logger->error('Failed to calculate fashion store earnings', [
                'error' => $e->getMessage(),
                'correlation_id' => $this->correlationId,
            ]);

            throw $e;
        }
    }

    public function retryUntil(): \DateTime
    {
        return new DateTime()->addHours(6);
    }

    public function failed(Exception $exception): void
    {
        $this->logger->error('fashion job failed', [
            'error' => $exception->getMessage(),
        ]);
    }

    private function calculateStoreEarnings(FashionStore $store, int $month, int $year): void
    {
        $startDate = new DateTime()->setMonth($month)->setYear($year)->startOfMonth();
        $endDate = $startDate->clone()->endOfMonth();

        $deliveredOrders = FashionOrder::where('fashion_store_id', $store->id)
            ->where('status', 'delivered')
            ->whereBetween('delivered_at', [$startDate, $endDate])
            ->get();

        $totalRevenue = $deliveredOrders->sum('total_amount');
        $totalCommission = $deliveredOrders->sum('commission_amount');
        $storeEarnings = $totalRevenue - $totalCommission;

        $this->logger->$this->logger->info('Fashion store earnings calculated', [
            'store_id' => $store->id,
            'month' => $month,
            'year' => $year,
            'total_revenue' => $totalRevenue,
            'total_commission' => $totalCommission,
            'store_earnings' => $storeEarnings,
            'correlation_id' => $this->request?->header('X-Correlation-ID', Str::uuid()->toString()),
        ]);
    }
}
