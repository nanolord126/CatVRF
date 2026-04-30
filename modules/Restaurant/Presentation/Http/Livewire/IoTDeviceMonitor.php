<?php

declare(strict_types=1);

namespace Modules\Restaurant\Presentation\Http\Livewire;

use Illuminate\Support\Facades\Cache;
use Livewire\Attributes\On;
use Livewire\Component;
use Modules\Restaurant\Application\Services\IoTHubService;
use Modules\Restaurant\Domain\Entities\IoTDevice;
use Modules\Restaurant\Domain\Repositories\IoTDeviceRepositoryInterface;

final class IoTDeviceMonitor extends Component
{
    public array $devices = [];
    public array $telemetry = [];
    public bool $loading = true;
    public string $selectedDeviceType = 'all';
    public int $refreshInterval = 5;

    public function mount(IoTHubService $iotHub): void
    {
        $this->loadDevices($iotHub);
        $this->loading = false;
    }

    public function loadDevices(IoTHubService $iotHub): void
    {
        $deviceRepo = app(IoTDeviceRepositoryInterface::class);
        $allDevices = $deviceRepo->findByTenant(tenant()->id);

        $this->devices = array_map(function (IoTDevice $device) use ($iotHub) {
            $status = $iotHub->getDeviceStatus($device->id);
            return [
                'id' => $device->id,
                'name' => $device->name,
                'type' => $device->type->value,
                'type_label' => $device->type->label(),
                'protocol' => $device->protocol->value,
                'is_online' => $device->isOnline,
                'last_seen' => $device->lastSeenAt?->toIso8601String(),
                'station_id' => $device->kitchenStationId,
                'latest_value' => $status['latest_telemetry']['value'] ?? null,
                'latest_unit' => $status['latest_telemetry']['unit'] ?? null,
                'alerts_count' => $status['recent_alerts_count'] ?? 0,
            ];
        }, $allDevices);

        if ($this->selectedDeviceType !== 'all') {
            $this->devices = array_filter($this->devices, fn($d) => $d['type'] === $this->selectedDeviceType);
        }
    }

    #[On('echo:iot-dashboard,DeviceUpdated')]
    public function handleDeviceUpdate(array $payload): void
    {
        $deviceId = $payload['device_id'] ?? null;
        if ($deviceId) {
            $iotHub = app(IoTHubService::class);
            $this->loadDevices($iotHub);
        }
    }

    #[On('echo:iot-dashboard,TelemetryReceived')]
    public function handleTelemetryUpdate(array $payload): void
    {
        $deviceId = $payload['device_id'] ?? null;
        if ($deviceId) {
            $iotHub = app(IoTHubService::class);
            $this->loadDevices($iotHub);
        }
    }

    public function sendCommand(int $deviceId, string $command): void
    {
        try {
            $iotHub = app(IoTHubService::class);
            $iotHub->sendCommand($deviceId, $command);
            
            $this->dispatch('notification', [
                'type' => 'success',
                'message' => 'Command sent successfully',
            ]);
        } catch (\Exception $e) {
            $this->dispatch('notification', [
                'type' => 'error',
                'message' => 'Failed to send command: ' . $e->getMessage(),
            ]);
        }
    }

    public function render()
    {
        return view('restaurant::livewire.iot-device-monitor');
    }
}
