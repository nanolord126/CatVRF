<?php

declare(strict_types=1);

namespace App\Services\Inventory;

use App\Services\Security\AuditService;
use App\Traits\WithAuditLogging;
use Illuminate\Database\DatabaseManager;
use Illuminate\Cache\Repository as CacheRepository;
use Psr\Log\LoggerInterface;

/**
 * IoT Sensor Service
 *
 * Manages IoT sensors for warehouse monitoring:
 * - Temperature and humidity sensors
 * - Real-time data collection
 * - Threshold-based alerts
 * - Sensor health monitoring
 * - Historical data analysis
 *
 * @author CatVRF Team
 * @version 2026.04.28
 */
final readonly class IoTSensorService
{
    use WithAuditLogging;

    public function __construct(
        private readonly DatabaseManager $db,
        private readonly LoggerInterface $logger,
        private readonly AuditService $auditService,
        private readonly CacheRepository $cache
    ) {}

    /**
     * Register IoT sensor
     *
     * @param  string  $sensorId  Sensor ID
     * @param  string  $sensorType  Sensor type (temperature, humidity, motion)
     * @param  string  $location  Location
     * @param  int  $warehouseId  Warehouse ID
     * @param  int  $tenantId  Tenant ID
     * @param  int  $userId  User registering
     * @return string Sensor record ID
     */
    public function registerSensor(
        string $sensorId,
        string $sensorType,
        string $location,
        int $warehouseId,
        int $tenantId,
        int $userId
    ): string {
        $recordId = (string) \Illuminate\Support\Str::uuid();

        $this->db->table('iot_sensors')->insert([
            'id' => $recordId,
            'sensor_id' => $sensorId,
            'sensor_type' => $sensorType,
            'location' => $location,
            'warehouse_id' => $warehouseId,
            'tenant_id' => $tenantId,
            'status' => 'active',
            'registered_at' => now(),
            'registered_by' => $userId,
            'created_at' => now(),
        ]);

        $this->logAction(
            action: 'iot_sensor_registered',
            entityType: 'IoTSensor',
            entityId: $recordId,
            context: [
                'sensor_id' => $sensorId,
                'sensor_type' => $sensorType,
                'location' => $location,
            ],
            userId: $userId,
            tenantId: $tenantId
        );

        return $recordId;
    }

    /**
     * Process sensor reading
     *
     * @param  string  $sensorId  Sensor ID
     * @param  float  $value  Sensor value
     * @param  string  $unit  Unit of measurement
     * @param  int  $tenantId  Tenant ID
     * @return string Reading ID
     */
    public function processSensorReading(
        string $sensorId,
        float $value,
        string $unit,
        int $tenantId
    ): string {
        $readingId = (string) \Illuminate\Support\Str::uuid();

        $sensor = $this->db->table('iot_sensors')
            ->where('sensor_id', $sensorId)
            ->first();

        if (! $sensor) {
            $this->logger->warning('Unknown sensor reading', [
                'sensor_id' => $sensorId,
                'value' => $value,
            ]);

            $this->db->table('iot_unknown_readings')->insert([
                'id' => $readingId,
                'sensor_id' => $sensorId,
                'value' => $value,
                'unit' => $unit,
                'tenant_id' => $tenantId,
                'read_at' => now(),
            ]);

            return $readingId;
        }

        $this->db->table('iot_sensor_readings')->insert([
            'id' => $readingId,
            'sensor_record_id' => $sensor->id,
            'sensor_id' => $sensorId,
            'value' => $value,
            'unit' => $unit,
            'tenant_id' => $tenantId,
            'read_at' => now(),
        ]);

        $this->db->table('iot_sensors')
            ->where('id', $sensor->id)
            ->update([
                'last_reading_at' => now(),
                'last_value' => $value,
            ]);

        $this->checkThresholds($sensor, $value);

        $this->cache->tags(['iot', "sensor:{$sensor->id}"])->flush();

        return $readingId;
    }

    /**
     * Set sensor thresholds
     *
     * @param  string  $sensorId  Sensor ID
     * @param  float  $minThreshold  Minimum threshold
     * @param  float  $maxThreshold  Maximum threshold
     * @param  int  $userId  User setting thresholds
     * @return bool
     */
    public function setSensorThresholds(
        string $sensorId,
        float $minThreshold,
        float $maxThreshold,
        int $userId
    ): bool {
        $sensor = $this->db->table('iot_sensors')
            ->where('sensor_id', $sensorId)
            ->first();

        if (! $sensor) {
            throw new \RuntimeException("Sensor not found: {$sensorId}");
        }

        $this->db->table('iot_sensors')
            ->where('id', $sensor->id)
            ->update([
                'min_threshold' => $minThreshold,
                'max_threshold' => $maxThreshold,
                'updated_at' => now(),
            ]);

        $this->logAction(
            action: 'iot_sensor_thresholds_set',
            entityType: 'IoTSensor',
            entityId: $sensor->id,
            context: [
                'sensor_id' => $sensorId,
                'min_threshold' => $minThreshold,
                'max_threshold' => $maxThreshold,
            ],
            userId: $userId,
            tenantId: $sensor->tenant_id
        );

        return true;
    }

    /**
     * Get sensor readings
     *
     * @param  string  $sensorId  Sensor ID
     * @param  int  $hours  Hours of data to retrieve
     * @return array Sensor readings
     */
    public function getSensorReadings(string $sensorId, int $hours = 24): array
    {
        $sensor = $this->db->table('iot_sensors')
            ->where('sensor_id', $sensorId)
            ->first();

        if (! $sensor) {
            throw new \RuntimeException("Sensor not found: {$sensorId}");
        }

        $readings = $this->db->table('iot_sensor_readings')
            ->where('sensor_record_id', $sensor->id)
            ->where('read_at', '>=', now()->subHours($hours))
            ->orderBy('read_at', 'desc')
            ->get()
            ->toArray();

        return [
            'sensor_id' => $sensorId,
            'sensor_type' => $sensor->sensor_type,
            'location' => $sensor->location,
            'period_hours' => $hours,
            'total_readings' => count($readings),
            'readings' => $readings,
        ];
    }

    /**
     * Get sensor statistics
     *
     * @param  string  $sensorId  Sensor ID
     * @param  int  $hours  Hours of data to analyze
     * @return array Statistics
     */
    public function getSensorStatistics(string $sensorId, int $hours = 24): array
    {
        $sensor = $this->db->table('iot_sensors')
            ->where('sensor_id', $sensorId)
            ->first();

        if (! $sensor) {
            throw new \RuntimeException("Sensor not found: {$sensorId}");
        }

        $readings = $this->db->table('iot_sensor_readings')
            ->where('sensor_record_id', $sensor->id)
            ->where('read_at', '>=', now()->subHours($hours))
            ->pluck('value')
            ->toArray();

        if (empty($readings)) {
            return [
                'sensor_id' => $sensorId,
                'period_hours' => $hours,
                'message' => 'No readings available',
            ];
        }

        $average = array_sum($readings) / count($readings);
        $min = min($readings);
        $max = max($readings);
        sort($readings);
        $median = $readings[floor(count($readings) / 2)];

        $variance = array_sum(array_map(function ($x) use ($average) {
            return pow($x - $average, 2);
        }, $readings)) / count($readings);

        $stdDev = sqrt($variance);

        return [
            'sensor_id' => $sensorId,
            'sensor_type' => $sensor->sensor_type,
            'period_hours' => $hours,
            'statistics' => [
                'average' => $average,
                'min' => $min,
                'max' => $max,
                'median' => $median,
                'std_dev' => $stdDev,
                'count' => count($readings),
            ],
        ];
    }

    /**
     * Get warehouse sensor overview
     *
     * @param  int  $warehouseId  Warehouse ID
     * @return array Sensor overview
     */
    public function getWarehouseSensorOverview(int $warehouseId): array
    {
        $sensors = $this->db->table('iot_sensors')
            ->where('warehouse_id', $warehouseId)
            ->get();

        $overview = [
            'total_sensors' => $sensors->count(),
            'active_sensors' => $sensors->where('status', 'active')->count(),
            'inactive_sensors' => $sensors->where('status', '!=', 'active')->count(),
            'by_type' => [],
            'by_location' => [],
        ];

        foreach ($sensors as $sensor) {
            $type = $sensor->sensor_type;
            $location = $sensor->location;

            if (! isset($overview['by_type'][$type])) {
                $overview['by_type'][$type] = 0;
            }
            $overview['by_type'][$type]++;

            if (! isset($overview['by_location'][$location])) {
                $overview['by_location'][$location] = 0;
            }
            $overview['by_location'][$location]++;
        }

        return $overview;
    }

    /**
     * Check thresholds and trigger alerts
     *
     * @param  mixed  $sensor  Sensor record
     * @param  float  $value  Current value
     * @return void
     */
    private function checkThresholds($sensor, float $value): void
    {
        $alertTriggered = false;
        $alertType = null;
        $message = null;

        if ($sensor->min_threshold !== null && $value < $sensor->min_threshold) {
            $alertTriggered = true;
            $alertType = 'below_threshold';
            $message = "Value {$value} below minimum threshold {$sensor->min_threshold}";
        } elseif ($sensor->max_threshold !== null && $value > $sensor->max_threshold) {
            $alertTriggered = true;
            $alertType = 'above_threshold';
            $message = "Value {$value} above maximum threshold {$sensor->max_threshold}";
        }

        if ($alertTriggered) {
            $this->db->table('iot_sensor_alerts')->insert([
                'id' => (string) \Illuminate\Support\Str::uuid(),
                'sensor_record_id' => $sensor->id,
                'sensor_id' => $sensor->sensor_id,
                'alert_type' => $alertType,
                'value' => $value,
                'threshold' => $alertType === 'below_threshold' ? $sensor->min_threshold : $sensor->max_threshold,
                'message' => $message,
                'resolved' => false,
                'created_at' => now(),
            ]);

            $this->logger->warning('IoT sensor threshold alert', [
                'sensor_id' => $sensor->sensor_id,
                'alert_type' => $alertType,
                'value' => $value,
                'message' => $message,
            ]);
        }
    }

    /**
     * Get sensor alerts
     *
     * @param  string  $sensorId  Sensor ID
     * @param  int  $hours  Hours of alerts to retrieve
     * @return array Alerts
     */
    public function getSensorAlerts(string $sensorId, int $hours = 24): array
    {
        $sensor = $this->db->table('iot_sensors')
            ->where('sensor_id', $sensorId)
            ->first();

        if (! $sensor) {
            throw new \RuntimeException("Sensor not found: {$sensorId}");
        }

        $alerts = $this->db->table('iot_sensor_alerts')
            ->where('sensor_record_id', $sensor->id)
            ->where('created_at', '>=', now()->subHours($hours))
            ->orderBy('created_at', 'desc')
            ->get()
            ->toArray();

        return [
            'sensor_id' => $sensorId,
            'period_hours' => $hours,
            'total_alerts' => count($alerts),
            'unresolved_alerts' => count(array_filter($alerts, fn ($a) => ! $a['resolved'])),
            'alerts' => $alerts,
        ];
    }

    /**
     * Resolve sensor alert
     *
     * @param  string  $alertId  Alert ID
     * @param  string  $resolution  Resolution notes
     * @param  int  $userId  User resolving
     * @return bool
     */
    public function resolveSensorAlert(string $alertId, string $resolution, int $userId): bool
    {
        $alert = $this->db->table('iot_sensor_alerts')
            ->where('id', $alertId)
            ->first();

        if (! $alert) {
            throw new \RuntimeException("Alert not found: {$alertId}");
        }

        $this->db->table('iot_sensor_alerts')
            ->where('id', $alertId)
            ->update([
                'resolved' => true,
                'resolution' => $resolution,
                'resolved_by' => $userId,
                'resolved_at' => now(),
            ]);

        $this->logAction(
            action: 'iot_sensor_alert_resolved',
            entityType: 'IoTSensorAlert',
            entityId: $alertId,
            context: [
                'sensor_id' => $alert->sensor_id,
                'resolution' => $resolution,
            ],
            userId: $userId,
            tenantId: 0
        );

        return true;
    }

    /**
     * Deactivate sensor
     *
     * @param  string  $sensorId  Sensor ID
     * @param  string  $reason  Deactivation reason
     * @param  int  $userId  User deactivating
     * @return bool
     */
    public function deactivateSensor(string $sensorId, string $reason, int $userId): bool
    {
        $sensor = $this->db->table('iot_sensors')
            ->where('sensor_id', $sensorId)
            ->first();

        if (! $sensor) {
            throw new \RuntimeException("Sensor not found: {$sensorId}");
        }

        $this->db->table('iot_sensors')
            ->where('id', $sensor->id)
            ->update([
                'status' => 'inactive',
                'deactivation_reason' => $reason,
                'deactivated_at' => now(),
                'deactivated_by' => $userId,
            ]);

        $this->logAction(
            action: 'iot_sensor_deactivated',
            entityType: 'IoTSensor',
            entityId: $sensor->id,
            context: [
                'sensor_id' => $sensorId,
                'reason' => $reason,
            ],
            userId: $userId,
            tenantId: $sensor->tenant_id
        );

        return true;
    }

    /**
     * Get temperature and humidity report for zone
     *
     * @param  string  $zone  Zone identifier
     * @param  int  $warehouseId  Warehouse ID
     * @param  int  $hours  Hours of data
     * @return array Zone report
     */
    public function getZoneEnvironmentReport(string $zone, int $warehouseId, int $hours = 24): array
    {
        $sensors = $this->db->table('iot_sensors')
            ->where('warehouse_id', $warehouseId)
            ->where('location', 'like', "%{$zone}%")
            ->get();

        $temperatureSensors = $sensors->where('sensor_type', 'temperature');
        $humiditySensors = $sensors->where('sensor_type', 'humidity');

        $temperatureData = [];
        $humidityData = [];

        foreach ($temperatureSensors as $sensor) {
            $stats = $this->getSensorStatistics($sensor->sensor_id, $hours);
            $temperatureData[] = [
                'sensor_id' => $sensor->sensor_id,
                'location' => $sensor->location,
                'statistics' => $stats['statistics'] ?? null,
            ];
        }

        foreach ($humiditySensors as $sensor) {
            $stats = $this->getSensorStatistics($sensor->sensor_id, $hours);
            $humidityData[] = [
                'sensor_id' => $sensor->sensor_id,
                'location' => $sensor->location,
                'statistics' => $stats['statistics'] ?? null,
            ];
        }

        return [
            'zone' => $zone,
            'warehouse_id' => $warehouseId,
            'period_hours' => $hours,
            'temperature_sensors' => count($temperatureData),
            'humidity_sensors' => count($humidityData),
            'temperature_data' => $temperatureData,
            'humidity_data' => $humidityData,
        ];
    }
}
