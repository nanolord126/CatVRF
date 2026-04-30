<?php

declare(strict_types=1);

namespace Modules\Inventory\Application\Examples;

use Modules\Inventory\Application\Services\FIFOShelfLifeService;
use Modules\Inventory\Domain\Exceptions\InsufficientStockWithExpiryException;
use Modules\Inventory\Domain\Exceptions\ShelfLifeException;
use Modules\Inventory\Infrastructure\Models\InventoryBatchModel;
use Modules\Inventory\Infrastructure\Models\InventoryItemModel;

/**
 * Детальный пример интеграции FIFO системы с косметикой и grooming-продуктами
 * 
 * Специфика косметики:
 * - Шампуни (разные типы: для короткой шерсти, длинной, чувствительной кожи)
 * - Кондиционеры и масла
 * - Красители и осветлители
 * - Стайлинговые средства
 * - Уходовые средства (лаки, спреи, порошки)
 * - Одноразовые материалы (колпачки, перчатки)
 */
final class DetailedCosmeticsIntegrationExample
{
    private FIFOShelfLifeService $fifoService;

    public function __construct(FIFOShelfLifeService $fifoService)
    {
        $this->fifoService = $fifoService;
    }

    /**
     * Пример 1: Использование шампуня с учётом типа шерсти
     */
    public function useShampooByCoatType(
        int $shampooItemId,
        string $coatType, // short, long, sensitive, curly
        int $petSize, // small, medium, large
        int $groomerId,
        int $appointmentId
    ): array {
        $item = InventoryItemModel::find($shampooItemId);
        if (!$item) {
            throw new \InvalidArgumentException('Шампунь не найден');
        }

        $domainItem = $item->toDomain();

        // Расчёт количества в зависимости от размера животного
        $quantityMap = [
            'small' => 50,   // мл
            'medium' => 100,  // мл
            'large' => 200,   // мл
        ];

        $quantity = $quantityMap[$petSize] ?? 100;

        // Проверка срока годности (минимум 90 дней для шампуней)
        if ($domainItem->getDaysUntilExpiry() !== null && $domainItem->getDaysUntilExpiry() < 90) {
            \Illuminate\Support\Facades\Log::warning('Шампунь с коротким сроком', [
                'shampoo_id' => $shampooItemId,
                'days_left' => $domainItem->getDaysUntilExpiry(),
                'coat_type' => $coatType,
            ]);
        }

        $result = $this->fifoService->autoDeduct(
            $domainItem,
            $quantity,
            'grooming',
            [
                'groomer_id' => $groomerId,
                'appointment_id' => $appointmentId,
                'coat_type' => $coatType,
                'pet_size' => $petSize,
                'product_type' => 'shampoo',
            ]
        );

        return [
            'shampoo_name' => $item->name,
            'coat_type' => $coatType,
            'pet_size' => $petSize,
            'quantity_used_ml' => $quantity,
            'batch_number' => $result->deductedBatches[0]['batch_number'],
            'expiry_date' => $result->deductedBatches[0]['expiry_date'],
        ];
    }

    /**
     * Пример 2: Использование красителя с трассировкой партии
     */
    public function useHairDye(
        int $dyeItemId,
        int $quantityMl,
        string $colorCode,
        int $groomerId,
        int $appointmentId
    ): array {
        $item = InventoryItemModel::find($dyeItemId);
        if (!$item) {
            throw new \InvalidArgumentException('Краситель не найден');
        }

        $domainItem = $item->toDomain();

        // Строгий контроль для красителей - минимум 180 дней (6 месяцев)
        if ($domainItem->getDaysUntilExpiry() !== null && $domainItem->getDaysUntilExpiry() < 180) {
            throw new \RuntimeException(
                'Краситель с сроком менее 6 месяцев не может быть использован'
            );
        }

        $result = $this->fifoService->autoDeduct(
            $domainItem,
            $quantityMl,
            'grooming',
            [
                'groomer_id' => $groomerId,
                'appointment_id' => $appointmentId,
                'color_code' => $colorCode,
                'product_type' => 'hair_dye',
            ]
        );

        // Запись для трассировки цвета
        $this->recordColorUsage(
            $appointmentId,
            $colorCode,
            $result->deductedBatches[0]['batch_number'],
            $quantityMl
        );

        return [
            'dye_name' => $item->name,
            'color_code' => $colorCode,
            'quantity_ml' => $quantityMl,
            'batch_number' => $result->deductedBatches[0]['batch_number'],
            'expiry_date' => $result->deductedBatches[0]['expiry_date'],
        ];
    }

    /**
     * Пример 3: Использование стайлингового средства (лак, спрей)
     */
    public function useStylingProduct(
        int $productId,
        int $quantity,
        string $productType, // hairspray, gel, powder, mousse
        int $groomerId,
        int $appointmentId
    ): array {
        $item = InventoryItemModel::find($productId);
        if (!$item) {
            throw new \InvalidArgumentException('Стайлинговое средство не найдено');
        }

        $domainItem = $item->toDomain();

        // Мягкий контроль для стайлинга - минимум 30 дней
        if ($domainItem->isExpired()) {
            throw new \RuntimeException('Стайлинговое средство просрочено');
        }

        $result = $this->fifoService->autoDeduct(
            $domainItem,
            $quantity,
            'grooming',
            [
                'groomer_id' => $groomerId,
                'appointment_id' => $appointmentId,
                'styling_type' => $productType,
            ]
        );

        return [
            'product_name' => $item->name,
            'styling_type' => $productType,
            'quantity' => $quantity,
            'batch_number' => $result->deductedBatches[0]['batch_number'],
        ];
    }

    /**
     * Пример 4: Полный комплект для услуги груминга
     */
    public function deductFullGroomingPackage(
        int $groomerId,
        int $appointmentId,
        array $petData // ['coat_type' => 'long', 'size' => 'medium', 'services' => ['bath', 'haircut', 'styling']]
    ): array {
        $packageComponents = $this->getPackageComponents($petData);
        $results = [];

        foreach ($packageComponents as $component) {
            try {
                $result = $this->fifoService->autoDeduct(
                    $component['domain_item'],
                    $component['quantity'],
                    'grooming',
                    [
                        'groomer_id' => $groomerId,
                        'appointment_id' => $appointmentId,
                        'service' => $component['service'],
                        'product_type' => $component['type'],
                    ]
                );

                $results[] = [
                    'product_name' => $component['name'],
                    'service' => $component['service'],
                    'quantity' => $component['quantity'],
                    'batch_number' => $result->deductedBatches[0]['batch_number'],
                    'status' => 'success',
                ];

            } catch (\Exception $e) {
                $results[] = [
                    'product_name' => $component['name'],
                    'service' => $component['service'],
                    'status' => 'error',
                    'message' => $e->getMessage(),
                ];
            }
        }

        return [
            'appointment_id' => $appointmentId,
            'components' => $results,
            'success_count' => collect($results)->where('status', 'success')->count(),
            'error_count' => collect($results)->where('status', 'error')->count(),
        ];
    }

    /**
     * Пример 5: Контроль партий красителей (строгий учёт)
     */
    public function checkDyeBatchAvailability(
        int $dyeItemId,
        string $colorCode,
        int $requiredQuantity
    ): array {
        $item = InventoryItemModel::find($dyeItemId);
        if (!$item) {
            return ['available' => false, 'reason' => 'Краситель не найден'];
        }

        $domainItem = $item->toDomain();

        // Строгая проверка срока - минимум 180 дней
        if ($domainItem->getDaysUntilExpiry() !== null && $domainItem->getDaysUntilExpiry() < 180) {
            return [
                'available' => false,
                'reason' => 'Срок менее 6 месяцев',
                'days_left' => $domainItem->getDaysUntilExpiry(),
            ];
        }

        $availableBatches = InventoryBatchModel::where('inventory_item_id', $item->id)
            ->usable()
            ->where('expiry_date', '>=', now()->addDays(180))
            ->orderBy('expiry_date', 'asc')
            ->get();

        $totalAvailable = $availableBatches->sum('current_quantity');

        if ($totalAvailable < $requiredQuantity) {
            return [
                'available' => false,
                'reason' => 'Недостаточно красителя с нужным сроком',
                'required' => $requiredQuantity,
                'available' => $totalAvailable,
                'batches' => $availableBatches->map(fn ($b) => [
                    'batch_number' => $b->batch_number,
                    'quantity' => $b->current_quantity,
                    'expiry_date' => $b->expiry_date->format('Y-m-d'),
                ])->toArray(),
            ];
        }

        return [
            'available' => true,
            'total_available' => $totalAvailable,
            'next_batch' => $availableBatches->first()?->batch_number,
            'batches_count' => $availableBatches->count(),
        ];
    }

    /**
     * Пример 6: Отчёт по использованию косметики за период
     */
    public function getCosmeticsUsageReport(
        int $groomerId,
        string $startDate,
        string $endDate
    ): array {
        return [
            'groomer_id' => $groomerId,
            'period' => ['start' => $startDate, 'end' => $endDate],
            'products_by_type' => [
                'shampoos' => [
                    'total_quantity_ml' => 5000,
                    'batches_used' => 3,
                    'appointments' => 25,
                ],
                'conditioners' => [
                    'total_quantity_ml' => 2500,
                    'batches_used' => 2,
                    'appointments' => 20,
                ],
                'hair_dyes' => [
                    'total_quantity_ml' => 300,
                    'batches_used' => 1,
                    'appointments' => 5,
                    'colors_used' => ['#FF5733', '#33FF57', '#3357FF'],
                ],
                'styling_products' => [
                    'total_quantity' => 50,
                    'batches_used' => 4,
                    'appointments' => 30,
                ],
            ],
        ];
    }

    /**
     * Пример 7: Предупреждение о низком остатке косметики
     */
    public function checkCosmeticsStockLevels(int $groomingLocationId): array
    {
        $lowStockItems = InventoryItemModel::where('category', 'grooming_product')
            ->where('quantity', '<=', \Illuminate\Support\Facades\DB::raw('min_stock_level'))
            ->get();

        $alerts = [];

        foreach ($lowStockItems as $item) {
            $domainItem = $item->toDomain();
            $alerts[] = [
                'product_name' => $item->name,
                'current_quantity' => $item->quantity,
                'min_level' => $item->min_stock_level,
                'unit' => $item->unit,
                'is_expiring_soon' => $domainItem->isExpiringSoon(30),
                'days_until_expiry' => $domainItem->getDaysUntilExpiry(),
            ];
        }

        return [
            'location_id' => $groomingLocationId,
            'low_stock_count' => count($alerts),
            'alerts' => $alerts,
        ];
    }

    private function getPackageComponents(array $petData): array
    {
        // В реальной реализации загрузка из конфигурации или БД
        $components = [];

        // Шампунь
        $shampoo = InventoryItemModel::where('category', 'grooming_product')
            ->where('name', 'like', '%шампунь%')
            ->first();

        if ($shampoo) {
            $quantityMap = ['small' => 50, 'medium' => 100, 'large' => 200];
            $components[] = [
                'name' => $shampoo->name,
                'domain_item' => $shampoo->toDomain(),
                'quantity' => $quantityMap[$petData['size']] ?? 100,
                'service' => 'bath',
                'type' => 'shampoo',
            ];
        }

        // Кондиционер
        $conditioner = InventoryItemModel::where('category', 'grooming_product')
            ->where('name', 'like', '%кондиционер%')
            ->first();

        if ($conditioner && in_array('bath', $petData['services'])) {
            $components[] = [
                'name' => $conditioner->name,
                'domain_item' => $conditioner->toDomain(),
                'quantity' => 50,
                'service' => 'bath',
                'type' => 'conditioner',
            ];
        }

        return $components;
    }

    private function recordColorUsage(
        int $appointmentId,
        string $colorCode,
        string $batchNumber,
        int $quantityMl
    ): void {
        \Illuminate\Support\Facades\Log::info('Использование красителя', [
            'appointment_id' => $appointmentId,
            'color_code' => $colorCode,
            'batch_number' => $batchNumber,
            'quantity_ml' => $quantityMl,
        ]);
    }
}
