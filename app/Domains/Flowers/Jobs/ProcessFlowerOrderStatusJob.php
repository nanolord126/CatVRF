<?php declare(strict_types=1);

namespace App\Domains\Flowers\Jobs;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

final class ProcessFlowerOrderStatusJob extends Model
{
    use HasFactory;

    // TODO: Проверить и восстановить содержимое класса, если оно было утеряно
    use Queueable;

        public $tries = 3;
        public $backoff = [60, 300, 900];

        public function handle(): void
        {
            try {
                DB::transaction(function () {
                    $orders = FlowerOrder::query()
                        ->where('status', 'confirmed')
                        ->where('delivery_date', '>=', now()->toDateString())
                        ->where('delivery_date', '<', now()->addDay()->toDateString())
                        ->get();

                    foreach ($orders as $order) {
                        if ($order->items()->count() > 0) {
                            $order->update(['status' => 'preparing']);

                            Log::channel('audit')->info('Flower order marked as preparing', [
                                'order_id' => $order->id,
                                'shop_id' => $order->shop_id,
                                'correlation_id' => $order->correlation_id,
                            ]);
                        }
                    }
                });
            } catch (\Exception $exception) {
                Log::channel('audit')->error('Flower order status processing failed', [
                    'error' => $exception->getMessage(),
                ]);
                throw $exception;
            }
        }
}
