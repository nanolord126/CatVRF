<?php declare(strict_types=1);

namespace App\Domains\Flowers\Services;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

final class ConsumableDeductionService extends Model
{
    use HasFactory;

    // TODO: Проверить и восстановить содержимое класса, если оно было утеряно
    // Dependencies injected via constructor
        // Add private readonly properties here
        public function __construct(
            private InventoryManagementService $inventoryService
        ) {}

        public function deductForBouquet(
            int $bouquetId,
            string $correlationId
        ): void {
            $consumables = FlowerConsumable::where('bouquet_id', $bouquetId)
                ->get();

            foreach ($consumables as $consumable) {
                $this->inventoryService->deductStock(
                    itemId: $consumable->id,
                    quantity: $consumable->quantity_per_bouquet,
                    reason: 'bouquet_completion',
                    sourceType: 'bouquet',
                    sourceId: $bouquetId
                );

                Log::channel('audit')->info('Consumable deducted', [
                    'consumable_id' => $consumable->id,
                    'bouquet_id' => $bouquetId,
                    'correlation_id' => $correlationId,
                ]);
            }
        }
}
