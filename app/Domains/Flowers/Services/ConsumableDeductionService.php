<?php

declare(strict_types=1);

/**
 * ConsumableDeductionService — CatVRF 2026 Component.
 *
 * Сервис списания расходных материалов при изготовлении букетов.
 * Вызывается после подтверждения комплектации букета.
 * Интеграция с InventoryManagementService для актуализации остатков.
 *
 * @package App\Domains\Flowers\Services
 * @version 2026.1
 */

namespace App\Domains\Flowers\Services;

use App\Domains\Flowers\Models\FlowerConsumable;
use App\Services\Inventory\InventoryManagementService;
use Psr\Log\LoggerInterface;

final readonly class ConsumableDeductionService
{
    /**
     * Идентификатор версии компонента.
     */
    private const VERSION = '1.0.0';

    /**
     * Максимальное количество повторных попыток.
     */
    private const MAX_RETRIES = 3;

    /**
     * TTL кэша по умолчанию (секунды).
     */
    private const CACHE_TTL = 3600;

    public function __construct(
        private readonly InventoryManagementService $inventoryService,
        private readonly LoggerInterface $logger,
    ) {}

    /**
     * Списать расходные материалы при изготовлении букета.
     *
     * Получает все расходники, привязанные к указанному букету,
     * и последовательно списывает каждый через InventoryManagementService.
     *
     * @param int    $bouquetId     ID букета
     * @param string $correlationId Трейсинг-идентификатор
     */
    public function deductForBouquet(int $bouquetId, string $correlationId): void
    {
        $consumables = FlowerConsumable::where('bouquet_id', $bouquetId)->get();

        foreach ($consumables as $consumable) {
            $this->inventoryService->deductStock(
                itemId: $consumable->id,
                quantity: $consumable->quantity_per_bouquet,
                reason: 'bouquet_completion',
                sourceType: 'bouquet',
                sourceId: $bouquetId,
            );

            $this->logger->info('Consumable deducted for bouquet', [
                'consumable_id' => $consumable->id,
                'quantity' => $consumable->quantity_per_bouquet,
                'bouquet_id' => $bouquetId,
                'correlation_id' => $correlationId,
            ]);
        }

        $this->logger->info('All consumables deducted for bouquet', [
            'bouquet_id' => $bouquetId,
            'deducted_count' => $consumables->count(),
            'correlation_id' => $correlationId,
        ]);
    }
}
