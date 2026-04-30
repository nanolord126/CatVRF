<?php

declare(strict_types=1);

namespace Modules\Restaurant\Domain\Interfaces;

use Modules\Restaurant\Domain\Entities\OrderKitchenStatus;

/**
 * Интерфейс драйвера интеграции с кухонным оборудованием
 */
interface KitchenIntegrationDriverInterface
{
    /**
     * Идентификатор драйвера
     */
    public function getId(): string;

    /**
     * Название драйвера
     */
    public function getName(): string;

    /**
     * Отправить заказ на кухонное устройство
     */
    public function sendOrder(OrderKitchenStatus $orderStatus): bool;

    /**
     * Обновить статус заказа на устройстве
     */
    public function updateOrderStatus(OrderKitchenStatus $orderStatus): bool;

    /**
     * Отменить заказ на устройстве
     */
    public function cancelOrder(OrderKitchenStatus $orderStatus): bool;

    /**
     * Проверить соединение с устройством
     */
    public function checkConnection(): bool;

    /**
     * Получить статус устройства
     */
    public function getDeviceStatus(): array;

    /**
     * Проверить, доступен ли драйвер
     */
    public function isAvailable(): bool;
}
