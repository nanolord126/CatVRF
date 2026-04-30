<?php

declare(strict_types=1);

namespace Modules\Restaurant\Application\Services;

use App\Traits\WithAuditLogging;
use App\Services\Security\AuditService;
use Illuminate\Log\LogManager;
use Illuminate\Redis\Connections\Connection;
use PhpMqtt\Client\MqttClient;
use PhpMqtt\Client\ConnectionSettings;
use Exception;

final readonly class MqttClientService implements MqttClientServiceInterface
{
    use WithAuditLogging;

    private ?MqttClient $client = null;
    private bool $connected = false;

    public function __construct(
        private readonly LogManager $log,
        private readonly Connection $redis,
        private readonly AuditService $auditService,
    ) {
        $this->initializeClient();
    }

    private function initializeClient(): void
    {
        try {
            $config = config('restaurant.mqtt', [
                'host' => env('MQTT_HOST', '127.0.0.1'),
                'port' => (int) env('MQTT_PORT', 1883),
                'username' => env('MQTT_USERNAME'),
                'password' => env('MQTT_PASSWORD'),
                'client_id' => env('MQTT_CLIENT_ID', 'catvrf_iot_hub'),
            ]);

            $this->client = new MqttClient(
                $config['host'],
                $config['port'],
                $config['client_id']
            );

            $connectionSettings = (new ConnectionSettings())
                ->setConnectTimeout(5)
                ->setUseTls(false)
                ->setLastWillQualityOfService(0);

            if (!empty($config['username'])) {
                $connectionSettings->setUsername($config['username']);
                $connectionSettings->setPassword($config['password']);
            }

            $this->client->connect($connectionSettings);
            $this->connected = true;

            Log::info('MQTT client connected', [
                'host' => $config['host'],
                'port' => $config['port'],
            ]);
        } catch (Exception $e) {
            Log::error('Failed to connect to MQTT broker', [
                'error' => $e->getMessage(),
            ]);
            $this->connected = false;
        }
    }

    public function publish(string $topic, string $message, int $qos = 0, bool $retain = false): bool
    {
        if (!$this->connected) {
            $this->initializeClient();
        }

        if (!$this->connected) {
            Log::warning('MQTT not connected, publishing to Redis bridge', [
                'topic' => $topic,
            ]);
            
            // Fallback to Redis bridge
            Redis::publish("mqtt:publish", json_encode([
                'topic' => $topic,
                'message' => $message,
                'qos' => $qos,
                'retain' => $retain,
            ]));
            
            return true;
        }

        try {
            $this->client->publish($topic, $message, $qos, $retain);
            return true;
        } catch (Exception $e) {
            Log::error('Failed to publish MQTT message', [
                'topic' => $topic,
                'error' => $e->getMessage(),
            ]);
            return false;
        }
    }

    public function subscribe(string $topic, callable $callback, int $qos = 0): bool
    {
        if (!$this->connected) {
            $this->initializeClient();
        }

        if (!$this->connected) {
            Log::error('Cannot subscribe - MQTT not connected');
            return false;
        }

        try {
            $this->client->subscribe($topic, function (string $topic, string $message) use ($callback) {
                $callback($topic, $message);
            }, $qos);
            return true;
        } catch (Exception $e) {
            Log::error('Failed to subscribe to MQTT topic', [
                'topic' => $topic,
                'error' => $e->getMessage(),
            ]);
            return false;
        }
    }

    public function loop(bool $allowSleep = true): void
    {
        if ($this->connected && $this->client) {
            $this->client->loop($allowSleep);
        }
    }

    public function disconnect(): void
    {
        if ($this->connected && $this->client) {
            try {
                $this->client->disconnect();
                $this->connected = false;
            } catch (Exception $e) {
                Log::error('Failed to disconnect MQTT client', [
                    'error' => $e->getMessage(),
                ]);
            }
        }
    }

    public function isConnected(): bool
    {
        return $this->connected;
    }
}
