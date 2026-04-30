<?php

declare(strict_types=1);

namespace Modules\Warehouse\Domain\Services;

use Illuminate\Database\DatabaseManager;
use Modules\Warehouse\Domain\Exceptions\LicenseManagementException;
use Psr\Log\LoggerInterface;

/**
 * Cold Chain Monitoring Service for ФЗ-323 compliance
 * 
 * Сервис обеспечивает мониторинг температурного режима для лекарственных средств:
 * - Проверка текущей температуры
 * - Логирование нарушений
 * - Алерты при выходе за пределы допустимого диапазона
 * - Контроль влажности
 */
final readonly class ColdChainMonitoringService
{
    private const TEMPERATURE_RANGES = [
        'standard' => ['min' => 2.0, 'max' => 25.0],
        'refrigerated' => ['min' => 2.0, 'max' => 8.0],
        'frozen' => ['min' => -25.0, 'max' => -10.0],
        'room_temperature' => ['min' => 15.0, 'max' => 25.0],
    ];

    private const HUMIDITY_RANGE = ['min' => 35, 'max' => 75];

    public function __construct(
        private readonly DatabaseManager $db,
        private readonly LoggerInterface $logger
    ) {}

    /**
     * Проверка температурного режима для продукта
     */
    public function checkTemperature(
        int $productId,
        float $currentTemperature,
        ?float $humidity = null,
        ?int $warehouseId = null
    ): array {
        $product = $this->getProduct($productId);
        $storageCondition = $product->getStorageCondition() ?? 'standard';
        
        $range = self::TEMPERATURE_RANGES[$storageCondition] ?? self::TEMPERATURE_RANGES['standard'];
        
        $violations = [];

        // Проверка температуры
        if ($currentTemperature < $range['min'] || $currentTemperature > $range['max']) {
            $violations[] = [
                'type' => 'temperature',
                'current' => $currentTemperature,
                'min' => $range['min'],
                'max' => $range['max'],
                'severity' => $this->calculateSeverity($currentTemperature, $range),
            ];

            $this->logger->error('Temperature violation detected', [
                'product_id' => $productId,
                'product_sku' => $product->getSku(),
                'current_temperature' => $currentTemperature,
                'allowed_range' => $range,
                'warehouse_id' => $warehouseId,
            ]);

            // Логирование нарушения в audit trail
            $this->logTemperatureViolation($productId, $currentTemperature, $range, $warehouseId);
        }

        // Проверка влажности (если требуется)
        if ($humidity !== null && $product->requiresHumidityControl()) {
            if ($humidity < self::HUMIDITY_RANGE['min'] || $humidity > self::HUMIDITY_RANGE['max']) {
                $violations[] = [
                    'type' => 'humidity',
                    'current' => $humidity,
                    'min' => self::HUMIDITY_RANGE['min'],
                    'max' => self::HUMIDITY_RANGE['max'],
                    'severity' => 'warning',
                ];

                $this->logger->warning('Humidity violation detected', [
                    'product_id' => $productId,
                    'current_humidity' => $humidity,
                    'allowed_range' => self::HUMIDITY_RANGE,
                ]);
            }
        }

        return [
            'compliant' => empty($violations),
            'violations' => $violations,
            'storage_condition' => $storageCondition,
            'temperature_range' => $range,
        ];
    }

    /**
     * Запись температурных показаний
     */
    public function recordTemperatureReading(
        int $warehouseId,
        int $zoneId,
        float $temperature,
        ?float $humidity = null,
        ?string $sensorId = null
    ): string {
        $readingId = (string) \Illuminate\Support\Str::uuid();

        $this->db->table('cold_chain_readings')->insert([
            'id' => $readingId,
            'warehouse_id' => $warehouseId,
            'zone_id' => $zoneId,
            'temperature' => $temperature,
            'humidity' => $humidity,
            'sensor_id' => $sensorId,
            'recorded_at' => now(),
            'created_at' => now(),
        ]);

        // Проверка на нарушение
        $this->checkZoneTemperature($zoneId, $temperature, $humidity);

        return $readingId;
    }

    /**
     * Проверка температурного режима зоны
     */
    private function checkZoneTemperature(int $zoneId, float $temperature, ?float $humidity): void
    {
        $zone = $this->db->table('warehouse_zones')
            ->where('id', $zoneId)
            ->first();

        if (!$zone) {
            return;
        }

        $range = match ($zone->type) {
            'refrigeration' => self::TEMPERATURE_RANGES['refrigerated'],
            'freezer' => self::TEMPERATURE_RANGES['frozen'],
            default => self::TEMPERATURE_RANGES['standard'],
        };

        if ($temperature < $range['min'] || $temperature > $range['max']) {
            $this->logger->error('Zone temperature violation', [
                'zone_id' => $zoneId,
                'zone_type' => $zone->type,
                'current_temperature' => $temperature,
                'allowed_range' => $range,
            ]);

            // Создание алерта
            $this->createTemperatureAlert($zoneId, $temperature, $range);
        }
    }

    /**
     * Создание алерта о нарушении температуры
     */
    private function createTemperatureAlert(int $zoneId, float $temperature, array $range): void
    {
        $this->db->table('temperature_alerts')->insert([
            'id' => (string) \Illuminate\Support\Str::uuid(),
            'zone_id' => $zoneId,
            'temperature' => $temperature,
            'min_allowed' => $range['min'],
            'max_allowed' => $range['max'],
            'severity' => $this->calculateSeverity($temperature, $range),
            'resolved_at' => null,
            'created_at' => now(),
        ]);
    }

    /**
     * Получение статистики температурного режима за период
     */
    public function getTemperatureStatistics(
        int $warehouseId,
        \DateTimeImmutable $startDate,
        \DateTimeImmutable $endDate
    ): array {
        $readings = $this->db->table('cold_chain_readings')
            ->where('warehouse_id', $warehouseId)
            ->whereBetween('recorded_at', [$startDate->format('Y-m-d H:i:s'), $endDate->format('Y-m-d H:i:s')])
            ->get();

        if ($readings->isEmpty()) {
            return [
                'total_readings' => 0,
                'average_temperature' => null,
                'min_temperature' => null,
                'max_temperature' => null,
                'violations_count' => 0,
            ];
        }

        $temperatures = $readings->pluck('temperature')->toArray();
        $violations = $this->db->table('temperature_alerts')
            ->whereBetween('created_at', [$startDate->format('Y-m-d H:i:s'), $endDate->format('Y-m-d H:i:s')])
            ->count();

        return [
            'total_readings' => $readings->count(),
            'average_temperature' => array_sum($temperatures) / count($temperatures),
            'min_temperature' => min($temperatures),
            'max_temperature' => max($temperatures),
            'violations_count' => $violations,
        ];
    }

    /**
     * Расчет степени серьезности нарушения
     */
    private function calculateSeverity(float $current, array $range): string
    {
        $deviation = 0;

        if ($current < $range['min']) {
            $deviation = $range['min'] - $current;
        } elseif ($current > $range['max']) {
            $deviation = $current - $range['max'];
        }

        return match (true) {
            $deviation >= 10 => 'critical',
            $deviation >= 5 => 'high',
            $deviation >= 2 => 'medium',
            default => 'low',
        };
    }

    /**
     * Логирование нарушения температуры
     */
    private function logTemperatureViolation(int $productId, float $current, array $range, ?int $warehouseId): void
    {
        $this->db->table('temperature_violations')->insert([
            'id' => (string) \Illuminate\Support\Str::uuid(),
            'product_id' => $productId,
            'warehouse_id' => $warehouseId,
            'current_temperature' => $current,
            'min_allowed' => $range['min'],
            'max_allowed' => $range['max'],
            'severity' => $this->calculateSeverity($current, $range),
            'resolved_at' => null,
            'created_at' => now(),
        ]);
    }

    /**
     * Получение продукта
     *
     * @return object{getSku(): string, getStorageCondition(): ?string, requiresHumidityControl(): bool}
     */
    private function getProduct(int $productId): object
    {
        $product = $this->db->table('products')
            ->where('id', $productId)
            ->first();

        if (!$product) {
            throw new \RuntimeException("Product not found: {$productId}");
        }

        // Anonymous value object for product cold-chain data
        return new class($product) {
            public function __construct(private readonly object $data) {}
            public function getSku(): string { return $this->data->sku ?? ''; }
            public function getStorageCondition(): ?string { return $this->data->storage_condition ?? null; }
            public function requiresHumidityControl(): bool { return $this->data->requires_humidity_control ?? false; }
        };
    }
}
