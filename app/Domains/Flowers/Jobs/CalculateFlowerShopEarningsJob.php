<?php

declare(strict_types=1);


namespace App\Domains\Flowers\Jobs;

use App\Domains\Flowers\Models\FlowerShop;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

final /**
 * CalculateFlowerShopEarningsJob
 * 
 * Основной класс для работы с платформой CatVRF.
 * 
 * @author CatVRF
 * @package %NAMESPACE%
 * @version 1.0.0
 */
class CalculateFlowerShopEarningsJob implements ShouldQueue
{
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
