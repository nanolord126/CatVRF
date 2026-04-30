<?php

declare(strict_types=1);

namespace Modules\Supermarket\Application\Services;

use App\Traits\WithAuditLogging;
use App\Traits\WithTelemetry;
use App\Services\FraudControlService;
use Modules\Supermarket\Domain\Models\TemperatureMonitoringDevice;
use Modules\Supermarket\Domain\Models\TemperatureReading;
use Modules\Supermarket\Domain\Models\ProductTemperatureRequirement;
use Modules\Supermarket\Application\Services\TemperatureCRMIntegrationService;
use Modules\Supermarket\Domain\Events\TemperatureViolationDetected;
use Modules\Supermarket\Domain\Events\TemperatureReadingReceived;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

/**
 * TemperatureMonitoringService — IoT device integration for temperature monitoring
 * 
 * Handles device registration, telemetry ingestion, MQTT/WebSocket integration,
 * and violation detection for supermarket temperature compliance
 */
final class TemperatureMonitoringService
{
    use WithAuditLogging;
    use WithTelemetry;

    private readonly FraudControlService $fraudControl;
    private readonly TemperatureCRMIntegrationService $crmIntegration;

    public function __construct(FraudControlService $fraudControl, TemperatureCRMIntegrationService $crmIntegration)
    {
        $this->fraudControl = $fraudControl;
        $this->crmIntegration = $crmIntegration;
    }

    /**
     * Register a new temperature monitoring device
     */
    public function registerDevice(array $data): TemperatureMonitoringDevice
    {
        return $this->withSpan(
            'temperature_monitoring.register_device',
            function () use ($data) {
                // Fraud check before device registration
                $this->fraudControl->check([
                    'operation_type' => 'device_registration',
                    'vertical' => 'supermarket',
                    'correlation_id' => $this->generateCorrelationId(),
                ]);

                $device = TemperatureMonitoringDevice::create([
                    'tenant_id' => $data['tenant_id'] ?? null,
                    'device_id' => $data['device_id'],
                    'name' => $data['name'],
                    'serial_number' => $data['serial_number'] ?? null,
                    'manufacturer' => $data['manufacturer'] ?? null,
                    'model' => $data['model'] ?? null,
                    'mqtt_topic' => $data['mqtt_topic'] ?? null,
                    'location' => $data['location'] ?? null,
                    'zone' => $data['zone'] ?? null,
                    'coordinates' => $data['coordinates'] ?? null,
                    'reporting_interval_seconds' => $data['reporting_interval_seconds'] ?? 300,
                    'accuracy_celsius' => $data['accuracy_celsius'] ?? 0.5,
                    'status' => $data['status'] ?? TemperatureMonitoringDevice::STATUS_ACTIVE,
                    'notes' => $data['notes'] ?? null,
                    'metadata' => $data['metadata'] ?? null,
                ]);

                // Generate access secret
                $device->generateAccessSecret();

                $this->logCreated(
                    entity: 'temperature_monitoring_device',
                    entityId: $device->id,
                    context: [
                        'device_id' => $device->device_id,
                        'name' => $device->name,
                        'location' => $device->location,
                    ]
                );

                Log::info('Temperature monitoring device registered', [
                    'device_id' => $device->id,
                    'device_identifier' => $device->device_id,
                ]);

                // Sync device to CRM
                dispatch(function () use ($device) {
                    $this->crmIntegration->syncDeviceToCRM($device);
                });

                return $device;
            },
            $this->getStandardAttributes(
                vertical: 'supermarket',
                operation: 'register_temperature_device',
            ),
        );
    }

    /**
     * Ingest temperature reading from device
     */
    public function ingestReading(string $accessKey, array $telemetry): TemperatureReading
    {
        return $this->withSpan(
            'temperature_monitoring.ingest_reading',
            function () use ($accessKey, $telemetry) {
                // Fraud check before ingesting reading
                $this->fraudControl->check([
                    'operation_type' => 'temperature_ingest',
                    'vertical' => 'supermarket',
                    'access_key' => Str::mask($accessKey, '*', 4, 20),
                    'correlation_id' => $this->generateCorrelationId(),
                ]);

                // Verify device by access key
                $device = TemperatureMonitoringDevice::where('access_key', $accessKey)
                    ->where('status', TemperatureMonitoringDevice::STATUS_ACTIVE)
                    ->first();

                if (!$device) {
                    Log::warning('Temperature reading rejected - invalid device', [
                        'access_key' => Str::mask($accessKey, '*', 4, 20),
                    ]);
                    throw new \InvalidArgumentException('Invalid device access key');
                }

                // Rate limiting check
                $rateLimitKey = "temp:ingest:{$device->id}:" . now()->format('Y-m-d-H');
                if (Cache::get($rateLimitKey, 0) > 1000) { // Max 1000 readings per hour per device
                    Log::warning('Temperature reading rate limit exceeded', [
                        'device_id' => $device->id,
                    ]);
                    throw new \RuntimeException('Rate limit exceeded');
                }
                Cache::increment($rateLimitKey, 1, 3600);

                // Create reading
                $reading = TemperatureReading::create([
                    'device_id' => $device->id,
                    'tenant_id' => $device->tenant_id,
                    'temperature_celsius' => $telemetry['temperature'],
                    'humidity_percent' => $telemetry['humidity'] ?? null,
                    'recorded_at' => $telemetry['recorded_at'] ?? now(),
                    'received_at' => now(),
                    'correlation_id' => $telemetry['correlation_id'] ?? $this->generateCorrelationId(),
                    'raw_data' => $telemetry['raw_data'] ?? null,
                ]);

                // Update device last seen
                $device->updateLastSeen();

                // Check for violations
                $this->checkViolation($reading, $device);

                $this->logAction(
                    entity: 'temperature_reading',
                    entityId: $reading->id,
                    action: 'ingested',
                    context: [
                        'device_id' => $device->id,
                        'temperature' => $reading->temperature_celsius,
                        'is_violation' => $reading->is_violation,
                    ]
                );

                // Dispatch events for CRM sync and other processing
                if ($reading->is_violation) {
                    $requirement = $reading->productRequirement;
                    if ($requirement) {
                        TemperatureViolationDetected::dispatch($reading, $device, $requirement);
                    }
                } else {
                    TemperatureReadingReceived::dispatch($reading, $device);
                }

                return $reading;
            },
            $this->getStandardAttributes(
                vertical: 'supermarket',
                operation: 'ingest_temperature_reading',
            ),
        );
    }

    /**
     * Check reading for temperature violations
     */
    private function checkViolation(TemperatureReading $reading, TemperatureMonitoringDevice $device): void
    {
        // Find applicable product requirements for this device's location/zone
        $requirements = ProductTemperatureRequirement::where('tenant_id', $device->tenant_id)
            ->where('requires_continuous_monitoring', true)
            ->get();

        foreach ($requirements as $requirement) {
            $reading->product_requirement_id = $requirement->id;
            $reading->checkViolation($requirement);

            if ($reading->is_violation) {
                $reading->save();

                // Dispatch alert if not already sent
                if (!$reading->alert_sent) {
                    $this->dispatchAlert($reading, $device, $requirement);
                }

                Log::warning('Temperature violation detected', [
                    'device_id' => $device->id,
                    'reading_id' => $reading->id,
                    'temperature' => $reading->temperature_celsius,
                    'severity' => $reading->violation_severity,
                    'requirement_id' => $requirement->id,
                ]);

                break; // Use first matching requirement
            }
        }

        $reading->save();
    }

    /**
     * Dispatch alert for temperature violation
     */
    private function dispatchAlert(
        TemperatureReading $reading,
        TemperatureMonitoringDevice $device,
        ProductTemperatureRequirement $requirement
    ): void {
        // Dispatch job to send alert (async to avoid blocking)
        dispatch(function () use ($reading, $device, $requirement) {
            $alertService = app(TemperatureAlertService::class);
            $alertService->sendViolationAlert($reading, $device, $requirement);
        });

        $reading->markAlertSent();
    }

    /**
     * Get device status summary
     */
    public function getDeviceStatus(int $deviceId): array
    {
        return $this->withSpan(
            'temperature_monitoring.device_status',
            function () use ($deviceId) {
                $device = TemperatureMonitoringDevice::with(['latestReading'])->find($deviceId);

                if (!$device) {
                    throw new \InvalidArgumentException('Device not found');
                }

                $latestReading = $device->getLatestReading();
                $isOnline = $device->isOnline();
                $isCalibrationDue = $device->isCalibrationDue();

                return [
                    'device' => [
                        'id' => $device->id,
                        'name' => $device->name,
                        'device_id' => $device->device_id,
                        'location' => $device->location,
                        'zone' => $device->zone,
                        'status' => $device->status,
                        'is_online' => $isOnline,
                        'last_seen_at' => $device->last_seen_at?->toIso8601String(),
                    ],
                    'latest_reading' => $latestReading ? [
                        'temperature_celsius' => $latestReading->temperature_celsius,
                        'recorded_at' => $latestReading->recorded_at->toIso8601String(),
                        'is_violation' => $latestReading->is_violation,
                        'violation_severity' => $latestReading->violation_severity,
                    ] : null,
                    'calibration' => [
                        'last_calibration_at' => $device->last_calibration_at?->toIso8601String(),
                        'next_calibration_due_at' => $device->next_calibration_due_at?->toIso8601String(),
                        'is_due' => $isCalibrationDue,
                    ],
                ];
            },
            $this->getStandardAttributes(
                vertical: 'supermarket',
                operation: 'get_device_status',
            ),
        );
    }

    /**
     * Get temperature history for a device
     */
    public function getTemperatureHistory(
        int $deviceId,
        \Carbon\Carbon $from,
        \Carbon\Carbon $to,
        int $intervalMinutes = 5
    ): array {
        return $this->withSpan(
            'temperature_monitoring.temperature_history',
            function () use ($deviceId, $from, $to, $intervalMinutes) {
                $readings = TemperatureReading::byDevice($deviceId)
                    ->betweenDates($from, $to)
                    ->orderBy('recorded_at')
                    ->get();

                // Aggregate data by interval
                $aggregated = [];
                $currentInterval = null;

                foreach ($readings as $reading) {
                    $intervalKey = $reading->recorded_at->format('Y-m-d H:i');
                    $intervalTime = $reading->recorded_at->copy()->startOfHour()
                        ->addMinutes((int)($reading->recorded_at->minute / $intervalMinutes) * $intervalMinutes);

                    if (!isset($aggregated[$intervalTime->toIso8601String()])) {
                        $aggregated[$intervalTime->toIso8601String()] = [
                            'timestamp' => $intervalTime->toIso8601String(),
                            'temperatures' => [],
                            'count' => 0,
                            'violations' => 0,
                        ];
                    }

                    $aggregated[$intervalTime->toIso8601String()]['temperatures'][] = $reading->temperature_celsius;
                    $aggregated[$intervalTime->toIso8601String()]['count']++;
                    if ($reading->is_violation) {
                        $aggregated[$intervalTime->toIso8601String()]['violations']++;
                    }
                }

                // Calculate averages
                foreach ($aggregated as $key => $data) {
                    if ($data['count'] > 0) {
                        $aggregated[$key]['avg_temperature'] = array_sum($data['temperatures']) / $data['count'];
                        $aggregated[$key]['min_temperature'] = min($data['temperatures']);
                        $aggregated[$key]['max_temperature'] = max($data['temperatures']);
                    }
                    unset($aggregated[$key]['temperatures']);
                }

                return array_values($aggregated);
            },
            $this->getStandardAttributes(
                vertical: 'supermarket',
                operation: 'get_temperature_history',
            ),
        );
    }

    /**
     * Update device calibration
     */
    public function updateCalibration(int $deviceId, array $data): TemperatureMonitoringDevice
    {
        return $this->withSpan(
            'temperature_monitoring.update_calibration',
            function () use ($deviceId, $data) {
                $device = TemperatureMonitoringDevice::find($deviceId);

                if (!$device) {
                    throw new \InvalidArgumentException('Device not found');
                }

                $device->update([
                    'last_calibration_at' => now(),
                    'next_calibration_due_at' => $data['next_calibration_due_at'] ?? now()->addMonths(6),
                    'accuracy_celsius' => $data['accuracy_celsius'] ?? $device->accuracy_celsius,
                    'notes' => $data['notes'] ?? $device->notes,
                ]);

                $this->logAction(
                    entity: 'temperature_monitoring_device',
                    entityId: $device->id,
                    action: 'calibration_updated',
                    context: [
                        'device_id' => $device->device_id,
                        'next_calibration_due_at' => $device->next_calibration_due_at,
                    ]
                );

                return $device->fresh();
            },
            $this->getStandardAttributes(
                vertical: 'supermarket',
                operation: 'update_device_calibration',
            ),
        );
    }

    /**
     * Deactivate device
     */
    public function deactivateDevice(int $deviceId, string $reason): bool
    {
        return $this->withSpan(
            'temperature_monitoring.deactivate_device',
            function () use ($deviceId, $reason) {
                $device = TemperatureMonitoringDevice::find($deviceId);

                if (!$device) {
                    throw new \InvalidArgumentException('Device not found');
                }

                $device->update([
                    'status' => TemperatureMonitoringDevice::STATUS_INACTIVE,
                    'notes' => $device->notes . "\nDeactivated: {$reason}",
                ]);

                $this->logAction(
                    entity: 'temperature_monitoring_device',
                    entityId: $device->id,
                    action: 'deactivated',
                    context: [
                        'device_id' => $device->device_id,
                        'reason' => $reason,
                    ]
                );

                Log::info('Temperature monitoring device deactivated', [
                    'device_id' => $device->id,
                    'reason' => $reason,
                ]);

                return true;
            },
            $this->getStandardAttributes(
                vertical: 'supermarket',
                operation: 'deactivate_device',
            ),
        );
    }
}
