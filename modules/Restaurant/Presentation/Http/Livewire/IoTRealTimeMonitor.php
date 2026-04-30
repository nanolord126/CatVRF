<?php

declare(strict_types=1);

namespace Modules\Restaurant\Presentation\Http\Livewire;

use Illuminate\Cache\CacheManager;
use Livewire\Attributes\On;
use Livewire\Component;
use Modules\Restaurant\Domain\Repositories\IoTDeviceRepositoryInterface;
use Modules\Restaurant\Domain\Repositories\IoTTelemetryRepositoryInterface;

/**
 * IoTRealTimeMonitor
 *
 * CatVRF 2026 Canon - Production Mandatory
 * - Dependency injection instead of facades
 * - Readonly class
 */
final readonly class IoTRealTimeMonitor extends Component
{
    public array $devices = [];
    public array $telemetryData = [];
    public string $selectedMetric = 'temperature';
    public bool $autoRefresh = true;
    public int $refreshInterval = 5;

    public function mount(
        IoTDeviceRepositoryInterface $deviceRepository,
        private readonly CacheManager $cache,
    ): void
    {
        $this->loadDevices($deviceRepository);
    }

    private function loadDevices(IoTDeviceRepositoryInterface $deviceRepository): void
    {
        $devices = $deviceRepository->findOnline();
        
        $this->devices = array_map(function ($device) {
            return [
                'id' => $device->id,
                'name' => $device->name,
                'type' => $device->type->value,
                'identifier' => $device->deviceIdentifier,
                'station_id' => $device->kitchenStationId,
            ];
        }, $devices);
    }

    public function loadTelemetry(IoTTelemetryRepositoryInterface $telemetryRepository): void
    {
        foreach ($this->devices as $device) {
            $cacheKey = "iot_live:{$device['id']}:{$this->selectedMetric}";
            
            $telemetry = $this->cache->remember($cacheKey, 10, function () use ($telemetryRepository, $device) {
                $data = $telemetryRepository->findByDeviceAndMetric(
                    $device['id'],
                    $this->selectedMetric,
                    1
                );
                return $data[0] ?? null;
            });

            $this->telemetryData[$device['id']] = $telemetry ? [
                'value' => $telemetry->value,
                'unit' => $telemetry->unit,
                'recorded_at' => $telemetry->recordedAt->toIso8601String(),
                'is_alert' => $telemetry->isAlert,
            ] : null;
        }
    }

    #[On('echo:iot-telemetry,IoTTelemetryReceived')]
    public function handleTelemetryUpdate(array $payload): void
    {
        $deviceId = $payload['device_id'];
        
        if (isset($this->telemetryData[$deviceId])) {
            $this->telemetryData[$deviceId] = [
                'value' => $payload['value'],
                'unit' => $payload['unit'],
                'recorded_at' => $payload['recorded_at'],
                'is_alert' => $payload['is_alert'] ?? false,
            ];
        }
    }

    public function refresh(IoTTelemetryRepositoryInterface $telemetryRepository): void
    {
        $this->loadTelemetry($telemetryRepository);
    }

    public function toggleAutoRefresh(): void
    {
        $this->autoRefresh = !$this->autoRefresh;
    }

    public function render()
    {
        return view('restaurant::livewire.iot-real-time-monitor');
    }
}
