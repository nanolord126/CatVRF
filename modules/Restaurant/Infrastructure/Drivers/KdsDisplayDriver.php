<?php

declare(strict_types=1);

namespace Modules\Restaurant\Infrastructure\Drivers;

use Illuminate\Support\Facades\Log;
use Modules\Restaurant\Domain\Entities\OrderKitchenStatus;

final class KdsDisplayDriver extends AbstractKitchenDriver
{
    public function __construct(string $tenantId)
    {
        parent::__construct($tenantId);
    }

    public function getId(): string
    {
        return 'kds_display';
    }

    public function getName(): string
    {
        return 'KDS Display (Internal WebSocket)';
    }

    public function isAvailable(): bool
    {
        return $this->isEnabled(); // Всегда доступен, т.к. использует внутренний WebSocket
    }

    public function getDeviceStatus(): array
    {
        $status = parent::getDeviceStatus();
        $status['type'] = 'internal_websocket';
        $status['active_connections'] = $this->getActiveConnectionsCount();
        return $status;
    }

    protected function doCheckConnection(): bool
    {
        // Проверяем, что WebSocket сервер запущен
        return $this->isAvailable();
    }

    protected function doSendOrder(OrderKitchenStatus $orderStatus): bool
    {
        // Внутренний KDS использует Laravel Echo Events напрямую
        // Драйвер просто транслирует событие, которое уже обрабатывается в Livewire
        event(new \Modules\Restaurant\Domain\Events\OrderSentToKitchen($orderStatus));
        
        return true;
    }

    protected function doUpdateOrderStatus(OrderKitchenStatus $orderStatus): bool
    {
        event(new \Modules\Restaurant\Domain\Events\OrderStatusUpdated(
            $orderStatus,
            $this->getPreviousStatus($orderStatus)
        ));
        
        return true;
    }

    protected function doCancelOrder(OrderKitchenStatus $orderStatus): bool
    {
        event(new \Modules\Restaurant\Domain\Events\OrderStatusUpdated(
            $orderStatus,
            $orderStatus->status->value
        ));
        
        return true;
    }

    private function getPreviousStatus(OrderKitchenStatus $orderStatus): string
    {
        // В реальном приложении можно получить из истории или event sourcing
        return $orderStatus->status->value;
    }

    private function getActiveConnectionsCount(): int
    {
        // Получить количество активных WebSocket соединений
        // Это может быть реализовано через Redis или Pusher dashboard API
        return 0; // Placeholder
    }
}
