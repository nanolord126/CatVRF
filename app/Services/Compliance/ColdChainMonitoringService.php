<?php

declare(strict_types=1);

namespace App\Services\Compliance;

use Illuminate\Database\DatabaseManager;
use Illuminate\Support\Facades\DB;
use Psr\Log\LoggerInterface;
use Carbon\Carbon;

/**
 * Cold Chain Monitoring Service - ФЗ-323 Compliance
 *
 * Implements temperature monitoring for pharmaceutical products:
 * - Real-time temperature tracking
 * - Automatic alerts for violations
 * - Compliance reporting for regulators
 * - Integration with IoT sensors
 *
 * @author CatVRF Team
 * @version 2026.04.28
 */
final readonly class ColdChainMonitoringService
{
    private const DEFAULT_MIN_TEMP = 2.0; // °C
    private const DEFAULT_MAX_TEMP = 25.0; // °C
    private const DEFAULT_HUMIDITY_MIN = 30.0; // %
    private const DEFAULT_HUMIDITY_MAX = 75.0; // %
    private const ALERT_THRESHOLD_MINUTES = 15; // Alert after 15 minutes of violation

    public function __construct(
        private readonly DatabaseManager $db,
        private readonly LoggerInterface $logger,
    ) {}

    /**
     * Record temperature reading from IoT sensor
     *
     * @param  int  $warehouseId  Warehouse ID
     * @param  int  $zoneId  Zone ID
     * @param  float  $temperature  Temperature in Celsius
     * @param  float|null  $humidity  Humidity percentage
     * @param  string  $sensorId  Sensor identifier
     * @param  int  $tenantId  Tenant ID
     * @return string Reading ID
     */
    public function recordTemperatureReading(
        int $warehouseId,
        int $zoneId,
        float $temperature,
        ?float $humidity,
        string $sensorId,
        int $tenantId
    ): string {
        $readingId = (string) \Illuminate\Support\Str::uuid();

        $this->db->table('cold_chain_readings')->insert([
            'id' => $readingId,
            'warehouse_id' => $warehouseId,
            'zone_id' => $zoneId,
            'temperature' => $temperature,
            'humidity' => $humidity,
            'sensor_id' => $sensorId,
            'tenant_id' => $tenantId,
            'recorded_at' => now(),
            'created_at' => now(),
        ]);

        // Check for violations and create alerts if needed
        $this->checkForViolations($warehouseId, $zoneId, $temperature, $humidity, $tenantId);

        return $readingId;
    }

    /**
     * Get temperature requirements for product category
     *
     * @param  string  $productCategory  Product category
     * @return array Temperature requirements
     */
    public function getTemperatureRequirements(string $productCategory): array
    {
        $category = strtolower($productCategory);

        return match (true) {
            str_contains($category, 'vaccine') => [
                'min_temp' => 2.0,
                'max_temp' => 8.0,
                'humidity_min' => 35.0,
                'humidity_max' => 65.0,
                'critical' => true,
            ],
            str_contains($category, 'insulin') => [
                'min_temp' => 2.0,
                'max_temp' => 8.0,
                'humidity_min' => 35.0,
                'humidity_max' => 65.0,
                'critical' => true,
            ],
            str_contains($category, 'antibiotic') => [
                'min_temp' => 8.0,
                'max_temp' => 25.0,
                'humidity_min' => 30.0,
                'humidity_max' => 75.0,
                'critical' => true,
            ],
            str_contains($category, 'refrigerated') => [
                'min_temp' => 2.0,
                'max_temp' => 8.0,
                'humidity_min' => 30.0,
                'humidity_max' => 75.0,
                'critical' => false,
            ],
            default => [
                'min_temp' => self::DEFAULT_MIN_TEMP,
                'max_temp' => self::DEFAULT_MAX_TEMP,
                'humidity_min' => self::DEFAULT_HUMIDITY_MIN,
                'humidity_max' => self::DEFAULT_HUMIDITY_MAX,
                'critical' => false,
            ],
        };
    }

    /**
     * Check for temperature/humidity violations and create alerts
     *
     * @param  int  $warehouseId  Warehouse ID
     * @param  int  $zoneId  Zone ID
     * @param  float  $temperature  Temperature in Celsius
     * @param  float|null  $humidity  Humidity percentage
     * @param  int  $tenantId  Tenant ID
     * @return void
     */
    private function checkForViolations(
        int $warehouseId,
        int $zoneId,
        float $temperature,
        ?float $humidity,
        int $tenantId
    ): void {
        // Get zone requirements
        $zone = $this->db->table('warehouse_zones')
            ->where('id', $zoneId)
            ->where('warehouse_id', $warehouseId)
            ->first();

        if (! $zone) {
            return;
        }

        $minTemp = $zone->min_temp ?? self::DEFAULT_MIN_TEMP;
        $maxTemp = $zone->max_temp ?? self::DEFAULT_MAX_TEMP;
        $minHumidity = $zone->min_humidity ?? self::DEFAULT_HUMIDITY_MIN;
        $maxHumidity = $zone->max_humidity ?? self::DEFAULT_HUMIDITY_MAX;

        $violations = [];

        // Check temperature
        if ($temperature < $minTemp || $temperature > $maxTemp) {
            $violations[] = [
                'type' => 'temperature',
                'value' => $temperature,
                'min' => $minTemp,
                'max' => $maxTemp,
            ];
        }

        // Check humidity if provided
        if ($humidity !== null) {
            if ($humidity < $minHumidity || $humidity > $maxHumidity) {
                $violations[] = [
                    'type' => 'humidity',
                    'value' => $humidity,
                    'min' => $minHumidity,
                    'max' => $maxHumidity,
                ];
            }
        }

        if (empty($violations)) {
            // Clear any existing active alerts for this zone
            $this->clearActiveAlerts($warehouseId, $zoneId);
            return;
        }

        // Check if there's already an active alert for this zone
        $existingAlert = $this->db->table('cold_chain_alerts')
            ->where('warehouse_id', $warehouseId)
            ->where('zone_id', $zoneId)
            ->where('status', 'active')
            ->first();

        if ($existingAlert) {
            // Update existing alert with new readings
            $this->updateExistingAlert($existingAlert->id, $temperature, $humidity, $violations);
        } else {
            // Create new alert
            $this->createAlert($warehouseId, $zoneId, $temperature, $humidity, $violations, $tenantId);
        }
    }

    /**
     * Create new cold chain alert
     *
     * @param  int  $warehouseId  Warehouse ID
     * @param  int  $zoneId  Zone ID
     * @param  float  $temperature  Temperature
     * @param  float|null  $humidity  Humidity
     * @param  array  $violations  Violations detected
     * @param  int  $tenantId  Tenant ID
     * @return void
     */
    private function createAlert(
        int $warehouseId,
        int $zoneId,
        float $temperature,
        ?float $humidity,
        array $violations,
        int $tenantId
    ): void {
        $alertId = (string) \Illuminate\Support\Str::uuid();

        $this->db->table('cold_chain_alerts')->insert([
            'id' => $alertId,
            'warehouse_id' => $warehouseId,
            'zone_id' => $zoneId,
            'alert_type' => $violations[0]['type'],
            'current_temperature' => $temperature,
            'current_humidity' => $humidity,
            'violations' => json_encode($violations),
            'status' => 'active',
            'severity' => $this->determineSeverity($violations),
            'notified' => false,
            'tenant_id' => $tenantId,
            'started_at' => now(),
            'created_at' => now(),
        ]);

        $this->logger->warning('Cold chain alert created', [
            'alert_id' => $alertId,
            'warehouse_id' => $warehouseId,
            'zone_id' => $zoneId,
            'violations' => $violations,
        ]);
    }

    /**
     * Update existing alert with new readings
     *
     * @param  string  $alertId  Alert ID
     * @param  float  $temperature  Temperature
     * @param  float|null  $humidity  Humidity
     * @param  array  $violations  Violations detected
     * @return void
     */
    private function updateExistingAlert(
        string $alertId,
        float $temperature,
        ?float $humidity,
        array $violations
    ): void {
        $this->db->table('cold_chain_alerts')
            ->where('id', $alertId)
            ->update([
                'current_temperature' => $temperature,
                'current_humidity' => $humidity,
                'violations' => json_encode($violations),
                'updated_at' => now(),
            ]);

        // Check if alert should be escalated based on duration
        $alert = $this->db->table('cold_chain_alerts')
            ->where('id', $alertId)
            ->first();

        if ($alert && $alert->started_at) {
            $minutesSinceStart = Carbon::parse($alert->started_at)->diffInMinutes(now());

            if ($minutesSinceStart >= self::ALERT_THRESHOLD_MINUTES && ! $alert->notified) {
                $this->escalateAlert($alertId);
            }
        }
    }

    /**
     * Clear active alerts for zone
     *
     * @param  int  $warehouseId  Warehouse ID
     * @param  int  $zoneId  Zone ID
     * @return void
     */
    private function clearActiveAlerts(int $warehouseId, int $zoneId): void
    {
        $this->db->table('cold_chain_alerts')
            ->where('warehouse_id', $warehouseId)
            ->where('zone_id', $zoneId)
            ->where('status', 'active')
            ->update([
                'status' => 'resolved',
                'resolved_at' => now(),
                'updated_at' => now(),
            ]);
    }

    /**
     * Determine alert severity based on violations
     *
     * @param  array  $violations  Violations detected
     * @return string Severity level
     */
    private function determineSeverity(array $violations): string
    {
        foreach ($violations as $violation) {
            if ($violation['type'] === 'temperature') {
                $diff = max(
                    abs($violation['value'] - $violation['min']),
                    abs($violation['value'] - $violation['max'])
                );

                if ($diff > 10.0) {
                    return 'critical';
                }

                if ($diff > 5.0) {
                    return 'high';
                }
            }
        }

        return 'medium';
    }

    /**
     * Escalate alert (send notifications)
     *
     * @param  string  $alertId  Alert ID
     * @return void
     */
    private function escalateAlert(string $alertId): void
    {
        $this->db->table('cold_chain_alerts')
            ->where('id', $alertId)
            ->update([
                'notified' => true,
                'notified_at' => now(),
                'updated_at' => now(),
            ]);

        $this->logger->alert('Cold chain alert escalated', [
            'alert_id' => $alertId,
        ]);

        // TODO: Send notifications (SMS, email, push)
        // TODO: Integration with notification service
    }

    /**
     * Get cold chain compliance report for period
     *
     * @param  int  $warehouseId  Warehouse ID
     * @param  Carbon  $startDate  Start date
     * @param  Carbon  $endDate  End date
     * @return array Compliance report
     */
    public function getComplianceReport(
        int $warehouseId,
        Carbon $startDate,
        Carbon $endDate
    ): array {
        $readings = $this->db->table('cold_chain_readings')
            ->where('warehouse_id', $warehouseId)
            ->whereBetween('recorded_at', [$startDate, $endDate])
            ->orderBy('recorded_at')
            ->get();

        $alerts = $this->db->table('cold_chain_alerts')
            ->where('warehouse_id', $warehouseId)
            ->whereBetween('started_at', [$startDate, $endDate])
            ->get();

        $totalReadings = $readings->count();
        $totalAlerts = $alerts->count();
        $criticalAlerts = $alerts->where('severity', 'critical')->count();
        $resolvedAlerts = $alerts->where('status', 'resolved')->count();

        $avgTemperature = $readings->avg('temperature');
        $minTemperature = $readings->min('temperature');
        $maxTemperature = $readings->max('temperature');

        return [
            'warehouse_id' => $warehouseId,
            'period' => [
                'start' => $startDate->format('Y-m-d H:i:s'),
                'end' => $endDate->format('Y-m-d H:i:s'),
            ],
            'readings' => [
                'total' => $totalReadings,
                'avg_temperature' => round($avgTemperature, 2),
                'min_temperature' => $minTemperature,
                'max_temperature' => $maxTemperature,
            ],
            'alerts' => [
                'total' => $totalAlerts,
                'critical' => $criticalAlerts,
                'resolved' => $resolvedAlerts,
                'active' => $totalAlerts - $resolvedAlerts,
            ],
            'compliance_rate' => $totalReadings > 0
                ? round((($totalReadings - $totalAlerts) / $totalReadings) * 100, 2)
                : 100.0,
        ];
    }

    /**
     * Get active alerts for warehouse
     *
     * @param  int  $warehouseId  Warehouse ID
     * @return array Active alerts
     */
    public function getActiveAlerts(int $warehouseId): array
    {
        return $this->db->table('cold_chain_alerts')
            ->where('warehouse_id', $warehouseId)
            ->where('status', 'active')
            ->orderBy('started_at', 'desc')
            ->get()
            ->toArray();
    }

    /**
     * Manually resolve alert
     *
     * @param  string  $alertId  Alert ID
     * @param  int  $resolvedBy  User ID resolving
     * @param  string  $resolutionNotes  Resolution notes
     * @return bool
     */
    public function resolveAlert(string $alertId, int $resolvedBy, string $resolutionNotes): bool
    {
        return $this->db->table('cold_chain_alerts')
            ->where('id', $alertId)
            ->update([
                'status' => 'resolved',
                'resolved_at' => now(),
                'resolved_by' => $resolvedBy,
                'resolution_notes' => $resolutionNotes,
                'updated_at' => now(),
            ]) > 0;
    }
}
