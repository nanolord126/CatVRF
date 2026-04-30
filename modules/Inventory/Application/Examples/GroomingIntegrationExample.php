<?php

declare(strict_types=1);

namespace Modules\Inventory\Application\Examples;

use Modules\Inventory\Application\Services\FIFOShelfLifeService;
use Modules\Inventory\Domain\Exceptions\InsufficientStockWithExpiryException;
use Modules\Inventory\Domain\Exceptions\ShelfLifeException;
use Modules\Inventory\Infrastructure\Models\InventoryBatchModel;
use Modules\Inventory\Infrastructure\Models\InventoryItemModel;

/**
 * Пример интеграции FIFO системы контроля срока годности с модулем груминга
 * 
 * Сценарии:
 * - Использование шампуней и косметики при груминге
 * - Списание расходных материалов (перчатки, салфетки)
 * - Контроль сроков годности grooming-средств
 */
final class GroomingIntegrationExample
{
    private FIFOShelfLifeService $fifoService;

    public function __construct(FIFOShelfLifeService $fifoService)
    {
        $this->fifoService = $fifoService;
    }

    /**
     * Пример 1: Использование шампуня при груминге
     */
    public function useShampoo(
        int $shampooItemId,
        int $quantityMl,
        int $groomerId,
        int $appointmentId,
        int $petId
    ): array {
        $item = InventoryItemModel::find($shampooItemId);
        if (!$item || $item->category !== 'grooming_product') {
            throw new \InvalidArgumentException('Шампунь не найден или неверная категория');
        }

        $domainItem = $item->toDomain();

        // Проверка срока годности (строже для косметики - минимум 60 дней)
        if ($domainItem->getDaysUntilExpiry() !== null && $domainItem->getDaysUntilExpiry() < 60) {
            \Illuminate\Support\Facades\Log::warning('Использование шампуня с коротким сроком годности', [
                'shampoo_id' => $shampooItemId,
                'days_left' => $domainItem->getDaysUntilExpiry(),
                'groomer_id' => $groomerId,
            ]);
        }

        try {
            // Автоматическое FIFO-списание
            $result = $this->fifoService->autoDeduct(
                $domainItem,
                $quantityMl,
                'grooming',
                [
                    'groomer_id' => $groomerId,
                    'appointment_id' => $appointmentId,
                    'pet_id' => $petId,
                    'product_type' => 'shampoo',
                ]
            );

            // Запись в карточку груминга
            $this->recordProductUsageInGroomingCard(
                $appointmentId,
                $shampooItemId,
                $quantityMl,
                $result->deductedBatches
            );

            return [
                'quantity_used' => $quantityMl,
                'batch_number' => $result->deductedBatches[0]['batch_number'],
                'expiry_date' => $result->deductedBatches[0]['expiry_date'],
                'days_left' => $result->deductedBatches[0]['days_left'],
            ];

        } catch (ShelfLifeException $e) {
            throw new \RuntimeException(
                "Невозможно использовать шампунь: {$e->getMessage()}"
            );
        } catch (InsufficientStockWithExpiryException $e) {
            throw new \RuntimeException(
                "Недостаточно шампуня с действующим сроком годности: {$e->getMessage()}"
            );
        }
    }

    /**
     * Пример 2: Использование масла или кондиционера
     */
    public function useConditioningOil(
        int $oilItemId,
        int $quantityMl,
        int $groomerId,
        int $appointmentId
    ): array {
        $item = InventoryItemModel::find($oilItemId);
        if (!$item) {
            throw new \InvalidArgumentException('Масло не найдено');
        }

        $domainItem = $item->toDomain();

        // Проверка срока годности
        if ($domainItem->isExpired()) {
            throw new \RuntimeException('Масло просрочено и не может быть использовано');
        }

        // FIFO-списание
        $result = $this->fifoService->autoDeduct(
            $domainItem,
            $quantityMl,
            'grooming',
            [
                'groomer_id' => $groomerId,
                'appointment_id' => $appointmentId,
                'product_type' => 'conditioning_oil',
            ]
        );

        return [
            'quantity_used' => $quantityMl,
            'batch_info' => $result->deductedBatches->toArray(),
        ];
    }

    /**
     * Пример 3: Списание расходных материалов (перчатки, салфетки)
     */
    public function deductConsumables(
        int $consumableItemId,
        int $quantity,
        int $groomerId,
        ?int $appointmentId = null
    ): void {
        $item = InventoryItemModel::find($consumableItemId);
        if (!$item) {
            throw new \InvalidArgumentException('Расходный материал не найден');
        }

        $domainItem = $item->toDomain();

        // Для расходных материалов контроль срока годности мягче
        if ($item->is_controlled && $domainItem->isExpired()) {
            throw new \RuntimeException('Расходный материал просрочен');
        }

        $result = $this->fifoService->autoDeduct(
            $domainItem,
            $quantity,
            'grooming',
            [
                'groomer_id' => $groomerId,
                'appointment_id' => $appointmentId,
                'product_type' => 'consumable',
            ]
        );

        \Illuminate\Support\Facades\Log::info('Списан расходный материал', [
            'item_id' => $consumableItemId,
            'quantity' => $quantity,
            'groomer_id' => $groomerId,
            'appointment_id' => $appointmentId,
            'batches' => $result->deductedBatches,
        ]);
    }

    /**
     * Пример 4: Проверка доступности grooming-средств перед записью
     */
    public function checkGroomingProductAvailability(
        int $productId,
        int $requiredQuantity
    ): array {
        $item = InventoryItemModel::find($productId);
        if (!$item) {
            return ['available' => false, 'reason' => 'Продукт не найден'];
        }

        $domainItem = $item->toDomain();

        // Проверка срока годности
        if ($domainItem->isExpired()) {
            return [
                'available' => false,
                'reason' => 'Продукт просрочен',
                'expiry_date' => $domainItem->expiryDate?->format('Y-m-d'),
            ];
        }

        // Предупреждение если срок подходит к концу
        $warning = null;
        if ($domainItem->isExpiringSoon(30)) {
            $warning = "Срок годности истекает через {$domainItem->getDaysUntilExpiry()} дней";
        }

        // Проверка доступного количества
        $availableStock = InventoryBatchModel::where('inventory_item_id', $item->id)
            ->usable()
            ->sum('current_quantity');

        if ($availableStock < $requiredQuantity) {
            return [
                'available' => false,
                'reason' => 'Недостаточно количества',
                'required' => $requiredQuantity,
                'available' => $availableStock,
                'warning' => $warning,
            ];
        }

        return [
            'available' => true,
            'stock' => $availableStock,
            'warning' => $warning,
            'next_expiry_batch' => $this->fifoService->getNextExpiringBatch($item->id)?->batch_number,
        ];
    }

    /**
     * Пример 5: Поступление новой партии grooming-средств
     */
    public function receiveGroomingProductShipment(
        int $productId,
        string $batchNumber,
        string $manufactureDate,
        string $expiryDate,
        int $quantity,
        float $purchasePrice,
        string $storageLocation
    ): InventoryBatchModel {
        $item = InventoryItemModel::find($productId);
        if (!$item) {
            throw new \InvalidArgumentException('Продукт не найден');
        }

        // Создание партии
        $batch = InventoryBatchModel::create([
            'inventory_item_id' => $item->id,
            'tenant_id' => $item->tenant_id,
            'batch_number' => $batchNumber,
            'manufacture_date' => $manufactureDate,
            'expiry_date' => $expiryDate,
            'initial_quantity' => $quantity,
            'current_quantity' => $quantity,
            'purchase_price' => $purchasePrice,
            'storage_location' => $storageLocation,
            'status' => 'active',
        ]);

        // Обновление общего количества
        $item->increment('quantity', $quantity);

        // Уведомление если срок короткий
        if ($batch->isExpiringSoon(90)) {
            $this->notifyAboutShortShelfLife($batch, 'grooming');
        }

        return $batch;
    }

    /**
     * Пример 6: Отчёт по использованию grooming-средств грумером
     */
    public function getGroomerUsageReport(
        int $groomerId,
        string $startDate,
        string $endDate
    ): array {
        // В реальной реализации нужна таблица для хранения FIFO-движений
        // Здесь пример структуры отчёта

        return [
            'groomer_id' => $groomerId,
            'period' => [
                'start' => $startDate,
                'end' => $endDate,
            ],
            'products_used' => [
                [
                    'product_id' => 1,
                    'product_name' => 'Шампунь для собак',
                    'total_quantity' => 500, // мл
                    'unit' => 'мл',
                    'batches_used' => [
                        [
                            'batch_number' => 'BATCH-001',
                            'quantity' => 300,
                            'expiry_date' => '2026-12-31',
                        ],
                        [
                            'batch_number' => 'BATCH-002',
                            'quantity' => 200,
                            'expiry_date' => '2027-01-15',
                        ],
                    ],
                ],
            ],
            'appointments_count' => 25,
        ];
    }

    /**
     * Пример 7: Автоматическое списание комплекта для услуги
     */
    public function deductServicePackage(
        int $groomerId,
        int $appointmentId,
        int $petId,
        array $products // [['product_id' => 1, 'quantity' => 50], ...]
    ): array {
        $results = [];

        foreach ($products as $product) {
            try {
                $item = InventoryItemModel::find($product['product_id']);
                if (!$item) {
                    $results[] = [
                        'product_id' => $product['product_id'],
                        'status' => 'error',
                        'message' => 'Продукт не найден',
                    ];
                    continue;
                }

                $domainItem = $item->toDomain();

                $result = $this->fifoService->autoDeduct(
                    $domainItem,
                    $product['quantity'],
                    'grooming',
                    [
                        'groomer_id' => $groomerId,
                        'appointment_id' => $appointmentId,
                        'pet_id' => $petId,
                    ]
                );

                $results[] = [
                    'product_id' => $product['product_id'],
                    'product_name' => $item->name,
                    'status' => 'success',
                    'quantity_used' => $product['quantity'],
                    'batch_number' => $result->deductedBatches[0]['batch_number'],
                ];

            } catch (\Exception $e) {
                $results[] = [
                    'product_id' => $product['product_id'],
                    'status' => 'error',
                    'message' => $e->getMessage(),
                ];
            }
        }

        return $results;
    }

    private function recordProductUsageInGroomingCard(
        int $appointmentId,
        int $productId,
        int $quantity,
        $batches
    ): void {
        // Интеграция с модулем груминга
        // Запись использования продукта в карточку услуги
        \Illuminate\Support\Facades\Log::info('Записано использование продукта в карточку груминга', [
            'appointment_id' => $appointmentId,
            'product_id' => $productId,
            'quantity' => $quantity,
            'batches' => $batches,
        ]);
    }

    private function notifyAboutShortShelfLife(InventoryBatchModel $batch, string $vertical): void
    {
        \Illuminate\Support\Facades\Log::warning('Поступила партия с коротким сроком годности', [
            'vertical' => $vertical,
            'batch_number' => $batch->batch_number,
            'expiry_date' => $batch->expiry_date->format('Y-m-d'),
            'days_left' => $batch->getDaysUntilExpiry(),
        ]);
    }
}
