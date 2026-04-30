<?php

declare(strict_types=1);

namespace Modules\Restaurant\Infrastructure\Drivers;

use Illuminate\Support\Facades\Log;
use Modules\Restaurant\Domain\Entities\OrderKitchenStatus;

final class SignalSystemDriver extends AbstractKitchenDriver
{
    public function __construct(string $tenantId)
    {
        parent::__construct($tenantId);
    }

    public function getId(): string
    {
        return 'signal_system';
    }

    public function getName(): string
    {
        return 'Signal System (Sound/Light Alerts)';
    }

    public function isAvailable(): bool
    {
        return $this->isEnabled() && $this->getConfig('api_url') !== null;
    }

    public function getDeviceStatus(): array
    {
        $status = parent::getDeviceStatus();
        $url = rtrim($this->getConfig('api_url'), '/') . '/status';
        $response = $this->httpGet($url);

        return $response ?? [
            'connected' => false,
            'sound_enabled' => false,
            'light_enabled' => false,
        ];
    }

    protected function doCheckConnection(): bool
    {
        if (!$this->isAvailable()) {
            return false;
        }

        $url = rtrim($this->getConfig('api_url'), '/') . '/ping';
        $response = $this->httpGet($url);

        return $response !== null && ($response['pong'] ?? false) === true;
    }

    protected function doSendOrder(OrderKitchenStatus $orderStatus): bool
    {
        // Для сигнальной системы sendOrder не используется напрямую
        // Вместо этого используем triggerAlert
        return true;
    }

    protected function doUpdateOrderStatus(OrderKitchenStatus $orderStatus): bool
    {
        if ($orderStatus->status->value === 'ready') {
            return $this->triggerAlert($orderStatus, 'order_ready');
        }

        if ($orderStatus->status->value === 'problem') {
            return $this->triggerAlert($orderStatus, 'order_problem');
        }

        return true;
    }

    protected function doCancelOrder(OrderKitchenStatus $orderStatus): bool
    {
        return $this->triggerAlert($orderStatus, 'order_cancelled');
    }

    public function triggerOverdueAlert(OrderKitchenStatus $orderStatus): bool
    {
        return $this->triggerAlert($orderStatus, 'order_overdue');
    }

    private function triggerAlert(OrderKitchenStatus $orderStatus, string $alertType): bool
    {
        $url = rtrim($this->getConfig('api_url'), '/') . '/alert';
        
        $payload = [
            'type' => $alertType,
            'order_id' => $orderStatus->orderId,
            'kitchen_station_id' => $orderStatus->kitchenStationId,
            'priority' => $orderStatus->priority->value,
            'elapsed_minutes' => $orderStatus->getElapsedMinutes(),
            'timestamp' => now()->toIso8601String(),
        ];

        // Звуковая сигнализация
        if ($this->getConfig('sound_enabled', true)) {
            $payload['sound'] = $this->getSoundForAlert($alertType);
        }

        // Световая сигнализация
        if ($this->getConfig('light_enabled', true)) {
            $payload['light'] = $this->getLightForAlert($alertType);
        }

        return $this->httpPost($url, $payload, [
            'Content-Type' => 'application/json',
            'X-API-Key' => $this->getConfig('api_key'),
        ]);
    }

    private function getSoundForAlert(string $alertType): array
    {
        return match ($alertType) {
            'order_ready' => [
                'type' => 'chime',
                'volume' => 80,
                'duration' => 2,
            ],
            'order_problem' => [
                'type' => 'alarm',
                'volume' => 100,
                'duration' => 5,
            ],
            'order_overdue' => [
                'type' => 'urgent',
                'volume' => 100,
                'duration' => 10,
            ],
            default => [
                'type' => 'beep',
                'volume' => 70,
                'duration' => 1,
            ],
        };
    }

    private function getLightForAlert(string $alertType): array
    {
        return match ($alertType) {
            'order_ready' => [
                'color' => 'green',
                'pattern' => 'blink',
                'duration' => 3,
            ],
            'order_problem' => [
                'color' => 'orange',
                'pattern' => 'flash',
                'duration' => 5,
            ],
            'order_overdue' => [
                'color' => 'red',
                'pattern' => 'strobe',
                'duration' => 10,
            ],
            default => [
                'color' => 'blue',
                'pattern' => 'solid',
                'duration' => 2,
            ],
        };
    }
}
