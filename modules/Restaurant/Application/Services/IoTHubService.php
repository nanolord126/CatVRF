<?php

declare(strict_types=1);

namespace Modules\Restaurant\Application\Services;

use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Redis;
use Modules\Restaurant\Domain\Entities\IoTDevice;
use Modules\Restaurant\Domain\Entities\IoTTelemetry;
use Modules\Restaurant\Domain\Enums\IoTDeviceType;
use Modules\Restaurant\Domain\Enums\IoTProtocol;
use Modules\Restaurant\Domain\Repositories\IoTDeviceRepositoryInterface;
use Modules\Restaurant\Domain\Repositories\IoTTelemetryRepositoryInterface;
use Modules\Restaurant\Domain\Events\IoTAlertTriggered;
use Carbon\CarbonImmutable;
use Exception;
use App\Traits\WithAuditLogging;
use App\Services\Security\AuditService;

final class IoTHubService
{
    use WithAuditLogging;

    private const CACHE_TTL = 300; // 5 minutes
    private const OFFLINE_THRESHOLD_MINUTES = 5;

    public function __construct(
        private readonly IoTDeviceRepositoryInterface $deviceRepository,
        private readonly IoTTelemetryRepositoryInterface $telemetryRepository,
        private readonly MqttClientServiceInterface $mqttClient,
        private readonly ModbusClientServiceInterface $modbusClient,
        private readonly IoTSecurityService $securityService,
        private readonly AuditService $auditService,
    ) {}

    /**
     * Register a new IoT device
     */
    public function registerDevice(
        int $tenantId,
        string $deviceIdentifier,
        string $name,
        IoTDeviceType $type,
        IoTProtocol $protocol,
        ?int $kitchenStationId = null,
        ?string $connectionConfig = null,
        ?string $brokerUrl = null,
        ?string $topicPrefix = null,
        ?array $metadata = null,
        ?string $description = null,
    ): IoTDevice {
        $existing = $this->deviceRepository->findByIdentifier($deviceIdentifier);
        if ($existing !== null) {
            throw new Exception("Device with identifier {$deviceIdentifier} already exists");
        }

        $device = IoTDevice::create(
            tenantId: $tenantId,
            deviceIdentifier: $deviceIdentifier,
            name: $name,
            type: $type,
            protocol: $protocol,
            kitchenStationId: $kitchenStationId,
            connectionConfig: $connectionConfig,
            brokerUrl: $brokerUrl,
            topicPrefix: $topicPrefix,
            metadata: $metadata,
            description: $description,
        );

        $saved = $this->deviceRepository->save($device);
        
        Log::info('IoT device registered', [
            'device_id' => $saved->id,
            'identifier' => $deviceIdentifier,
            'type' => $type->value,
            'protocol' => $protocol->value,
        ]);

        return $saved;
    }

    /**
     * Send command to device
     */
    public function sendCommand(int $deviceId, string $command, array $payload = []): bool
    {
        $device = $this->deviceRepository->findById($deviceId);
        if ($device === null) {
            throw new Exception("Device not found: {$deviceId}");
        }

        if (!$device->isActive) {
            throw new Exception("Device is not active: {$deviceId}");
        }

        if (!$device->isOnline) {
            Log::warning('Attempted to send command to offline device', [
                'device_id' => $deviceId,
                'command' => $command,
            ]);
            return false;
        }

        return $this->dispatchCommandByProtocol($device, $command, $payload);
    }

    /**
     * Handle incoming telemetry data with security verification
     */
    public function handleIncomingData(string $deviceIdentifier, array $payload, ?string $signature = null, ?string $nonce = null): void
    {
        // Security verification
        if ($signature !== null) {
            $signableData = json_encode($payload);
            $device = $this->securityService->verifyDeviceAndSignature(
                $deviceIdentifier,
                $signature,
                $signableData,
                null,
                $nonce
            );

            if ($device === null) {
                Log::warning('Security verification failed for incoming telemetry', [
                    'identifier' => $deviceIdentifier,
                ]);
                return;
            }
        } else {
            // Fallback to non-secure mode (for backward compatibility)
            $device = $this->deviceRepository->findByIdentifier($deviceIdentifier);
            if ($device === null) {
                Log::warning('Received data from unknown device', [
                    'identifier' => $deviceIdentifier,
                ]);
                return;
            }
        }

        $updatedDevice = $device->withOnlineStatus(true);
        $this->deviceRepository->save($updatedDevice);

        foreach ($payload as $metricType => $valueData) {
            $telemetry = $this->createTelemetryFromPayload(
                $device->id,
                $metricType,
                $valueData
            );
            
            $this->telemetryRepository->save($telemetry);
            $this->checkAlertRules($device, $telemetry);
        }

        // Run anomaly detection
        $anomalyMessage = $this->securityService->detectAnomaly($device->id, $payload);
        if ($anomalyMessage !== null) {
            Log::warning('IoT anomaly detected', [
                'device_id' => $device->id,
                'message' => $anomalyMessage,
            ]);
        }

        $this->invalidateDeviceCache($device->id);

        Log::debug('IoT telemetry processed', [
            'device_id' => $device->id,
            'metrics_count' => count($payload),
        ]);
    }

    /**
     * Subscribe to tenant topics for real-time updates
     */
    public function subscribeToTenant(int $tenantId): void
    {
        $topicPattern = "catcrm/{$tenantId}/devices/#";
        
        $this->mqttClient->subscribe($topicPattern, function (string $topic, string $message) {
            $this->handleMqttMessage($topic, $message);
        });

        Log::info('Subscribed to tenant IoT topics', [
            'tenant_id' => $tenantId,
            'topic_pattern' => $topicPattern,
        ]);
    }

    /**
     * Handle MQTT message
     */
    private function handleMqttMessage(string $topic, string $message): void
    {
        try {
            $payload = json_decode($message, true);
            if (!is_array($payload)) {
                Log::warning('Invalid MQTT message payload', ['topic' => $topic]);
                return;
            }

            $parts = explode('/', $topic);
            if (count($parts) < 4) {
                Log::warning('Invalid MQTT topic format', ['topic' => $topic]);
                return;
            }

            $deviceIdentifier = $parts[3];
            $this->handleIncomingData($deviceIdentifier, $payload);
        } catch (Exception $e) {
            Log::error('Failed to process MQTT message', [
                'topic' => $topic,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Get device telemetry history
     */
    public function getDeviceTelemetry(int $deviceId, ?string $metricType = null, int $limit = 100): array
    {
        $cacheKey = "iot_telemetry:{$deviceId}:" . ($metricType ?? 'all');
        
        return Cache::remember($cacheKey, self::CACHE_TTL, function () use ($deviceId, $metricType, $limit) {
            if ($metricType !== null) {
                return $this->telemetryRepository->findByDeviceAndMetric($deviceId, $metricType, $limit);
            }
            
            return $this->telemetryRepository->findByDevice($deviceId, $limit);
        });
    }

    /**
     * Get device current status
     */
    public function getDeviceStatus(int $deviceId): array
    {
        $device = $this->deviceRepository->findById($deviceId);
        if ($device === null) {
            throw new Exception("Device not found: {$deviceId}");
        }

        $latestTelemetry = $this->telemetryRepository->findLatestByDevice($deviceId);
        $recentAlerts = $this->telemetryRepository->findAlerts($deviceId, CarbonImmutable::now()->subHours(24));

        return [
            'device' => [
                'id' => $device->id,
                'name' => $device->name,
                'type' => $device->type->value,
                'protocol' => $device->protocol->value,
                'is_online' => $device->isOnline,
                'last_seen_at' => $device->lastSeenAt?->toIso8601String(),
            ],
            'latest_telemetry' => $latestTelemetry ? [
                'metric_type' => $latestTelemetry->metricType,
                'value' => $latestTelemetry->value,
                'unit' => $latestTelemetry->unit,
                'recorded_at' => $latestTelemetry->recordedAt->toIso8601String(),
            ] : null,
            'recent_alerts_count' => count($recentAlerts),
        ];
    }

    /**
     * Check for offline devices
     */
    public function checkOfflineDevices(): array
    {
        $offlineDevices = $this->deviceRepository->findOfflineFor(self::OFFLINE_THRESHOLD_MINUTES);
        
        foreach ($offlineDevices as $device) {
            if ($device->isOnline) {
                $updated = $device->withOnlineStatus(false);
                $this->deviceRepository->save($updated);
                
                Log::warning('Device marked as offline', [
                    'device_id' => $device->id,
                    'identifier' => $device->deviceIdentifier,
                    'last_seen' => $device->lastSeenAt?->toIso8601String(),
                ]);
            }
        }

        return $offlineDevices;
    }

    private function dispatchCommandByProtocol(IoTDevice $device, string $command, array $payload): bool
    {
        try {
            return match ($device->protocol) {
                IoTProtocol::MQTT => $this->sendMqttCommand($device, $command, $payload),
                IoTProtocol::WEBSOCKET => $this->sendWebSocketCommand($device, $command, $payload),
                IoTProtocol::HTTP => $this->sendHttpCommand($device, $command, $payload),
                IoTProtocol::MODBUS_TCP => $this->sendModbusTcpCommand($device, $command, $payload),
                default => throw new Exception("Protocol not supported: {$device->protocol->value}"),
            };
        } catch (Exception $e) {
            Log::error('Failed to send IoT command', [
                'device_id' => $device->id,
                'command' => $command,
                'error' => $e->getMessage(),
            ]);
            return false;
        }
    }

    private function sendMqttCommand(IoTDevice $device, string $command, array $payload): bool
    {
        $topic = $device->topicPrefix ?? "catcrm/{$device->tenantId}/devices/{$device->deviceIdentifier}/commands";
        $message = json_encode([
            'command' => $command,
            'payload' => $payload,
            'timestamp' => now()->toIso8601String(),
            'device_id' => $device->id,
        ]);

        $success = $this->mqttClient->publish($topic, $message, 1, false);
        
        if ($success) {
            Log::info('MQTT command sent', [
                'device_id' => $device->id,
                'topic' => $topic,
                'command' => $command,
            ]);
        }

        return $success;
    }

    private function sendWebSocketCommand(IoTDevice $device, string $command, array $payload): bool
    {
        $channel = "iot.device.{$device->deviceIdentifier}";
        Redis::publish($channel, json_encode([
            'command' => $command,
            'payload' => $payload,
            'timestamp' => now()->toIso8601String(),
        ]));

        return true;
    }

    private function sendHttpCommand(IoTDevice $device, string $command, array $payload): bool
    {
        $config = json_decode($device->connectionConfig ?? '{}', true);
        $url = $config['url'] ?? null;
        
        if ($url === null) {
            throw new Exception("HTTP URL not configured for device");
        }

        Log::info('HTTP command queued', [
            'device_id' => $device->id,
            'url' => $url,
            'command' => $command,
        ]);

        return true;
    }

    private function sendModbusTcpCommand(IoTDevice $device, string $command, array $payload): bool
    {
        $config = json_decode($device->connectionConfig ?? '{}', true);
        $host = $config['host'] ?? null;
        $port = $config['port'] ?? 502;
        $slaveId = $config['slave_id'] ?? 1;
        
        if ($host === null) {
            Log::error('Modbus TCP host not configured', [
                'device_id' => $device->id,
            ]);
            return false;
        }

        $connected = $this->modbusClient->connect($host, $port);
        
        if (!$connected) {
            Log::error('Failed to connect to Modbus device', [
                'device_id' => $device->id,
                'host' => $host,
                'port' => $port,
            ]);
            return false;
        }

        try {
            return match ($command) {
                'read_register' => $this->handleModbusRead($slaveId, $payload),
                'write_register' => $this->handleModbusWrite($slaveId, $payload),
                default => throw new Exception("Unknown Modbus command: {$command}"),
            };
        } finally {
            $this->modbusClient->disconnect();
        }
    }

    private function handleModbusRead(int $slaveId, array $payload): bool
    {
        $address = $payload['address'] ?? 0;
        $quantity = $payload['quantity'] ?? 1;
        
        $values = $this->modbusClient->readHoldingRegister($slaveId, $address, $quantity);
        
        if ($values === null) {
            Log::error('Modbus read failed', [
                'slave_id' => $slaveId,
                'address' => $address,
            ]);
            return false;
        }

        Log::info('Modbus read successful', [
            'slave_id' => $slaveId,
            'address' => $address,
            'values' => $values,
        ]);

        return true;
    }

    private function handleModbusWrite(int $slaveId, array $payload): bool
    {
        $address = $payload['address'] ?? 0;
        $value = $payload['value'] ?? 0;
        
        $success = $this->modbusClient->writeSingleRegister($slaveId, $address, $value);
        
        if (!$success) {
            Log::error('Modbus write failed', [
                'slave_id' => $slaveId,
                'address' => $address,
                'value' => $value,
            ]);
        }

        return $success;
    }

    private function createTelemetryFromPayload(int $deviceId, string $metricType, mixed $valueData): IoTTelemetry
    {
        $value = null;
        $valueString = null;
        $valueJson = null;
        $unit = null;

        if (is_numeric($valueData)) {
            $value = (float) $valueData;
            
            $unit = match ($metricType) {
                'temperature' => 'celsius',
                'weight' => 'kg',
                'humidity' => '%',
                'oil_level' => '%',
                default => null,
            };
        } elseif (is_string($valueData)) {
            $valueString = $valueData;
        } elseif (is_array($valueData)) {
            $valueJson = $valueData;
            $unit = $valueData['unit'] ?? null;
        }

        return IoTTelemetry::create(
            iotDeviceId: $deviceId,
            metricType: $metricType,
            value: $value,
            valueString: $valueString,
            valueJson: $valueJson,
            unit: $unit,
        );
    }

    private function checkAlertRules(IoTDevice $device, IoTTelemetry $telemetry): void
    {
        $alertTriggered = false;
        $alertMessage = null;

        if ($telemetry->metricType === 'temperature' && $telemetry->value !== null) {
            $threshold = $device->metadata['temperature_threshold'] ?? 8.0;
            if ($telemetry->value > $threshold) {
                $alertTriggered = true;
                $alertMessage = "Temperature exceeds safe threshold: {$telemetry->value}°C (max: {$threshold}°C)";
            }
        }

        if ($telemetry->metricType === 'weight' && $telemetry->value !== null) {
            $maxWeight = $device->metadata['max_weight'] ?? null;
            if ($maxWeight !== null && $telemetry->value > $maxWeight) {
                $alertTriggered = true;
                $alertMessage = "Weight exceeds maximum: {$telemetry->value}kg (max: {$maxWeight}kg)";
            }
        }

        if ($alertTriggered && $alertMessage !== null) {
            $alertTelemetry = $telemetry->asAlert($alertMessage);
            $this->telemetryRepository->save($alertTelemetry);
            
            event(new IoTAlertTriggered($device, $telemetry, $alertMessage));
            
            Log::warning('IoT alert triggered', [
                'device_id' => $device->id,
                'metric_type' => $telemetry->metricType,
                'value' => $telemetry->value,
                'message' => $alertMessage,
            ]);
        }
    }

    private function invalidateDeviceCache(int $deviceId): void
    {
        Cache::forget("iot_telemetry:{$deviceId}:all");
        Cache::forget("iot_telemetry:{$deviceId}:temperature");
        Cache::forget("iot_telemetry:{$deviceId}:weight");
    }
}
