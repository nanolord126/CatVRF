<?php

declare(strict_types=1);

namespace Modules\Inventory\Application\Examples;

use Modules\Inventory\Application\Services\FIFOShelfLifeService;
use Modules\Inventory\Domain\Exceptions\InsufficientStockWithExpiryException;
use Modules\Inventory\Domain\Exceptions\ShelfLifeException;
use Modules\Inventory\Infrastructure\Models\InventoryBatchModel;
use Modules\Inventory\Infrastructure\Models\InventoryItemModel;

/**
 * Пример интеграции FIFO системы контроля срока годности с модулем кухни/стационара
 * 
 * Сценарии:
 * - Списание продуктов для приготовления питания
 * - Жёсткий контроль сроков годности продуктов
 * - Ежедневный отчёт по кухне
 * - Управление продуктами для стационара
 */
final class KitchenIntegrationExample
{
    private FIFOShelfLifeService $fifoService;

    public function __construct(FIFOShelfLifeService $fifoService)
    {
        $this->fifoService = $fifoService;
    }

    /**
     * Пример 1: Списание продуктов для приготовления питания
     */
    public function deductIngredientsForMeal(
        int $kitchenId,
        array $ingredients, // [['product_id' => 1, 'quantity' => 500, 'unit' => 'г'], ...]
        string $mealType, // breakfast, lunch, dinner
        string $mealDate
    ): array {
        $results = [];
        $failedIngredients = [];

        foreach ($ingredients as $ingredient) {
            try {
                $item = InventoryItemModel::find($ingredient['product_id']);
                if (!$item || $item->category !== 'kitchen_product') {
                    $failedIngredients[] = [
                        'product_id' => $ingredient['product_id'],
                        'reason' => 'Продукт не найден или неверная категория',
                    ];
                    continue;
                }

                $domainItem = $item->toDomain();

                // Жёсткая проверка срока годности для продуктов
                if ($domainItem->isExpired()) {
                    $failedIngredients[] = [
                        'product_id' => $ingredient['product_id'],
                        'product_name' => $item->name,
                        'reason' => 'Просрочен',
                        'expiry_date' => $domainItem->expiryDate?->format('Y-m-d'),
                    ];
                    continue;
                }

                // Предупреждение если срок подходит к концу (строже - 7 дней)
                if ($domainItem->isExpiringSoon(7)) {
                    \Illuminate\Support\Facades\Log::warning('Использование продукта с коротким сроком годности', [
                        'product_id' => $ingredient['product_id'],
                        'product_name' => $item->name,
                        'days_left' => $domainItem->getDaysUntilExpiry(),
                        'kitchen_id' => $kitchenId,
                    ]);
                }

                // FIFO-списание
                $result = $this->fifoService->autoDeduct(
                    $domainItem,
                    $ingredient['quantity'],
                    'kitchen',
                    [
                        'kitchen_id' => $kitchenId,
                        'meal_type' => $mealType,
                        'meal_date' => $mealDate,
                    ]
                );

                $results[] = [
                    'product_id' => $ingredient['product_id'],
                    'product_name' => $item->name,
                    'quantity' => $ingredient['quantity'],
                    'unit' => $ingredient['unit'],
                    'batch_number' => $result->deductedBatches[0]['batch_number'],
                    'expiry_date' => $result->deductedBatches[0]['expiry_date'],
                    'days_left' => $result->deductedBatches[0]['days_left'],
                    'status' => 'success',
                ];

            } catch (InsufficientStockWithExpiryException $e) {
                $failedIngredients[] = [
                    'product_id' => $ingredient['product_id'],
                    'reason' => 'Недостаточно продукта с действующим сроком',
                    'message' => $e->getMessage(),
                ];
            } catch (\Exception $e) {
                $failedIngredients[] = [
                    'product_id' => $ingredient['product_id'],
                    'reason' => 'Ошибка',
                    'message' => $e->getMessage(),
                ];
            }
        }

        // Запись в отчёт по кухне
        $this->recordMealPreparation($kitchenId, $mealType, $mealDate, $results, $failedIngredients);

        return [
            'success' => $results,
            'failed' => $failedIngredients,
            'total_success' => count($results),
            'total_failed' => count($failedIngredients),
        ];
    }

    /**
     * Пример 2: Проверка доступности продуктов для меню
     */
    public function checkMenuAvailability(
        int $kitchenId,
        array $menuItems // [['product_id' => 1, 'quantity' => 500], ...]
    ): array {
        $availability = [];

        foreach ($menuItems as $item) {
            $product = InventoryItemModel::find($item['product_id']);
            if (!$product) {
                $availability[] = [
                    'product_id' => $item['product_id'],
                    'available' => false,
                    'reason' => 'Продукт не найден',
                ];
                continue;
            }

            $domainProduct = $product->toDomain();

            // Жёсткая проверка срока годности
            if ($domainProduct->isExpired()) {
                $availability[] = [
                    'product_id' => $item['product_id'],
                    'product_name' => $product->name,
                    'available' => false,
                    'reason' => 'Просрочен',
                    'expiry_date' => $domainProduct->expiryDate?->format('Y-m-d'),
                ];
                continue;
            }

            // Проверка доступного количества
            $availableStock = InventoryBatchModel::where('inventory_item_id', $product->id)
                ->usable()
                ->sum('current_quantity');

            $availability[] = [
                'product_id' => $item['product_id'],
                'product_name' => $product->name,
                'available' => $availableStock >= $item['quantity'],
                'required' => $item['quantity'],
                'available_stock' => $availableStock,
                'warning' => $domainProduct->isExpiringSoon(7) ? 'Истекает через ' . $domainProduct->getDaysUntilExpiry() . ' дней' : null,
            ];
        }

        return $availability;
    }

    /**
     * Пример 3: Ежедневный отчёт по кухне
     */
    public function generateDailyKitchenReport(
        int $kitchenId,
        string $reportDate
    ): array {
        // Получение всех списаний за день
        // В реальной реализации нужна таблица для хранения FIFO-движений

        $expiringSoonProducts = InventoryBatchModel::whereHas('inventoryItem', function ($query) use ($kitchenId) {
            $query->where('category', 'kitchen_product');
        })
            ->expiringSoon(7)
            ->with('inventoryItem')
            ->get();

        $expiredProducts = InventoryBatchModel::whereHas('inventoryItem', function ($query) use ($kitchenId) {
            $query->where('category', 'kitchen_product');
        })
            ->expired()
            ->with('inventoryItem')
            ->get();

        return [
            'kitchen_id' => $kitchenId,
            'report_date' => $reportDate,
            'expiring_soon' => $expiringSoonProducts->map(fn ($batch) => [
                'product_name' => $batch->inventoryItem->name,
                'batch_number' => $batch->batch_number,
                'expiry_date' => $batch->expiry_date->format('Y-m-d'),
                'days_left' => $batch->getDaysUntilExpiry(),
                'quantity' => $batch->current_quantity,
            ])->toArray(),
            'expired' => $expiredProducts->map(fn ($batch) => [
                'product_name' => $batch->inventoryItem->name,
                'batch_number' => $batch->batch_number,
                'expiry_date' => $batch->expiry_date->format('Y-m-d'),
                'quantity' => $batch->current_quantity,
                'days_expired' => abs($batch->getDaysUntilExpiry()),
            ])->toArray(),
            'total_expiring_soon' => $expiringSoonProducts->count(),
            'total_expired' => $expiredProducts->count(),
        ];
    }

    /**
     * Пример 4: Поступление продуктов на кухню
     */
    public function receiveKitchenProducts(
        int $kitchenId,
        array $shipments // [['product_id' => 1, 'batch_number' => '...', 'quantity' => 10, ...], ...]
    ): array {
        $results = [];

        foreach ($shipments as $shipment) {
            try {
                $item = InventoryItemModel::find($shipment['product_id']);
                if (!$item) {
                    $results[] = [
                        'product_id' => $shipment['product_id'],
                        'status' => 'error',
                        'message' => 'Продукт не найден',
                    ];
                    continue;
                }

                // Создание партии
                $batch = InventoryBatchModel::create([
                    'inventory_item_id' => $item->id,
                    'tenant_id' => $item->tenant_id,
                    'batch_number' => $shipment['batch_number'],
                    'manufacture_date' => $shipment['manufacture_date'] ?? now()->subDays(7)->format('Y-m-d'),
                    'expiry_date' => $shipment['expiry_date'],
                    'initial_quantity' => $shipment['quantity'],
                    'current_quantity' => $shipment['quantity'],
                    'purchase_price' => $shipment['purchase_price'] ?? 0,
                    'storage_location' => $shipment['storage_location'] ?? "Кухня {$kitchenId}",
                    'status' => 'active',
                ]);

                // Обновление общего количества
                $item->increment('quantity', $shipment['quantity']);

                // Проверка срока годности при поступлении
                $warning = null;
                if ($batch->isExpiringSoon(7)) {
                    $warning = "Критически короткий срок: {$batch->getDaysUntilExpiry()} дней";
                    $this->notifyAboutCriticalExpiry($batch, $kitchenId);
                } elseif ($batch->isExpiringSoon(14)) {
                    $warning = "Короткий срок: {$batch->getDaysUntilExpiry()} дней";
                }

                $results[] = [
                    'product_id' => $shipment['product_id'],
                    'product_name' => $item->name,
                    'batch_number' => $batch->batch_number,
                    'quantity' => $shipment['quantity'],
                    'expiry_date' => $batch->expiry_date->format('Y-m-d'),
                    'status' => 'success',
                    'warning' => $warning,
                ];

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
     * Пример 5: Списание продуктов для стационара (индивидуальное питание)
     */
    public function deductForHospitalPatient(
        int $patientId,
        int $kitchenId,
        array $prescribedDiet,
        string $mealDate
    ): array {
        $results = [];

        foreach ($prescribedDiet as $dietItem) {
            try {
                $item = InventoryItemModel::find($dietItem['product_id']);
                if (!$item) {
                    $results[] = [
                        'product_id' => $dietItem['product_id'],
                        'status' => 'error',
                        'message' => 'Продукт не найден',
                    ];
                    continue;
                }

                $domainItem = $item->toDomain();

                // Жёсткая проверка - для пациентов только свежие продукты
                if ($domainItem->isExpired()) {
                    throw new \RuntimeException('Просроченный продукт нельзя использовать для пациента');
                }

                if ($domainItem->isExpiringSoon(3)) {
                    throw new \RuntimeException('Продукт слишком близок к просрочке для пациента');
                }

                $result = $this->fifoService->autoDeduct(
                    $domainItem,
                    $dietItem['quantity'],
                    'kitchen',
                    [
                        'kitchen_id' => $kitchenId,
                        'patient_id' => $patientId,
                        'meal_date' => $mealDate,
                        'diet_type' => $dietItem['diet_type'] ?? 'regular',
                    ]
                );

                $results[] = [
                    'product_id' => $dietItem['product_id'],
                    'product_name' => $item->name,
                    'quantity' => $dietItem['quantity'],
                    'batch_number' => $result->deductedBatches[0]['batch_number'],
                    'status' => 'success',
                ];

                // Запись в медицинскую карту пациента
                $this->recordPatientMeal($patientId, $item->name, $dietItem['quantity'], $mealDate);

            } catch (\Exception $e) {
                $results[] = [
                    'product_id' => $dietItem['product_id'],
                    'status' => 'error',
                    'message' => $e->getMessage(),
                ];
            }
        }

        return $results;
    }

    /**
     * Пример 6: Отчёт по отходам (просроченные продукты)
     */
    public function generateWasteReport(
        int $kitchenId,
        string $startDate,
        string $endDate
    ): array {
        $expiredBatches = InventoryBatchModel::whereHas('inventoryItem', function ($query) use ($kitchenId) {
            $query->where('category', 'kitchen_product');
        })
            ->where('status', 'expired')
            ->whereBetween('updated_at', [$startDate, $endDate])
            ->with('inventoryItem')
            ->get();

        $totalWasteValue = $expiredBatches->sum(function ($batch) {
            return $batch->initial_quantity * $batch->purchase_price;
        });

        return [
            'kitchen_id' => $kitchenId,
            'period' => [
                'start' => $startDate,
                'end' => $endDate,
            ],
            'waste_items' => $expiredBatches->map(fn ($batch) => [
                'product_name' => $batch->inventoryItem->name,
                'batch_number' => $batch->batch_number,
                'quantity' => $batch->initial_quantity,
                'purchase_price' => $batch->purchase_price,
                'total_value' => $batch->initial_quantity * $batch->purchase_price,
                'expiry_date' => $batch->expiry_date->format('Y-m-d'),
            ])->toArray(),
            'total_waste_value' => $totalWasteValue,
            'total_items' => $expiredBatches->count(),
        ];
    }

    private function recordMealPreparation(
        int $kitchenId,
        string $mealType,
        string $mealDate,
        array $success,
        array $failed
    ): void {
        \Illuminate\Support\Facades\Log::info('Подготовка питания на кухне', [
            'kitchen_id' => $kitchenId,
            'meal_type' => $mealType,
            'meal_date' => $mealDate,
            'success_count' => count($success),
            'failed_count' => count($failed),
            'failed_items' => $failed,
        ]);
    }

    private function recordPatientMeal(
        int $patientId,
        string $productName,
        int $quantity,
        string $mealDate
    ): void {
        // Интеграция с модулем стационара
        \Illuminate\Support\Facades\Log::info('Запись питания пациента', [
            'patient_id' => $patientId,
            'product_name' => $productName,
            'quantity' => $quantity,
            'meal_date' => $mealDate,
        ]);
    }

    private function notifyAboutCriticalExpiry(InventoryBatchModel $batch, int $kitchenId): void
    {
        \Illuminate\Support\Facades\Log::critical('КРИТИЧЕСКИ: Продукт с очень коротким сроком на кухне', [
            'kitchen_id' => $kitchenId,
            'batch_number' => $batch->batch_number,
            'expiry_date' => $batch->expiry_date->format('Y-m-d'),
            'days_left' => $batch->getDaysUntilExpiry(),
            'quantity' => $batch->current_quantity,
        ]);
    }
}
