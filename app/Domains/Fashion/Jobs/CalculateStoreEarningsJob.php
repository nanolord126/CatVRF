<?php declare(strict_types=1);

namespace App\Domains\Fashion\Jobs;

use Carbon\Carbon;



use Psr\Log\LoggerInterface;
use Illuminate\Http\Request;
final class CalculateStoreEarningsJob
{

    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

        public function __construct(
            private string $correlationId = '', private readonly Request $request, private readonly LoggerInterface $logger) {
            $this->onQueue('default');
        }

        public function handle(): void
        {
            try {
                $currentMonth = Carbon::now()->month;
                $currentYear = Carbon::now()->year;

                FashionStore::where('is_active', true)
                    ->chunk(50, function ($stores) use ($currentMonth, $currentYear) {
                        foreach ($stores as $store) {
                            $this->calculateStoreEarnings($store, $currentMonth, $currentYear);
                        }
                    });

                $this->logger->info('Fashion store earnings calculated', [
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

        private function calculateStoreEarnings(FashionStore $store, int $month, int $year): void
        {
            $startDate = Carbon::now()->setMonth($month)->setYear($year)->startOfMonth();
            $endDate = $startDate->clone()->endOfMonth();

            $deliveredOrders = FashionOrder::where('fashion_store_id', $store->id)
                ->where('status', 'delivered')
                ->whereBetween('delivered_at', [$startDate, $endDate])
                ->get();

            $totalRevenue = $deliveredOrders->sum('total_amount');
            $totalCommission = $deliveredOrders->sum('commission_amount');
            $storeEarnings = $totalRevenue - $totalCommission;

            $this->logger->info('Fashion store earnings calculated', [
                'store_id' => $store->id,
                'month' => $month,
                'year' => $year,
                'total_revenue' => $totalRevenue,
                'total_commission' => $totalCommission,
                'store_earnings' => $storeEarnings,
                'correlation_id' => $this->request?->header('X-Correlation-ID', \Illuminate\Support\Str::uuid()->toString()),
            ]);
        }

        public function retryUntil(): \DateTime
        {
            return Carbon::now()->addHours(6);
        }
}
