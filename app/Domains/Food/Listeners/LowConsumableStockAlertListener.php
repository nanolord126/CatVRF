<?php declare(strict_types=1);

namespace App\Domains\Food\Listeners;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

final class LowConsumableStockAlertListener extends Model
{
    use HasFactory;

    // TODO: Проверить и восстановить содержимое класса, если оно было утеряно
    public function handle(LowConsumableStock $event): void
        {
            try {
                Log::channel('audit')->warning('Low consumable stock alert', [
                    'consumable_id' => $event->consumable->id,
                    'name' => $event->consumable->name,
                    'current_stock' => $event->consumable->current_stock,
                    'min_threshold' => $event->consumable->min_stock_threshold,
                    'unit' => $event->consumable->unit,
                    'correlation_id' => $event->correlationId,
                ]);
                // Notification::send($event->consumable->restaurant->owner, new LowStockNotification($event->consumable));
            } catch (\Throwable $e) {
                Log::channel('audit')->error('Low stock alert failed', [
                    'error' => $e->getMessage(),
                    'correlation_id' => $event->correlationId,
                ]);

                throw $e;
            }
        }
}
