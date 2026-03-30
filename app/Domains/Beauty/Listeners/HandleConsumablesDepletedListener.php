<?php declare(strict_types=1);

namespace App\Domains\Beauty\Listeners;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

final class HandleConsumablesDepletedListener extends Model
{
    use HasFactory;

    // TODO: Проверить и восстановить содержимое класса, если оно было утеряно
    public function handle(ConsumablesDepleted $event): void
        {
            $consumable = $event->consumable;

            // Check if stock is below threshold
            if ($consumable->current_stock <= $consumable->min_stock_threshold) {
                NotifyLowConsumablesJob::dispatch($event->correlationId);

                Log::channel('audit')->warning('Consumable depleted below threshold', [
                    'consumable_id' => $consumable->id,
                    'name' => $consumable->name,
                    'current_stock' => $consumable->current_stock,
                    'threshold' => $consumable->min_stock_threshold,
                    'correlation_id' => $event->correlationId,
                ]);
            }

            Log::channel('audit')->info('ConsumablesDepleted event handled', [
                'consumable_id' => $consumable->id,
                'quantity_depleted' => $event->quantity,
                'correlation_id' => $event->correlationId,
            ]);
        }
}
