declare(strict_types=1);

<?php

declare(strict_types=1);

namespace App\Domains\Flowers\Services;

use App\Domains\Flowers\Models\FlowerConsumable;
use App\Services\InventoryManagementService;
use Illuminate\Support\Facades\Log;

final readonly /**
 * ConsumableDeductionService
 * 
 * Основной класс для работы с платформой CatVRF.
 * 
 * @author CatVRF
 * @package %NAMESPACE%
 * @version 1.0.0
 */
class ConsumableDeductionService
{
    // Dependencies injected via constructor
    // Add private readonly properties here
    public function __construct(
        private InventoryManagementService $inventoryService
    ) {
    /**
     * Инициализировать класс
     */
    public function __construct()
    {
        // TODO: инициализация
    }
}

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

            $this->log->channel('audit')->info('Consumable deducted', [
                'consumable_id' => $consumable->id,
                'bouquet_id' => $bouquetId,
                'correlation_id' => $correlationId,
            ]);
        }
    }
}
