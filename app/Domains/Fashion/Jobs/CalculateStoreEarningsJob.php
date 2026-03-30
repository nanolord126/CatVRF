<?php declare(strict_types=1);

namespace App\Domains\Fashion\Jobs;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

final class CalculateStoreEarningsJob extends Model
{
    use HasFactory;

    // TODO: Проверить и восстановить содержимое класса, если оно было утеряно
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

        public function __construct(
            private readonly string $correlationId = '',
        ) {
            $this->onQueue('default');
        }

        public function handle(): void
        {
            try {
                $currentMonth = now()->month;
                $currentYear = now()->year;

                FashionStore::where('is_active', true)
                    ->chunk(50, function ($stores) use ($currentMonth, $currentYear) {
                        foreach ($stores as $store) {
                            $this->calculateStoreEarnings($store, $currentMonth, $currentYear);
                        }
                    });

                Log::channel('audit')->info('Fashion store earnings calculated', [
                    'month' => $currentMonth,
                    'year' => $currentYear,
                    'correlation_id' => $this->correlationId,
                ]);
            } catch (Throwable $e) {
                Log::channel('audit')->error('Failed to calculate fashion store earnings', [
                    'error' => $e->getMessage(),
                    'correlation_id' => $this->correlationId,
                ]);

                throw $e;
            }
        }

        private function calculateStoreEarnings(FashionStore $store, int $month, int $year): void
        {
            $startDate = now()->setMonth($month)->setYear($year)->startOfMonth();
            $endDate = $startDate->clone()->endOfMonth();

            $deliveredOrders = FashionOrder::where('fashion_store_id', $store->id)
                ->where('status', 'delivered')
                ->whereBetween('delivered_at', [$startDate, $endDate])
                ->get();

            $totalRevenue = $deliveredOrders->sum('total_amount');
            $totalCommission = $deliveredOrders->sum('commission_amount');
            $storeEarnings = $totalRevenue - $totalCommission;

            Log::channel('audit')->info('Fashion store earnings calculated', [
                'store_id' => $store->id,
                'month' => $month,
                'year' => $year,
                'total_revenue' => $totalRevenue,
                'total_commission' => $totalCommission,
                'store_earnings' => $storeEarnings,
            ]);
        }

        public function retryUntil(): \DateTime
        {
            return now()->addHours(6);
        }
}
