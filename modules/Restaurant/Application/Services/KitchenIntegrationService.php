<?php

declare(strict_types=1);

namespace Modules\Restaurant\Application\Services;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;
use Modules\Restaurant\Domain\Entities\OrderKitchenStatus;
use Modules\Restaurant\Domain\Enums\IntegrationDriverType;
use Modules\Restaurant\Domain\Interfaces\KitchenIntegrationDriverInterface;
use Modules\Restaurant\Infrastructure\Drivers\KdsDisplayDriver;
use Modules\Restaurant\Infrastructure\Drivers\KitchenPrinterDriver;
use Modules\Restaurant\Infrastructure\Drivers\SignalSystemDriver;
use Modules\Restaurant\Infrastructure\Drivers\MqttDriver;
use Modules\Restaurant\Infrastructure\Drivers\BrowserPrintDriver;
use Modules\Restaurant\Infrastructure\Drivers\GenericHttpDriver;

final class KitchenIntegrationService
{
    private array $drivers = [];

    public function __construct(
        private readonly string $tenantId,
    ) {
        $this->initializeDrivers();
    }

    private function initializeDrivers(): void
    {
        $this->drivers = [
            IntegrationDriverType::KDS_DISPLAY->value => new KdsDisplayDriver($this->tenantId),
            IntegrationDriverType::KITCHEN_PRINTER->value => new KitchenPrinterDriver($this->tenantId),
            IntegrationDriverType::SIGNAL_SYSTEM->value => new SignalSystemDriver($this->tenantId),
            IntegrationDriverType::MQTT->value => new MqttDriver($this->tenantId),
            IntegrationDriverType::WEBSOCKET->value => new KdsDisplayDriver($this->tenantId), // Reuse KDS driver for WebSocket
            IntegrationDriverType::BROWSER_PRINT->value => new BrowserPrintDriver($this->tenantId),
            IntegrationDriverType::GENERIC_HTTP->value => new GenericHttpDriver($this->tenantId),
        ];
    }

    /**
     * Отправить заказ на все активные драйверы
     */
    public function sendOrderToIntegrations(OrderKitchenStatus $orderStatus, array $enabledDrivers): array
    {
        $results = [];

        foreach ($enabledDrivers as $driverType) {
            $driver = $this->drivers[$driverType] ?? null;

            if ($driver === null) {
                Log::warning("Driver not found: {$driverType}");
                $results[$driverType] = ['success' => false, 'error' => 'Driver not found'];
                continue;
            }

            if (!$driver->isAvailable()) {
                Log::warning("Driver not available: {$driverType}");
                $results[$driverType] = ['success' => false, 'error' => 'Driver not available'];
                continue;
            }

            try {
                $success = $driver->sendOrder($orderStatus);
                $results[$driverType] = ['success' => $success];
                
                Log::info("Order sent to driver: {$driverType}", [
                    'order_id' => $orderStatus->orderId,
                    'success' => $success,
                ]);
            } catch (\Throwable $e) {
                Log::error("Failed to send order to driver: {$driverType}", [
                    'order_id' => $orderStatus->orderId,
                    'error' => $e->getMessage(),
                    'trace' => $e->getTraceAsString(),
                ]);
                $results[$driverType] = ['success' => false, 'error' => $e->getMessage()];
            }
        }

        return $results;
    }

    /**
     * Обновить статус заказа на всех активных драйверах
     */
    public function updateOrderStatusOnIntegrations(OrderKitchenStatus $orderStatus, array $enabledDrivers): array
    {
        $results = [];

        foreach ($enabledDrivers as $driverType) {
            $driver = $this->drivers[$driverType] ?? null;

            if ($driver === null || !$driver->isAvailable()) {
                continue;
            }

            try {
                $success = $driver->updateOrderStatus($orderStatus);
                $results[$driverType] = ['success' => $success];
            } catch (\Throwable $e) {
                Log::error("Failed to update status on driver: {$driverType}", [
                    'order_id' => $orderStatus->orderId,
                    'error' => $e->getMessage(),
                ]);
                $results[$driverType] = ['success' => false, 'error' => $e->getMessage()];
            }
        }

        return $results;
    }

    /**
     * Отменить заказ на всех активных драйверах
     */
    public function cancelOrderOnIntegrations(OrderKitchenStatus $orderStatus, array $enabledDrivers): array
    {
        $results = [];

        foreach ($enabledDrivers as $driverType) {
            $driver = $this->drivers[$driverType] ?? null;

            if ($driver === null || !$driver->isAvailable()) {
                continue;
            }

            try {
                $success = $driver->cancelOrder($orderStatus);
                $results[$driverType] = ['success' => $success];
            } catch (\Throwable $e) {
                Log::error("Failed to cancel order on driver: {$driverType}", [
                    'order_id' => $orderStatus->orderId,
                    'error' => $e->getMessage(),
                ]);
                $results[$driverType] = ['success' => false, 'error' => $e->getMessage()];
            }
        }

        return $results;
    }

    /**
     * Проверить соединение со всеми драйверами
     */
    public function checkAllConnections(array $enabledDrivers): array
    {
        $results = [];

        foreach ($enabledDrivers as $driverType) {
            $driver = $this->drivers[$driverType] ?? null;

            if ($driver === null) {
                $results[$driverType] = ['connected' => false, 'error' => 'Driver not found'];
                continue;
            }

            try {
                $connected = $driver->checkConnection();
                $status = $driver->getDeviceStatus();
                $results[$driverType] = [
                    'connected' => $connected,
                    'available' => $driver->isAvailable(),
                    'status' => $status,
                ];
            } catch (\Throwable $e) {
                $results[$driverType] = [
                    'connected' => false,
                    'error' => $e->getMessage(),
                ];
            }
        }

        return $results;
    }

    /**
     * Получить список доступных драйверов
     */
    public function getAvailableDrivers(): Collection
    {
        return collect($this->drivers)
            ->filter(fn (KitchenIntegrationDriverInterface $driver) => $driver->isAvailable())
            ->map(fn (KitchenIntegrationDriverInterface $driver) => [
                'id' => $driver->getId(),
                'name' => $driver->getName(),
                'status' => $driver->checkConnection() ? 'connected' : 'disconnected',
            ]);
    }

    /**
     * Получить драйвер по типу
     */
    public function getDriver(string $driverType): ?KitchenIntegrationDriverInterface
    {
        return $this->drivers[$driverType] ?? null;
    }

    /**
     * Запустить сигнализацию о просрочке
     */
    public function triggerOverdueAlert(OrderKitchenStatus $orderStatus, array $enabledDrivers): void
    {
        foreach ($enabledDrivers as $driverType) {
            if ($driverType === IntegrationDriverType::SIGNAL_SYSTEM->value) {
                $driver = $this->drivers[$driverType] ?? null;
                if ($driver !== null && $driver->isAvailable()) {
                    try {
                        $driver->sendOrder($orderStatus); // Reuse sendOrder for alert
                    } catch (\Throwable $e) {
                        Log::error("Failed to trigger overdue alert", [
                            'driver' => $driverType,
                            'error' => $e->getMessage(),
                        ]);
                    }
                }
            }
        }
    }
}
