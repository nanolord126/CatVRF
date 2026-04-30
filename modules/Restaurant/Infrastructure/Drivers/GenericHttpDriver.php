<?php

declare(strict_types=1);

namespace Modules\Restaurant\Infrastructure\Drivers;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Modules\Restaurant\Domain\Entities\OrderKitchenStatus;

/**
 * Универсальный HTTP драйвер для кастомных внутренних интеграций
 * Позволяет отправлять заказы на любые HTTP-эндпоинты с настраиваемыми шаблонами
 */
final class GenericHttpDriver extends AbstractKitchenDriver
{
    public function getId(): string
    {
        return 'generic_http';
    }

    public function getName(): string
    {
        return 'Generic HTTP API';
    }

    public function isAvailable(): bool
    {
        return $this->isEnabled() && !empty($this->getConfig('endpoints', []));
    }

    public function checkConnection(): bool
    {
        if (!$this->isAvailable()) {
            return false;
        }

        $healthCheckUrl = $this->getConfig('health_check_url');

        if ($healthCheckUrl === null) {
            // Если health_check_url не настроен, считаем драйвер доступным
            return true;
        }

        try {
            $response = Http::timeout(5)->get($healthCheckUrl);
            return $response->successful();
        } catch (\Throwable $e) {
            Log::error("GenericHttpDriver health check failed", [
                'url' => $healthCheckUrl,
                'error' => $e->getMessage(),
            ]);
            return false;
        }
    }

    public function getDeviceStatus(): array
    {
        $status = parent::getDeviceStatus();
        
        $status['endpoints'] = $this->getConfig('endpoints', []);
        $status['health_check_url'] = $this->getConfig('health_check_url');
        $status['auth_type'] = $this->getConfig('auth_type', 'none');
        
        return $status;
    }

    protected function doSendOrder(OrderKitchenStatus $orderStatus): bool
    {
        $endpoints = $this->getConfig('endpoints', []);
        $endpoint = $endpoints[$orderStatus->kitchenStationId] ?? null;

        if ($endpoint === null) {
            Log::warning("GenericHttpDriver: endpoint not found for station", [
                'station_id' => $orderStatus->kitchenStationId,
            ]);
            return false;
        }

        $payload = $this->buildPayload($orderStatus, $endpoint);
        $url = $endpoint['url'];

        try {
            $http = Http::timeout($endpoint['timeout'] ?? 10);

            // Аутентификация
            $authType = $this->getConfig('auth_type', 'none');
            $http = $this->applyAuth($http, $authType);

            // Заголовки
            $headers = $endpoint['headers'] ?? [];
            foreach ($headers as $key => $value) {
                $http = $http->withHeaders([$key => $value]);
            }

            // Отправка запроса
            $method = strtoupper($endpoint['method'] ?? 'POST');
            
            $response = match ($method) {
                'GET' => $http->get($url, $payload),
                'POST' => $http->post($url, $payload),
                'PUT' => $http->put($url, $payload),
                'PATCH' => $http->patch($url, $payload),
                default => $http->post($url, $payload),
            };

            if ($response->successful()) {
                Log::info("GenericHttpDriver: order sent successfully", [
                    'url' => $url,
                    'order_id' => $orderStatus->orderId,
                    'status' => $response->status(),
                ]);
                return true;
            }

            Log::error("GenericHttpDriver: order send failed", [
                'url' => $url,
                'order_id' => $orderStatus->orderId,
                'status' => $response->status(),
                'body' => $response->body(),
            ]);
            return false;

        } catch (\Throwable $e) {
            Log::error("GenericHttpDriver: exception during send", [
                'url' => $url,
                'order_id' => $orderStatus->orderId,
                'error' => $e->getMessage(),
            ]);
            return false;
        }
    }

    protected function doUpdateOrderStatus(OrderKitchenStatus $orderStatus): bool
    {
        $endpoints = $this->getConfig('endpoints', []);
        $endpoint = $endpoints[$orderStatus->kitchenStationId] ?? null;

        if ($endpoint === null || !isset($endpoint['status_update_url'])) {
            return true; // Если endpoint не настроен для обновления статуса, считаем успехом
        }

        $payload = $this->buildPayload($orderStatus, $endpoint);
        $url = $endpoint['status_update_url'];

        try {
            $http = Http::timeout($endpoint['timeout'] ?? 10);
            $http = $this->applyAuth($http, $this->getConfig('auth_type', 'none'));

            $response = $http->put($url, $payload);

            return $response->successful();
        } catch (\Throwable $e) {
            Log::error("GenericHttpDriver: status update failed", [
                'url' => $url,
                'order_id' => $orderStatus->orderId,
                'error' => $e->getMessage(),
            ]);
            return false;
        }
    }

    protected function doCancelOrder(OrderKitchenStatus $orderStatus): bool
    {
        $endpoints = $this->getConfig('endpoints', []);
        $endpoint = $endpoints[$orderStatus->kitchenStationId] ?? null;

        if ($endpoint === null || !isset($endpoint['cancel_url'])) {
            return true;
        }

        $payload = $this->buildPayload($orderStatus, $endpoint);
        $url = $endpoint['cancel_url'];

        try {
            $http = Http::timeout($endpoint['timeout'] ?? 10);
            $http = $this->applyAuth($http, $this->getConfig('auth_type', 'none'));

            $response = $http->delete($url, $payload);

            return $response->successful();
        } catch (\Throwable $e) {
            Log::error("GenericHttpDriver: cancel failed", [
                'url' => $url,
                'order_id' => $orderStatus->orderId,
                'error' => $e->getMessage(),
            ]);
            return false;
        }
    }

    protected function doCheckConnection(): bool
    {
        return $this->checkConnection();
    }

    /**
     * Построить payload на основе шаблона
     */
    private function buildPayload(OrderKitchenStatus $orderStatus, array $endpoint): array
    {
        $template = $endpoint['payload_template'] ?? [];
        $order = \App\Models\Order::with('items')->find($orderStatus->orderId);

        $data = [
            'order_id' => $orderStatus->orderId,
            'order_uuid' => $order?->uuid,
            'kitchen_station_id' => $orderStatus->kitchenStationId,
            'status' => $orderStatus->status->value,
            'priority' => $orderStatus->priority->label(),
            'estimated_minutes' => $orderStatus->estimatedPreparationTime->minutes,
            'is_vip' => $orderStatus->isVip,
            'is_from_marketplace' => $orderStatus->isFromMarketplace,
            'elapsed_minutes' => $orderStatus->getElapsedMinutes(),
            'time_remaining' => $orderStatus->getTimeRemaining(),
            'progress' => $orderStatus->getProgress(),
            'started_at' => $orderStatus->startedAt?->toIso8601String(),
            'completed_at' => $orderStatus->completedAt?->toIso8601String(),
            'created_at' => $orderStatus->createdAt->toIso8601String(),
            'updated_at' => $orderStatus->updatedAt->toIso8601String(),
        ];

        if ($order) {
            $data['items'] = $order->items->map(fn ($item) => [
                'id' => $item->id,
                'product_name' => $item->product_name,
                'quantity' => $item->quantity,
                'unit_price' => $item->unit_price,
                'total_price' => $item->total_price,
                'options' => $item->options,
            ])->toArray();
        }

        // Применяем шаблон если есть
        if (!empty($template)) {
            return $this->applyTemplate($data, $template);
        }

        return $data;
    }

    /**
     * Применить шаблон к данным
     */
    private function applyTemplate(array $data, array $template): array
    {
        $result = [];

        foreach ($template as $key => $value) {
            if (is_string($value) && str_starts_with($value, '{{') && str_ends_with($value, '}}')) {
                // Simple template variable replacement
                $varPath = trim(substr($value, 2, -2));
                $result[$key] = $this->getNestedValue($data, $varPath);
            } elseif (is_array($value)) {
                $result[$key] = $this->applyTemplate($data, $value);
            } else {
                $result[$key] = $value;
            }
        }

        return $result;
    }

    /**
     * Получить вложенное значение по пути (dot.notation)
     */
    private function getNestedValue(array $data, string $path): mixed
    {
        $keys = explode('.', $path);
        $value = $data;

        foreach ($keys as $key) {
            if (!is_array($value) || !array_key_exists($key, $value)) {
                return null;
            }
            $value = $value[$key];
        }

        return $value;
    }

    /**
     * Применить аутентификацию к HTTP запросу
     */
    private function applyAuth(\Illuminate\Http\Client\PendingRequest $http, string $authType): \Illuminate\Http\Client\PendingRequest
    {
        return match ($authType) {
            'bearer' => $http->withToken($this->getConfig('auth_token')),
            'basic' => $http->withBasicAuth(
                $this->getConfig('auth_username'),
                $this->getConfig('auth_password')
            ),
            'api_key' => $http->withHeaders([
                $this->getConfig('auth_key_header', 'X-API-Key') => $this->getConfig('auth_key_value'),
            ]),
            'digest' => $http->withDigestAuth(
                $this->getConfig('auth_username'),
                $this->getConfig('auth_password')
            ),
            default => $http,
        };
    }
}
