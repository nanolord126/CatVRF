<?php

declare(strict_types=1);

namespace Modules\Inventory\Application\Examples;

use Modules\Inventory\Application\Services\FIFOShelfLifeService;
use Modules\Inventory\Domain\Exceptions\InsufficientStockWithExpiryException;
use Modules\Inventory\Domain\Exceptions\ShelfLifeException;
use Modules\Inventory\Infrastructure\Models\InventoryBatchModel;
use Modules\Inventory\Infrastructure\Models\InventoryItemModel;

/**
 * Пример интеграции FIFO системы контроля срока годности с вертикалью Fashion
 * 
 * Специфика Fashion:
 * - Одежда и аксессуары (обычно без срока годности, но могут быть сезонные)
 * - Косметика и парфюмерия (строгий контроль срока)
 * - Обувь (сезонный учёт)
 * - Ткани и материалы (контроль условий хранения)
 * - Расходные материалы для шитья
 */
final class FashionIntegrationExample
{
    private FIFOShelfLifeService $fifoService;

    public function __construct(FIFOShelfLifeService $fifoService)
    {
        $this->fifoService = $fifoService;
    }

    /**
     * Пример 1: Продажа парфюмерии с контролем срока годности
     */
    public function sellPerfume(
        int $perfumeItemId,
        int $quantity,
        int $cashierId,
        int $orderId
    ): array {
        $item = InventoryItemModel::find($perfumeItemId);
        if (!$item) {
            throw new \InvalidArgumentException('Парфюм не найден');
        }

        $domainItem = $item->toDomain();

        // Строгий контроль для парфюмерии - минимум 12 месяцев
        if ($domainItem->getDaysUntilExpiry() !== null && $domainItem->getDaysUntilExpiry() < 365) {
            throw new \RuntimeException(
                'Парфюмерия с сроком менее 12 месяцев не может быть продана'
            );
        }

        try {
            $result = $this->fifoService->autoDeduct(
                $domainItem,
                $quantity,
                'sale',
                [
                    'order_id' => $orderId,
                    'cashier_id' => $cashierId,
                    'product_type' => 'perfume',
                ]
            );

            return [
                'perfume_name' => $item->name,
                'brand' => $item->metadata['brand'] ?? null,
                'quantity' => $quantity,
                'price' => $item->selling_price,
                'total' => $item->selling_price * $quantity,
                'batch_number' => $result->deductedBatches[0]['batch_number'],
                'expiry_date' => $result->deductedBatches[0]['expiry_date'],
                'days_left' => $result->deductedBatches[0]['days_left'],
            ];

        } catch (ShelfLifeException $e) {
            throw new \RuntimeException("Парфюмерия не может быть продана: {$e->getMessage()}");
        }
    }

    /**
     * Пример 2: Продажа косметики с контролем срока
     */
    public function sellCosmetics(
        int $cosmeticsItemId,
        int $quantity,
        int $cashierId,
        int $orderId
    ): array {
        $item = InventoryItemModel::find($cosmeticsItemId);
        if (!$item) {
            throw new \InvalidArgumentException('Косметика не найдена');
        }

        $domainItem = $item->toDomain();

        // Контроль для косметики - минимум 6 месяцев
        if ($domainItem->isExpired()) {
            throw new \RuntimeException('Косметика просрочена и не может быть продана');
        }

        if ($domainItem->getDaysUntilExpiry() !== null && $domainItem->getDaysUntilExpiry() < 180) {
            \Illuminate\Support\Facades\Log::warning('Продажа косметики с коротким сроком', [
                'cosmetics_id' => $cosmeticsItemId,
                'days_left' => $domainItem->getDaysUntilExpiry(),
            ]);
        }

        $result = $this->fifoService->autoDeduct(
            $domainItem,
            $quantity,
            'sale',
            [
                'order_id' => $orderId,
                'cashier_id' => $cashierId,
                'product_type' => 'cosmetics',
            ]
        );

        return [
            'cosmetics_name' => $item->name,
            'quantity' => $quantity,
            'price' => $item->selling_price,
            'batch_number' => $result->deductedBatches[0]['batch_number'],
            'expiry_date' => $result->deductedBatches[0]['expiry_date'],
        ];
    }

    /**
     * Пример 3: Продажа одежды (без контроля срока, но с учётом сезона)
     */
    public function sellClothing(
        int $clothingItemId,
        int $quantity,
        int $cashierId,
        int $orderId
    ): array {
        $item = InventoryItemModel::find($clothingItemId);
        if (!$item) {
            throw new \InvalidArgumentException('Одежда не найдена');
        }

        $domainItem = $item->toDomain();

        // Для одежды контроль срока не требуется, если не указан
        if ($item->is_controlled && $domainItem->expiryDate !== null && $domainItem->isExpired()) {
            throw new \RuntimeException('Одежда просрочена (сезон)');
        }

        // Проверка доступного количества
        if ($item->quantity < $quantity) {
            throw new \RuntimeException('Недостаточно одежды на складе');
        }

        // Прямое списание без FIFO (для одежды без срока годности)
        $item->decrement('quantity', $quantity);

        return [
            'clothing_name' => $item->name,
            'sku' => $item->sku,
            'size' => $item->metadata['size'] ?? null,
            'color' => $item->metadata['color'] ?? null,
            'quantity' => $quantity,
            'price' => $item->selling_price,
            'total' => $item->selling_price * $quantity,
            'season_warning' => $this->getSeasonWarning($item),
        ];
    }

    /**
     * Пример 4: Списание материалов для шитья/ателье
     */
    public function deductFabricForProduction(
        int $fabricItemId,
        int $quantityMeters,
        int $tailorId,
        int $productionOrderId
    ): array {
        $item = InventoryItemModel::find($fabricItemId);
        if (!$item) {
            throw new \InvalidArgumentException('Ткань не найдена');
        }

        $domainItem = $item->toDomain();

        // Проверка условий хранения (если есть срок годности)
        if ($item->is_controlled && $domainItem->isExpired()) {
            throw new \RuntimeException('Ткань просрочена или не соответствует условиям хранения');
        }

        $result = $this->fifoService->autoDeduct(
            $domainItem,
            $quantityMeters,
            'production',
            [
                'tailor_id' => $tailorId,
                'production_order_id' => $productionOrderId,
                'material_type' => 'fabric',
            ]
        );

        return [
            'fabric_name' => $item->name,
            'quantity_meters' => $quantityMeters,
            'batch_number' => $result->deductedBatches[0]['batch_number'],
            'expiry_date' => $result->deductedBatches[0]['expiry_date'],
        ];
    }

    /**
     * Пример 5: Проверка набора для образа (outfit)
     */
    public function checkOutfitAvailability(
        array $outfitItems // [['product_id' => 1, 'quantity' => 1], ...]
    ): array {
        $availability = [];

        foreach ($outfitItems as $outfitItem) {
            $item = InventoryItemModel::find($outfitItem['product_id']);
            if (!$item) {
                $availability[] = [
                    'product_id' => $outfitItem['product_id'],
                    'available' => false,
                    'reason' => 'Не найден',
                ];
                continue;
            }

            $domainItem = $item->toDomain();

            // Проверка срока для контролируемых товаров
            if ($item->is_controlled && $domainItem->isExpired()) {
                $availability[] = [
                    'product_id' => $outfitItem['product_id'],
                    'product_name' => $item->name,
                    'available' => false,
                    'reason' => 'Просрочен',
                ];
                continue;
            }

            $availableStock = $item->quantity; // Для одежды без FIFO

            $availability[] = [
                'product_id' => $outfitItem['product_id'],
                'product_name' => $item->name,
                'available' => $availableStock >= $outfitItem['quantity'],
                'required' => $outfitItem['quantity'],
                'available_stock' => $availableStock,
                'size' => $item->metadata['size'] ?? null,
                'color' => $item->metadata['color'] ?? null,
            ];
        }

        $allAvailable = collect($availability)->every('available');

        return [
            'all_available' => $allAvailable,
            'items' => $availability,
        ];
    }

    /**
     * Пример 6: Сезонные скидки на одежду
     */
    public function applySeasonalDiscount(
        int $clothingItemId,
        float $basePrice
    ): array {
        $item = InventoryItemModel::find($clothingItemId);
        if (!$item) {
            return ['discount_applied' => false, 'final_price' => $basePrice];
        }

        $season = $item->metadata['season'] ?? null;
        if (!$season) {
            return ['discount_applied' => false, 'final_price' => $basePrice];
        }

        $currentMonth = now()->month;
        $discountPercent = 0;

        // Определение скидки в зависимости от сезона
        $seasonEndMap = [
            'spring' => 5,  // Май - конец весны
            'summer' => 8,  // Август - конец лета
            'autumn' => 11, // Ноябрь - конец осени
            'winter' => 2,  // Февраль - конец зимы
        ];

        $endMonth = $seasonEndMap[$season] ?? 12;

        if ($currentMonth > $endMonth) {
            // Сезон прошёл - скидка
            $monthsPast = $currentMonth - $endMonth;
            $discountPercent = min($monthsPast * 10, 70); // До 70% скидки
        }

        if ($discountPercent > 0) {
            $discountAmount = $basePrice * ($discountPercent / 100);
            $finalPrice = $basePrice - $discountAmount;

            return [
                'discount_applied' => true,
                'discount_percent' => $discountPercent,
                'discount_amount' => $discountAmount,
                'final_price' => $finalPrice,
                'reason' => "Сезонная скидка (сезон: {$season})",
            ];
        }

        return [
            'discount_applied' => false,
            'final_price' => $basePrice,
            'season' => $season,
        ];
    }

    /**
     * Пример 7: Поступление fashion-товаров
     */
    public function receiveFashionShipment(
        array $shipments
    ): array {
        $results = [];

        foreach ($shipments as $shipment) {
            try {
                $item = InventoryItemModel::find($shipment['product_id']);
                if (!$item) {
                    $results[] = [
                        'product_id' => $shipment['product_id'],
                        'status' => 'error',
                        'message' => 'Товар не найден',
                    ];
                    continue;
                }

                // Создание партии (только для товаров с контролем срока)
                if ($item->is_controlled && isset($shipment['expiry_date'])) {
                    $batch = InventoryBatchModel::create([
                        'inventory_item_id' => $item->id,
                        'tenant_id' => $item->tenant_id,
                        'batch_number' => $shipment['batch_number'] ?? uniqid(),
                        'manufacture_date' => $shipment['manufacture_date'] ?? null,
                        'expiry_date' => $shipment['expiry_date'],
                        'initial_quantity' => $shipment['quantity'],
                        'current_quantity' => $shipment['quantity'],
                        'purchase_price' => $shipment['purchase_price'] ?? 0,
                        'storage_location' => $shipment['storage_location'] ?? 'Fashion Warehouse',
                        'status' => 'active',
                    ]);

                    $item->increment('quantity', $shipment['quantity']);

                    $results[] = [
                        'product_id' => $shipment['product_id'],
                        'product_name' => $item->name,
                        'quantity' => $shipment['quantity'],
                        'batch_number' => $batch->batch_number,
                        'expiry_date' => $batch->expiry_date->format('Y-m-d'),
                        'status' => 'success',
                    ];
                } else {
                    // Для одежды без срока - просто увеличение количества
                    $item->increment('quantity', $shipment['quantity']);

                    $results[] = [
                        'product_id' => $shipment['product_id'],
                        'product_name' => $item->name,
                        'quantity' => $shipment['quantity'],
                        'status' => 'success',
                        'note' => 'Без срока годности',
                    ];
                }

            } catch (\Exception $e) {
                $results[] = [
                    'product_id' => $shipment['product_id'],
                    'status' => 'error',
                    'message' => $e->getMessage(),
                ];
            }
        }

        return $results;
    }

    /**
     * Пример 8: Отчёт по просроченной парфюмерии и косметике
     */
    public function getExpiredFashionProductsReport(): array
    {
        $expiredBatches = InventoryBatchModel::where('status', 'expired')
            ->whereHas('inventoryItem', function ($query) {
                $query->where('is_controlled', true);
            })
            ->with('inventoryItem')
            ->get();

        return [
            'total_expired_batches' => $expiredBatches->count(),
            'expired_products' => $expiredBatches->map(fn ($batch) => [
                'product_name' => $batch->inventoryItem->name,
                'category' => $batch->inventoryItem->category,
                'batch_number' => $batch->batch_number,
                'expiry_date' => $batch->expiry_date->format('Y-m-d'),
                'quantity' => $batch->current_quantity,
                'days_expired' => abs($batch->getDaysUntilExpiry()),
                'purchase_value' => $batch->current_quantity * $batch->purchase_price,
            ])->toArray(),
        ];
    }

    private function getSeasonWarning(InventoryItemModel $item): ?string
    {
        $season = $item->metadata['season'] ?? null;
        if (!$season) {
            return null;
        }

        $currentMonth = now()->month;
        $seasonEndMap = [
            'spring' => 5,
            'summer' => 8,
            'autumn' => 11,
            'winter' => 2,
        ];

        $endMonth = $seasonEndMap[$season] ?? 12;

        if ($currentMonth > $endMonth) {
            return "Сезон {$season} закончился, возможна скидка";
        }

        return null;
    }
}
