<?php

declare(strict_types=1);

namespace Modules\Restaurant\Infrastructure\Drivers;

use Illuminate\Support\Facades\Log;
use Modules\Restaurant\Domain\Entities\OrderKitchenStatus;
use PhpMqtt\Client\MqttClient;
use PhpMqtt\Client\ConnectionSettings;

final class MqttDriver extends AbstractKitchenDriver
{
    private ?MqttClient $client = null;

    public function __construct(string $tenantId)
    {
        parent::__construct($tenantId);
    }

    public function getId(): string
    {
        return 'mqtt';
    }

    public function getName(): string
    {
        return 'MQTT (Tablets)';
    }

    public function isAvailable(): bool
    {
        return $this->isEnabled() && $this->getConfig('host') !== null;
    }

    public function getDeviceStatus(): array
    {
        $status = parent::getDeviceStatus();
        $status['host'] = $this->getConfig('host');
        $status['port'] = $this->getConfig('port', 1883);
        $status['subscribed_stations'] = $this->getConfig('subscribed_stations', []);
        return $status;
    }

    protected function doCheckConnection(): bool
    {
        if (!$this->isAvailable()) {
            return false;
        }

        try {
            $client = $this->getClient();
            $client->connect($this->getConnectionSettings());
            $connected = $client->isConnected();
            $client->disconnect();
            
            return $connected;
        } catch (\Throwable $e) {
            Log::error("MQTT connection check failed", ['error' => $e->getMessage()]);
            return false;
        }
    }

    protected function doSendOrder(OrderKitchenStatus $orderStatus): bool
    {
        try {
            $client = $this->getClient();
            $client->connect($this->getConnectionSettings());

            $topic = "kitchen/station/{$orderStatus->kitchenStationId}/orders";
            $payload = json_encode([
                'order_id' => $orderStatus->orderId,
                'status' => $orderStatus->status->value,
                'priority' => $orderStatus->priority->value,
                'estimated_minutes' => $orderStatus->estimatedPreparationTime->minutes,
                'is_vip' => $orderStatus->isVip,
                'is_from_marketplace' => $orderStatus->isFromMarketplace,
                'created_at' => $orderStatus->createdAt->toIso8601String(),
            ]);

            $client->publish($topic, $payload, 1);
            $client->disconnect();

            return true;
        } catch (\Throwable $e) {
            Log::error("MQTT publish failed", [
                'error' => $e->getMessage(),
                'order_id' => $orderStatus->orderId,
            ]);
            return false;
        }
    }

    protected function doUpdateOrderStatus(OrderKitchenStatus $orderStatus): bool
    {
        try {
            $client = $this->getClient();
            $client->connect($this->getConnectionSettings());

            $topic = "kitchen/station/{$orderStatus->kitchenStationId}/orders/{$orderStatus->orderId}/status";
            $payload = json_encode([
                'status' => $orderStatus->status->value,
                'updated_at' => $orderStatus->updatedAt->toIso8601String(),
                'elapsed_minutes' => $orderStatus->getElapsedMinutes(),
                'is_overdue' => $orderStatus->isOverdue(),
            ]);

            $client->publish($topic, $payload, 1);
            $client->disconnect();

            return true;
        } catch (\Throwable $e) {
            Log::error("MQTT status update failed", [
                'error' => $e->getMessage(),
                'order_id' => $orderStatus->orderId,
            ]);
            return false;
        }
    }

    protected function doCancelOrder(OrderKitchenStatus $orderStatus): bool
    {
        try {
            $client = $this->getClient();
            $client->connect($this->getConnectionSettings());

            $topic = "kitchen/station/{$orderStatus->kitchenStationId}/orders/{$orderStatus->orderId}/cancel";
            $payload = json_encode([
                'cancelled_at' => now()->toIso8601String(),
            ]);

            $client->publish($topic, $payload, 1);
            $client->disconnect();

            return true;
        } catch (\Throwable $e) {
            Log::error("MQTT cancel failed", [
                'error' => $e->getMessage(),
                'order_id' => $orderStatus->orderId,
            ]);
            return false;
        }
    }

    private function getClient(): MqttClient
    {
        if ($this->client === null) {
            $host = $this->getConfig('host', 'localhost');
            $port = $this->getConfig('port', 1883);
            $clientId = $this->getConfig('client_id', 'catvrf-kds-' . getmypid());
            
            $this->client = new MqttClient($host, $port, $clientId);
        }

        return $this->client;
    }

    private function getConnectionSettings(): ConnectionSettings
    {
        $settings = new ConnectionSettings();
        
        if ($this->getConfig('username')) {
            $settings->setUsername($this->getConfig('username'));
        }

        if ($this->getConfig('password')) {
            $settings->setPassword($this->getConfig('password'));
        }

        $settings->setUseTls($this->getConfig('use_tls', false));
        $settings->setTlsSelfSignedAllowed($this->getConfig('tls_self_signed', true));

        return $settings;
    }
}
