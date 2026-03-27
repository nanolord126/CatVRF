<?php declare(strict_types=1);

namespace App\Domains\Fashion\Listeners;

use App\Domains\Fashion\Events\ItemReservedEvent;
use App\Domains\Fashion\Models\FashionProduct;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;

/**
 * HandleStockReservation
 * 
 * Обработчик резервирования товара.
 * Реализует транзакционный резерв и логирование аудита.
 */
class HandleStockReservation implements ShouldQueue
{
    use InteractsWithQueue;

    public function handle(ItemReservedEvent $event): void
    {
        Log::channel('audit')->info('Stock reservation listener triggered', [
            'product_id' => $event->product->id,
            'quantity' => $event->quantity,
            'correlation_id' => $event->correlationId,
        ]);

        try {
            DB::transaction(function () use ($event) {
                $product = FashionProduct::lockForUpdate()->find($event->product->id);

                if ($product->quantity < $event->quantity) {
                    throw new \Exception('Insufficient stock for reservation');
                }

                // Увеличение reserve_quantity на 20 мин (логика сброса в Jobs)
                $product->increment('reserve_quantity', $event->quantity);

                Log::channel('audit')->info('Reservation successful', [
                    'product_id' => $product->id,
                    'new_reserve' => $product->reserve_quantity,
                    'correlation_id' => $event->correlationId,
                ]);
            });
        } catch (\Throwable $e) {
            Log::channel('audit')->error('Stock reservation failed in listener', [
                'error' => $e->getMessage(),
                'correlation_id' => $event->correlationId,
            ]);
        }
    }
}
