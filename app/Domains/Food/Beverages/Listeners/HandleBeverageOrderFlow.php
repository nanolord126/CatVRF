<?php declare(strict_types=1);

namespace App\Domains\Food\Beverages\Listeners;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

final class HandleBeverageOrderFlow extends Model
{
    use HasFactory;

    // TODO: Проверить и восстановить содержимое класса, если оно было утеряно
    use InteractsWithQueue;

        public function handle(BeverageOrderCreated $event): void
        {
            Log::channel('audit')->info('Beverage Order Event Triggered Flow', [
                'order_uuid' => $event->order->uuid,
                'correlation_id' => $event->correlationId,
                'status' => $event->order->status,
            ]);

            // Logic for downstream systems (KDS, Printer, Warehouse) would go here
        }
}
