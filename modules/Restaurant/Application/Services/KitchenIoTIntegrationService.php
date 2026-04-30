<?php

declare(strict_types=1);

namespace Modules\Restaurant\Application\Services;

use Illuminate\Support\Facades\Log;
use Modules\Restaurant\Domain\Entities\IoTDevice;
use Modules\Restaurant\Domain\Entities\OrderKitchenStatus;
use Modules\Restaurant\Domain\Enums\IoTDeviceType;
use Modules\Restaurant\Domain\Repositories\IoTDeviceRepositoryInterface;

final class KitchenIoTIntegrationService
{
    public function __construct(
        private readonly IoTDeviceRepositoryInterface $deviceRepository,
        private readonly IoTHubService $iotHub,
    ) {}

    /**
     * Automatically start smart timer when order is sent to kitchen station
     */
    public function startSmartTimerOnOrder(OrderKitchenStatus $orderStatus): void
    {
        $timers = $this->deviceRepository->findByType(IoTDeviceType::SMART_TIMER);
        
        foreach ($timers as $timer) {
            if ($timer->kitchenStationId === $orderStatus->kitchenStationId) {
                $this->iotHub->sendCommand(
                    $timer->id,
                    'start_timer',
                    [
                        'duration_minutes' => $orderStatus->estimatedPreparationTime->toMinutes(),
                        'order_id' => $orderStatus->orderId,
                    ]
                );
                
                Log::info('Smart timer started for order', [
                    'device_id' => $timer->id,
                    'order_id' => $orderStatus->orderId,
                    'duration' => $orderStatus->estimatedPreparationTime->toMinutes(),
                ]);
            }
        }
    }

    /**
     * Print order on IoT printer when sent to kitchen station
     */
    public function printOrderOnIoT(OrderKitchenStatus $orderStatus): void
    {
        $printers = $this->deviceRepository->findByType(IoTDeviceType::SMART_PRINTER);
        
        foreach ($printers as $printer) {
            if ($printer->kitchenStationId === $orderStatus->kitchenStationId) {
                $this->iotHub->sendCommand(
                    $printer->id,
                    'print_order',
                    [
                        'order_id' => $orderStatus->orderId,
                        'items' => $this->formatOrderItems($orderStatus),
                        'priority' => $orderStatus->priority->value,
                        'timestamp' => now()->toIso8601String(),
                    ]
                );
                
                Log::info('Order printed on IoT device', [
                    'device_id' => $printer->id,
                    'order_id' => $orderStatus->orderId,
                ]);
            }
        }
    }

    /**
     * Get temperature from sensors for kitchen station
     */
    public function getKitchenTemperature(int $kitchenStationId): ?float
    {
        $sensors = $this->deviceRepository->findByType(IoTDeviceType::TEMPERATURE_SENSOR);
        
        foreach ($sensors as $sensor) {
            if ($sensor->kitchenStationId === $kitchenStationId && $sensor->isOnline) {
                $telemetry = $this->iotHub->getDeviceTelemetry($sensor->id, 'temperature', 1);
                return $telemetry[0]?->value ?? null;
            }
        }
        
        return null;
    }

    /**
     * Check if kitchen station meets safety requirements
     */
    public function checkKitchenSafety(int $kitchenStationId): array
    {
        $sensors = $this->deviceRepository->findByType(IoTDeviceType::TEMPERATURE_SENSOR);
        $issues = [];
        
        foreach ($sensors as $sensor) {
            if ($sensor->kitchenStationId === $kitchenStationId) {
                $telemetry = $this->iotHub->getDeviceTelemetry($sensor->id, 'temperature', 1);
                $latestTemp = $telemetry[0]?->value ?? null;
                
                if ($latestTemp !== null && $latestTemp > 8.0) {
                    $issues[] = [
                        'device_id' => $sensor->id,
                        'device_name' => $sensor->name,
                        'issue' => 'Temperature exceeds safe threshold',
                        'current_temp' => $latestTemp,
                    ];
                }
            }
        }
        
        return [
            'safe' => empty($issues),
            'issues' => $issues,
        ];
    }

    /**
     * Get weight from smart scales for ingredient verification
     */
    public function getWeightFromScales(int $kitchenStationId): ?float
    {
        $scales = $this->deviceRepository->findByType(IoTDeviceType::WEIGHT_SCALE);
        
        foreach ($scales as $scale) {
            if ($scale->kitchenStationId === $kitchenStationId && $scale->isOnline) {
                $telemetry = $this->iotHub->getDeviceTelemetry($scale->id, 'weight', 1);
                return $telemetry[0]?->value ?? null;
            }
        }
        
        return null;
    }

    private function formatOrderItems(OrderKitchenStatus $orderStatus): array
    {
        // This would format order items for printing
        // For now, return a placeholder
        return [
            [
                'name' => 'Order #' . $orderStatus->orderId,
                'quantity' => 1,
                'notes' => $orderStatus->priority->value,
            ],
        ];
    }
}
