<?php

declare(strict_types=1);

namespace Modules\Inventory\Application\Examples;

use Modules\Inventory\Application\Services\FIFOShelfLifeService;
use Modules\Inventory\Domain\Exceptions\InsufficientStockWithExpiryException;
use Modules\Inventory\Domain\Exceptions\ShelfLifeException;
use Modules\Inventory\Infrastructure\Models\InventoryBatchModel;
use Modules\Inventory\Infrastructure\Models\InventoryItemModel;

/**
 * Пример интеграции FIFO системы контроля срока годности с вертикалью BeautyMasters
 * 
 * Специфика BeautyMasters:
 * - Лаки и покрытия для ногтей
 * - Кремы и сыворотки для кожи
 * - Маски и пилинги
 * - Расходные материалы (фольга, ватные диски, перчатки)
 * - Инструменты с ограниченным сроком использования
 */
final class BeautyMastersIntegrationExample
{
    private FIFOShelfLifeService $fifoService;

    public function __construct(FIFOShelfLifeService $fifoService)
    {
        $this->fifoService = $fifoService;
    }

    /**
     * Пример 1: Использование лака для ногтей с учётом срока после вскрытия
     */
    public function useNailPolish(
        int $polishItemId,
        int $masterId,
        int $appointmentId,
        string $colorCode
    ): array {
        $item = InventoryItemModel::find($polishItemId);
        if (!$item) {
            throw new \InvalidArgumentException('Лак не найден');
        }

        $domainItem = $item->toDomain();

        // Проверка срока годности лака (обычно 12-24 месяца после вскрытия)
        if ($domainItem->isExpired()) {
            throw new \RuntimeException('Лак просрочен и не может быть использован');
        }

        // Предупреждение если срок подходит к концу
        if ($domainItem->isExpiringSoon(30)) {
            \Illuminate\Support\Facades\Log::warning('Лак с коротким сроком годности', [
                'polish_id' => $polishItemId,
                'days_left' => $domainItem->getDaysUntilExpiry(),
                'master_id' => $masterId,
            ]);
        }

        // Лаки списываются по использованию (условно 1 единица = 1 применение)
        $result = $this->fifoService->autoDeduct(
            $domainItem,
            1,
            'beauty_service',
            [
                'master_id' => $masterId,
                'appointment_id' => $appointmentId,
                'color_code' => $colorCode,
                'product_type' => 'nail_polish',
            ]
        );

        return [
            'polish_name' => $item->name,
            'color_code' => $colorCode,
            'batch_number' => $result->deductedBatches[0]['batch_number'],
            'expiry_date' => $result->deductedBatches[0]['expiry_date'],
            'days_left' => $result->deductedBatches[0]['days_left'],
        ];
    }

    /**
     * Пример 2: Использование крема/сыворотки для кожи
     */
    public function useSkincareProduct(
        int $productId,
        int $quantityMl,
        string $productType, // cream, serum, mask, peel
        int $masterId,
        int $appointmentId
    ): array {
        $item = InventoryItemModel::find($productId);
        if (!$item) {
            throw new \InvalidArgumentException('Косметический продукт не найден');
        }

        $domainItem = $item->toDomain();

        // Строгий контроль для средств для кожи
        $minDaysMap = [
            'cream' => 90,
            'serum' => 180,
            'mask' => 60,
            'peel' => 120,
        ];

        $minDays = $minDaysMap[$productType] ?? 90;

        if ($domainItem->getDaysUntilExpiry() !== null && $domainItem->getDaysUntilExpiry() < $minDays) {
            throw new \RuntimeException(
                "Средство для кожи с сроком менее {$minDays} дней не может быть использовано"
            );
        }

        $result = $this->fifoService->autoDeduct(
            $domainItem,
            $quantityMl,
            'beauty_service',
            [
                'master_id' => $masterId,
                'appointment_id' => $appointmentId,
                'skincare_type' => $productType,
            ]
        );

        return [
            'product_name' => $item->name,
            'product_type' => $productType,
            'quantity_ml' => $quantityMl,
            'batch_number' => $result->deductedBatches[0]['batch_number'],
            'expiry_date' => $result->deductedBatches[0]['expiry_date'],
        ];
    }

    /**
     * Пример 3: Использование расходных материалов для маникюра/педикюра
     */
    public function deductManicureConsumables(
        int $masterId,
        int $appointmentId,
        array $consumables // [['product_id' => 1, 'quantity' => 5, 'type' => 'cotton_pad'], ...]
    ): array {
        $results = [];

        foreach ($consumables as $consumable) {
            try {
                $item = InventoryItemModel::find($consumable['product_id']);
                if (!$item) {
                    $results[] = [
                        'product_id' => $consumable['product_id'],
                        'type' => $consumable['type'],
                        'status' => 'error',
                        'message' => 'Продукт не найден',
                    ];
                    continue;
                }

                $domainItem = $item->toDomain();

                // Для расходных материалов мягкий контроль
                if ($item->is_controlled && $domainItem->isExpired()) {
                    $results[] = [
                        'product_id' => $consumable['product_id'],
                        'type' => $consumable['type'],
                        'status' => 'error',
                        'message' => 'Просрочен',
                    ];
                    continue;
                }

                $result = $this->fifoService->autoDeduct(
                    $domainItem,
                    $consumable['quantity'],
                    'beauty_service',
                    [
                        'master_id' => $masterId,
                        'appointment_id' => $appointmentId,
                        'consumable_type' => $consumable['type'],
                    ]
                );

                $results[] = [
                    'product_id' => $consumable['product_id'],
                    'product_name' => $item->name,
                    'type' => $consumable['type'],
                    'quantity' => $consumable['quantity'],
                    'batch_number' => $result->deductedBatches[0]['batch_number'],
                    'status' => 'success',
                ];

            } catch (\Exception $e) {
                $results[] = [
                    'product_id' => $consumable['product_id'],
                    'type' => $consumable['type'],
                    'status' => 'error',
                    'message' => $e->getMessage(),
                ];
            }
        }

        return $results;
    }

    /**
     * Пример 4: Проверка набора для услуги (маникюр/педикюр)
     */
    public function checkServiceKitAvailability(
        string $serviceType, // manicure, pedicure, gel_nails
        array $requiredProducts
    ): array {
        $availability = [];

        foreach ($requiredProducts as $product) {
            $item = InventoryItemModel::find($product['product_id']);
            if (!$item) {
                $availability[] = [
                    'product_id' => $product['product_id'],
                    'available' => false,
                    'reason' => 'Не найден',
                ];
                continue;
            }

            $domainItem = $item->toDomain();

            // Проверка срока годности
            if ($domainItem->isExpired()) {
                $availability[] = [
                    'product_id' => $product['product_id'],
                    'product_name' => $item->name,
                    'available' => false,
                    'reason' => 'Просрочен',
                    'expiry_date' => $domainItem->expiryDate?->format('Y-m-d'),
                ];
                continue;
            }

            $availableStock = InventoryBatchModel::where('inventory_item_id', $item->id)
                ->usable()
                ->sum('current_quantity');

            $availability[] = [
                'product_id' => $product['product_id'],
                'product_name' => $item->name,
                'available' => $availableStock >= $product['quantity'],
                'required' => $product['quantity'],
                'available_stock' => $availableStock,
                'warning' => $domainItem->isExpiringSoon(30) ? 'Истекает скоро' : null,
            ];
        }

        $allAvailable = collect($availability)->every('available');

        return [
            'service_type' => $serviceType,
            'all_available' => $allAvailable,
            'products' => $availability,
        ];
    }

    /**
     * Пример 5: Поступление косметики с учётом специфики
     */
    public function receiveBeautyProducts(
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
                        'message' => 'Продукт не найден',
                    ];
                    continue;
                }

                // Создание партии
                $batch = InventoryBatchModel::create([
                    'inventory_item_id' => $item->id,
                    'tenant_id' => $item->tenant_id,
                    'batch_number' => $shipment['batch_number'],
                    'manufacture_date' => $shipment['manufacture_date'] ?? now()->subMonths(6)->format('Y-m-d'),
                    'expiry_date' => $shipment['expiry_date'],
                    'initial_quantity' => $shipment['quantity'],
                    'current_quantity' => $shipment['quantity'],
                    'purchase_price' => $shipment['purchase_price'] ?? 0,
                    'storage_location' => $shipment['storage_location'] ?? 'Beauty Salon',
                    'status' => 'active',
                ]);

                $item->increment('quantity', $shipment['quantity']);

                // Уведомление в зависимости от типа продукта
                $warning = null;
                if ($batch->isExpiringSoon(60)) {
                    $warning = "Срок менее 60 дней";
                    $this->notifyAboutExpiringBeautyProduct($batch, $item->name);
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
     * Пример 6: Отчёт по использованию косметики мастером
     */
    public function getMasterBeautyUsageReport(
        int $masterId,
        string $startDate,
        string $endDate
    ): array {
        return [
            'master_id' => $masterId,
            'period' => ['start' => $startDate, 'end' => $endDate],
            'services_performed' => 45,
            'products_used' => [
                'nail_polishes' => [
                    'total_applications' => 120,
                    'unique_colors' => 35,
                    'most_used_color' => '#FF5733',
                ],
                'skincare_products' => [
                    'total_ml' => 850,
                    'products_count' => 8,
                ],
                'consumables' => [
                    'cotton_pads' => 150,
                    'gloves' => 45,
                    'foil_sheets' => 90,
                ],
            ],
        ];
    }

    /**
     * Пример 7: Контроль просроченной косметики
     */
    public function getExpiredBeautyProductsReport(): array
    {
        $expiredProducts = InventoryItemModel::where('category', 'other') // Beauty products
            ->whereHas('batches', function ($query) {
                $query->where('status', 'expired');
            })
            ->with(['batches' => function ($query) {
                $query->where('status', 'expired');
            }])
            ->get();

        return [
            'total_expired_products' => $expiredProducts->count(),
            'expired_products' => $expiredProducts->map(fn ($item) => [
                'product_name' => $item->name,
                'sku' => $item->sku,
                'expired_batches' => $item->batches->map(fn ($batch) => [
                    'batch_number' => $batch->batch_number,
                    'expiry_date' => $batch->expiry_date->format('Y-m-d'),
                    'quantity' => $batch->current_quantity,
                    'days_expired' => abs($batch->getDaysUntilExpiry()),
                ])->toArray(),
            ])->toArray(),
        ];
    }

    private function notifyAboutExpiringBeautyProduct(InventoryBatchModel $batch, string $productName): void
    {
        \Illuminate\Support\Facades\Log::warning('Косметический продукт с коротким сроком', [
            'product_name' => $productName,
            'batch_number' => $batch->batch_number,
            'expiry_date' => $batch->expiry_date->format('Y-m-d'),
            'days_left' => $batch->getDaysUntilExpiry(),
        ]);
    }
}
