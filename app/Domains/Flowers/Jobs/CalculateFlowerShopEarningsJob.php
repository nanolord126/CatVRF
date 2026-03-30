<?php declare(strict_types=1);

namespace App\Domains\Flowers\Jobs;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

final class CalculateFlowerShopEarningsJob extends Model
{
    use HasFactory;

    // TODO: Проверить и восстановить содержимое класса, если оно было утеряно
    use Queueable;

        public $tries = 3;

        public function handle(): void
        {
            try {
                DB::transaction(function () {
                    $shops = FlowerShop::query()
                        ->where('is_active', true)
                        ->get();

                    foreach ($shops as $shop) {
                        $completedOrders = $shop->orders()
                            ->where('status', 'delivered')
                            ->where('payment_status', 'paid')
                            ->where('created_at', '>=', now()->subDay())
                            ->get();

                        $totalEarnings = $completedOrders->sum('total_amount');
                        $totalCommission = $completedOrders->sum('commission_amount');
                        $earnings = $totalEarnings - $totalCommission;

                        Log::channel('audit')->info('Flower shop earnings calculated', [
                            'shop_id' => $shop->id,
                            'orders_count' => $completedOrders->count(),
                            'earnings' => $earnings,
                            'commission' => $totalCommission,
                        ]);
                    }
                });
            } catch (\Exception $exception) {
                Log::channel('audit')->error('Flower shop earnings calculation failed', [
                    'error' => $exception->getMessage(),
                ]);
                throw $exception;
            }
        }
}
