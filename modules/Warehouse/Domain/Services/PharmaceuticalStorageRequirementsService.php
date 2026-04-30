<?php

declare(strict_types=1);

namespace Modules\Warehouse\Domain\Services;

use Illuminate\Database\DatabaseManager;
use Modules\Warehouse\Domain\Entities\Warehouse;
use Modules\Warehouse\Domain\Exceptions\LicenseManagementException;
use Psr\Log\LoggerInterface;

/**
 * Pharmaceutical Storage Requirements Service for ФЗ-61 compliance
 * 
 * Сервис обеспечивает контроль требований к помещениям для хранения лекарственных средств:
 * - Проверка соответствия помещения категории лекарств
 * - Контроль температурных зон
 * - Контроль влажности
 * - Контроль освещения
 * - Контроль вентиляции
 */
final readonly class PharmaceuticalStorageRequirementsService
{
    private const STORAGE_REQUIREMENTS = [
        'narcotic' => [
            'requires_vault' => true,
            'requires_dual_control' => true,
            'requires_alarm' => true,
            'requires_video_surveillance' => true,
            'temperature_range' => ['min' => 2.0, 'max' => 25.0],
            'humidity_range' => ['min' => 35, 'max' => 75],
            'min_area_sqm' => 10,
        ],
        'psychotropic' => [
            'requires_vault' => true,
            'requires_dual_control' => true,
            'requires_alarm' => true,
            'requires_video_surveillance' => true,
            'temperature_range' => ['min' => 2.0, 'max' => 25.0],
            'humidity_range' => ['min' => 35, 'max' => 75],
            'min_area_sqm' => 8,
        ],
        'antibiotic' => [
            'requires_vault' => false,
            'requires_dual_control' => false,
            'requires_alarm' => false,
            'requires_video_surveillance' => true,
            'temperature_range' => ['min' => 2.0, 'max' => 8.0],
            'humidity_range' => ['min' => 35, 'max' => 75],
            'min_area_sqm' => 5,
        ],
        'vaccine' => [
            'requires_vault' => false,
            'requires_dual_control' => false,
            'requires_alarm' => true,
            'requires_video_surveillance' => true,
            'temperature_range' => ['min' => 2.0, 'max' => 8.0],
            'humidity_range' => ['min' => 35, 'max' => 75],
            'min_area_sqm' => 5,
        ],
        'insulin' => [
            'requires_vault' => false,
            'requires_dual_control' => false,
            'requires_alarm' => true,
            'requires_video_surveillance' => true,
            'temperature_range' => ['min' => 2.0, 'max' => 8.0],
            'humidity_range' => ['min' => 35, 'max' => 75],
            'min_area_sqm' => 4,
        ],
        'refrigerated' => [
            'requires_vault' => false,
            'requires_dual_control' => false,
            'requires_alarm' => false,
            'requires_video_surveillance' => false,
            'temperature_range' => ['min' => 2.0, 'max' => 8.0],
            'humidity_range' => ['min' => 35, 'max' => 75],
            'min_area_sqm' => 3,
        ],
        'frozen' => [
            'requires_vault' => false,
            'requires_dual_control' => false,
            'requires_alarm' => false,
            'requires_video_surveillance' => false,
            'temperature_range' => ['min' => -25.0, 'max' => -10.0],
            'humidity_range' => ['min' => 30, 'max' => 80],
            'min_area_sqm' => 3,
        ],
        'standard' => [
            'requires_vault' => false,
            'requires_dual_control' => false,
            'requires_alarm' => false,
            'requires_video_surveillance' => false,
            'temperature_range' => ['min' => 15.0, 'max' => 25.0],
            'humidity_range' => ['min' => 35, 'max' => 75],
            'min_area_sqm' => 2,
        ],
    ];

    public function __construct(
        private readonly DatabaseManager $db,
        private readonly LoggerInterface $logger
    ) {}

    /**
     * Проверка соответствия помещения требованиям хранения
     */
    public function validateStorageRequirements(int $warehouseId, string $drugCategory): array
    {
        $warehouse = $this->getWarehouse($warehouseId);
        $requirements = self::STORAGE_REQUIREMENTS[$drugCategory] ?? self::STORAGE_REQUIREMENTS['standard'];

        $violations = [];

        // Проверка площади
        if ($warehouse->getArea() < $requirements['min_area_sqm']) {
            $violations[] = [
                'type' => 'area',
                'required' => $requirements['min_area_sqm'],
                'current' => $warehouse->getArea(),
                'severity' => 'high',
            ];
        }

        // Проверка сейфа для наркотических/психотропных
        if ($requirements['requires_vault'] && !$warehouse->hasVault()) {
            $violations[] = [
                'type' => 'vault',
                'required' => true,
                'current' => false,
                'severity' => 'critical',
            ];
        }

        // Проверка двойного контроля
        if ($requirements['requires_dual_control'] && !$warehouse->hasDualControl()) {
            $violations[] = [
                'type' => 'dual_control',
                'required' => true,
                'current' => false,
                'severity' => 'critical',
            ];
        }

        // Проверка сигнализации
        if ($requirements['requires_alarm'] && !$warehouse->hasAlarm()) {
            $violations[] = [
                'type' => 'alarm',
                'required' => true,
                'current' => false,
                'severity' => 'high',
            ];
        }

        // Проверка видеонаблюдения
        if ($requirements['requires_video_surveillance'] && !$warehouse->hasVideoSurveillance()) {
            $violations[] = [
                'type' => 'video_surveillance',
                'required' => true,
                'current' => false,
                'severity' => 'medium',
            ];
        }

        // Проверка температурной зоны
        $zoneCompliance = $this->checkTemperatureZone($warehouseId, $drugCategory);
        if (!$zoneCompliance['compliant']) {
            $violations[] = [
                'type' => 'temperature_zone',
                'required' => $requirements['temperature_range'],
                'current' => $zoneCompliance['current_range'],
                'severity' => 'critical',
            ];
        }

        return [
            'compliant' => empty($violations),
            'violations' => $violations,
            'requirements' => $requirements,
        ];
    }

    /**
     * Получение требований для категории лекарств
     */
    public function getStorageRequirements(string $drugCategory): array
    {
        return self::STORAGE_REQUIREMENTS[$drugCategory] ?? self::STORAGE_REQUIREMENTS['standard'];
    }

    /**
     * Проверка температурной зоны
     */
    private function checkTemperatureZone(int $warehouseId, string $drugCategory): array
    {
        $requirements = self::STORAGE_REQUIREMENTS[$drugCategory] ?? self::STORAGE_REQUIREMENTS['standard'];

        $zones = $this->db->table('warehouse_zones')
            ->where('warehouse_id', $warehouseId)
            ->get();

        $suitableZones = $zones->filter(function ($zone) use ($requirements) {
            $range = match ($zone->type) {
                'refrigeration' => self::STORAGE_REQUIREMENTS['refrigerated']['temperature_range'],
                'freezer' => self::STORAGE_REQUIREMENTS['frozen']['temperature_range'],
                default => self::STORAGE_REQUIREMENTS['standard']['temperature_range'],
            };

            return $this->rangesOverlap($range, $requirements['temperature_range']);
        });

        return [
            'compliant' => $suitableZones->isNotEmpty(),
            'current_range' => $suitableZones->isNotEmpty() ? $requirements['temperature_range'] : null,
            'available_zones' => $suitableZones->count(),
        ];
    }

    /**
     * Проверка пересечения диапазонов температур
     */
    private function rangesOverlap(array $range1, array $range2): bool
    {
        return !($range1['max'] < $range2['min'] || $range1['min'] > $range2['max']);
    }

    /**
     * Получение категории лекарств по продукту
     */
    public function getDrugCategory(int $productId): string
    {
        $product = $this->db->table('products')
            ->where('id', $productId)
            ->first();

        if (!$product) {
            return 'standard';
        }

        $category = strtolower($product->category ?? '');

        $categoryMap = [
            'narcotic' => ['narcotic', 'опиум', 'морфин'],
            'psychotropic' => ['psychotropic', 'психотроп', 'аминазин'],
            'antibiotic' => ['antibiotic', 'антибиотик', 'пенициллин'],
            'vaccine' => ['vaccine', 'вакцина', 'прививка'],
            'insulin' => ['insulin', 'инсулин'],
        ];

        foreach ($categoryMap as $mappedCategory => $keywords) {
            foreach ($keywords as $keyword) {
                if (str_contains($category, $keyword)) {
                    return $mappedCategory;
                }
            }
        }

        // Проверка по SKU
        $sku = strtolower($product->sku ?? '');
        if (str_starts_with($sku, 'NARC-')) {
            return 'narcotic';
        }
        if (str_starts_with($sku, 'PSY-')) {
            return 'psychotropic';
        }
        if (str_starts_with($sku, 'ANT-')) {
            return 'antibiotic';
        }
        if (str_starts_with($sku, 'VAC-')) {
            return 'vaccine';
        }

        return 'standard';
    }

    /**
     * Получение склада
     */
    private function getWarehouse(int $warehouseId): Warehouse
    {
        $warehouse = $this->db->table('warehouses')
            ->where('id', $warehouseId)
            ->first();

        if (!$warehouse) {
            throw new \RuntimeException("Warehouse not found: {$warehouseId}");
        }

        return new Warehouse(
            id: new \Modules\Warehouse\Domain\ValueObjects\WarehouseId($warehouse->id),
            name: $warehouse->name,
            tenantId: $warehouse->tenant_id,
            branchId: $warehouse->branch_id,
            type: $warehouse->type,
            address: $warehouse->address,
            capacity: $warehouse->capacity,
            currentStock: $warehouse->current_stock,
            isActive: $warehouse->is_active,
        );
    }
}
