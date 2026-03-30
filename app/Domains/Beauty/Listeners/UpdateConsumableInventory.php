<?php declare(strict_types=1);

namespace App\Domains\Beauty\Listeners;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

final class UpdateConsumableInventory extends Model
{
    use HasFactory;

    // TODO: Проверить и восстановить содержимое класса, если оно было утеряно
    public function handle(ConsumableDeducted $event): void
        {
            try {
                DB::transaction(function () use ($event) {
                    Log::channel('audit')->info('Consumable inventory updated', [
                        'appointment_id' => $event->appointmentId,
                        'consumables_count' => count($event->consumables),
                        'correlation_id' => $event->correlationId,
                        'action' => 'consumable_inventory_deducted',
                    ]);
                    // foreach ($event->consumables as $consumable) {
                    //     InventoryService::deduct($consumable['id'], $consumable['quantity']);
                    // }
                });
            } catch (\Exception $e) {
                Log::channel('audit')->error('Failed to update consumable inventory', [
                    'correlation_id' => $event->correlationId,
                    'error' => $e->getMessage(),
                    'trace' => $e->getTraceAsString(),
                ]);
            }
        }
}
