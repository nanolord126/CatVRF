<?php

declare(strict_types=1);

namespace Modules\Restaurant\Infrastructure\Drivers;

use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;
use Modules\Restaurant\Domain\Entities\OrderKitchenStatus;
use Modules\Restaurant\Domain\Interfaces\KitchenIntegrationDriverInterface;

/**
 * Абстрактный базовый класс для всех драйверов кухонного оборудования
 * Реализует общую логику для проверки доступности, конфигурации и обработки ошибок
 */
abstract class AbstractKitchenDriver implements KitchenIntegrationDriverInterface
{
    protected const CACHE_TTL = 300; // 5 минут
    protected const CACHE_KEY_PREFIX = 'kitchen_driver_';

    public function __construct(
        protected readonly string $tenantId,
    ) {}

    /**
     * Отправить заказ на кухонное устройство
     */
    public function sendOrder(OrderKitchenStatus $orderStatus): bool
    {
        if (!$this->isAvailable()) {
            Log::warning("Driver not available: {$this->getId()}", [
                'order_id' => $orderStatus->orderId,
            ]);
            return false;
        }

        try {
            return $this->doSendOrder($orderStatus);
        } catch (\Throwable $e) {
            Log::error("Failed to send order to driver: {$this->getId()}", [
                'order_id' => $orderStatus->orderId,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            $this->markAsUnavailable();
            return false;
        }
    }

    /**
     * Обновить статус заказа на устройстве
     */
    public function updateOrderStatus(OrderKitchenStatus $orderStatus): bool
    {
        if (!$this->isAvailable()) {
            return false;
        }

        try {
            return $this->doUpdateOrderStatus($orderStatus);
        } catch (\Throwable $e) {
            Log::error("Failed to update status on driver: {$this->getId()}", [
                'order_id' => $orderStatus->orderId,
                'error' => $e->getMessage(),
            ]);
            return false;
        }
    }

    /**
     * Отменить заказ на устройстве
     */
    public function cancelOrder(OrderKitchenStatus $orderStatus): bool
    {
        if (!$this->isAvailable()) {
            return false;
        }

        try {
            return $this->doCancelOrder($orderStatus);
        } catch (\Throwable $e) {
            Log::error("Failed to cancel order on driver: {$this->getId()}", [
                'order_id' => $orderStatus->orderId,
                'error' => $e->getMessage(),
            ]);
            return false;
        }
    }

    /**
     * Проверить соединение с устройством
     */
    public function checkConnection(): bool
    {
        $cacheKey = $this->getCacheKey('connection');
        
        return Cache::remember($cacheKey, self::CACHE_TTL, function () {
            return $this->doCheckConnection();
        });
    }

    /**
     * Получить статус устройства
     */
    public function getDeviceStatus(): array
    {
        return [
            'id' => $this->getId(),
            'name' => $this->getName(),
            'connected' => $this->checkConnection(),
            'available' => $this->isAvailable(),
        ];
    }

    /**
     * Проверить, доступен ли драйвер
     */
    public function isAvailable(): bool
    {
        $cacheKey = $this->getCacheKey('available');
        
        return Cache::remember($cacheKey, self::CACHE_TTL, function () {
            return $this->isEnabled() && $this->doCheckConnection();
        });
    }

    /**
     * Получить конфигурацию драйвера
     */
    protected function getConfig(string $key, mixed $default = null): mixed
    {
        $configKey = "kitchen.{$this->getId()}.{$key}";
        return config($configKey, $default);
    }

    /**
     * Проверить, включен ли драйвер в конфигурации
     */
    protected function isEnabled(): bool
    {
        return $this->getConfig('enabled', false);
    }

    /**
     * Отметить драйвер как недоступный (для circuit breaker)
     */
    protected function markAsUnavailable(): void
    {
        $cacheKey = $this->getCacheKey('available');
        Cache::put($cacheKey, false, 60); // Блокируем на 1 минуту
    }

    /**
     * Сбросить кэш доступности
     */
    protected function resetAvailabilityCache(): void
    {
        $cacheKey = $this->getCacheKey('available');
        Cache::forget($cacheKey);
        
        $connectionKey = $this->getCacheKey('connection');
        Cache::forget($connectionKey);
    }

    /**
     * Получить ключ кэша
     */
    protected function getCacheKey(string $suffix): string
    {
        return self::CACHE_KEY_PREFIX . $this->getId() . '_' . $this->tenantId . '_' . $suffix;
    }

    /**
     * Абстрактные методы для реализации в дочерних классах
     */
    abstract protected function doSendOrder(OrderKitchenStatus $orderStatus): bool;
    abstract protected function doUpdateOrderStatus(OrderKitchenStatus $orderStatus): bool;
    abstract protected function doCancelOrder(OrderKitchenStatus $orderStatus): bool;
    abstract protected function doCheckConnection(): bool;
}
