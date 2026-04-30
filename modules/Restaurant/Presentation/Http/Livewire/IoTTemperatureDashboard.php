<?php

declare(strict_types=1);

namespace Modules\Restaurant\Presentation\Http\Livewire;

use Livewire\Attributes\On;
use Livewire\Component;
use Modules\Restaurant\Application\Services\IoTHubService;
use Modules\Restaurant\Domain\Repositories\IoTDeviceRepositoryInterface;
use Modules\Restaurant\Domain\Enums\IoTDeviceType;

final class IoTTemperatureDashboard extends Component
{
    public array $temperatureData = [];
    public bool $loading = true;
    public string $timeRange = '1h'; // 1h, 6h, 24h, 7d

    public function mount(IoTHubService $iotHub): void
    {
        $this->loadTemperatureData($iotHub);
        $this->loading = false;
    }

    public function loadTemperatureData(IoTHubService $iotHub): void
    {
        $deviceRepo = app(IoTDeviceRepositoryInterface::class);
        $tempSensors = $deviceRepo->findByType(IoTDeviceType::TEMPERATURE_SENSOR);

        $this->temperatureData = [];
        
        foreach ($tempSensors as $device) {
            $telemetry = $iotHub->getDeviceTelemetry($device->id, 'temperature', 100);
            
            $this->temperatureData[] = [
                'device_id' => $device->id,
                'device_name' => $device->name,
                'device_identifier' => $device->deviceIdentifier,
                'current_temp' => $telemetry[0]?->value ?? null,
                'data_points' => array_map(fn($t) => [
                    'value' => $t->value,
                    'timestamp' => $t->recordedAt->toIso8601String(),
                    'is_alert' => $t->isAlert,
                ], $telemetry),
            ];
        }
    }

    #[On('echo:iot-dashboard,TemperatureUpdated')]
    public function handleTemperatureUpdate(array $payload): void
    {
        $iotHub = app(IoTHubService::class);
        $this->loadTemperatureData($iotHub);
    }

    public function updatedTimeRange(): void
    {
        $iotHub = app(IoTHubService::class);
        $this->loadTemperatureData($iotHub);
    }

    public function render()
    {
        return view('restaurant::livewire.iot-temperature-dashboard');
    }
}
