<?php

declare(strict_types=1);

namespace App\Domains\Flowers\Services;

use App\Domains\Flowers\Models\FlowerConsumable;
use App\Services\InventoryManagementService;
use Illuminate\Support\Facades\Log;

final readonly class ConsumableDeductionService
{
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
