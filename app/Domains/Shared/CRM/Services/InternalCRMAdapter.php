<?php

declare(strict_types=1);

namespace App\Domains\Shared\CRM\Services;

use GuzzleHttp\Client;
use Illuminate\Support\Facades\Log;
use Psr\Log\LoggerInterface;

/**
 * InternalCRMAdapter - Адаптер для синхронизации с внутренней CRM CatVRF.
 *
 * Отправляет события из вертикалей (Supermarket, Restaurant, etc.) в CRM
 * через HTTP API с авторизацией и обработкой ошибок.
 *
 * Конфигурация в config/crm.php:
 * - internal.base_url - базовый URL CRM API
 * - internal.token - токен авторизации
 * - internal.timeout - таймаут запросов (по умолчанию 8 сек)
 */
final readonly class InternalCRMAdapter
{
    private Client $client;
    private LoggerInterface $logger;

    public function __construct(LoggerInterface $logger)
    {
        $this->logger = $logger;
        
        $this->client = new Client([
            'base_uri' => config('crm.internal.base_url', 'http://localhost:8000'),
            'headers' => [
                'Authorization' => 'Bearer ' . config('crm.internal.token'),
                'Content-Type' => 'application/json',
                'Accept' => 'application/json',
                'User-Agent' => 'CatVRF-CRM-Adapter/1.0',
            ],
            'timeout' => config('crm.internal.timeout', 8),
            'connect_timeout' => 5,
        ]);
    }

    /**
     * Отправить данные в CRM.
     *
     * @param string $endpoint Эндпоинт CRM API
     * @param array $payload Данные для отправки
     * @return bool Успешность отправки
     */
    public function send(string $endpoint, array $payload): bool
    {
        $correlationId = $payload['correlation_id'] ?? $this->generateCorrelationId();

        $this->logger->info('CRM sync started', [
            'correlation_id' => $correlationId,
            'endpoint' => $endpoint,
            'order_id' => $payload['order_id'] ?? null,
            'event' => $payload['event'] ?? null,
        ]);

        try {
            $response = $this->client->post($endpoint, [
                'json' => $payload,
                'headers' => [
                    'X-Correlation-ID' => $correlationId,
                ],
            ]);
            
            $statusCode = $response->getStatusCode();
            $success = $statusCode === 200 || $statusCode === 201 || $statusCode === 202;

            if ($success) {
                $this->logger->info('CRM sync success', [
                    'correlation_id' => $correlationId,
                    'endpoint' => $endpoint,
                    'order_id' => $payload['order_id'] ?? null,
                    'status_code' => $statusCode,
                ]);
            } else {
                $this->logger->warning('CRM sync returned non-success status', [
                    'correlation_id' => $correlationId,
                    'endpoint' => $endpoint,
                    'order_id' => $payload['order_id'] ?? null,
                    'status_code' => $statusCode,
                    'response' => (string) $response->getBody(),
                ]);
            }

            return $success;
        } catch (\GuzzleHttp\Exception\ConnectException $e) {
            $this->logger->error('CRM sync connection failed', [
                'correlation_id' => $correlationId,
                'endpoint' => $endpoint,
                'error' => $e->getMessage(),
                'payload' => $this->maskSensitiveData($payload),
            ]);
            return false;
        } catch (\GuzzleHttp\Exception\TimeoutException $e) {
            $this->logger->error('CRM sync timeout', [
                'correlation_id' => $correlationId,
                'endpoint' => $endpoint,
                'error' => $e->getMessage(),
                'payload' => $this->maskSensitiveData($payload),
            ]);
            return false;
        } catch (\GuzzleHttp\Exception\RequestException $e) {
            $response = $e->getResponse();
            $this->logger->error('CRM sync request failed', [
                'correlation_id' => $correlationId,
                'endpoint' => $endpoint,
                'error' => $e->getMessage(),
                'status_code' => $response ? $response->getStatusCode() : null,
                'response' => $response ? (string) $response->getBody() : null,
                'payload' => $this->maskSensitiveData($payload),
            ]);
            return false;
        } catch (\Exception $e) {
            $this->logger->error('CRM sync unexpected error', [
                'correlation_id' => $correlationId,
                'endpoint' => $endpoint,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'payload' => $this->maskSensitiveData($payload),
            ]);
            return false;
        }
    }

    /**
     * Проверить доступность CRM.
     *
     * @return bool
     */
    public function healthCheck(): bool
    {
        try {
            $response = $this->client->get('/health', [
                'timeout' => 3,
            ]);
            
            return $response->getStatusCode() === 200;
        } catch (\Exception $e) {
            $this->logger->warning('CRM health check failed', [
                'error' => $e->getMessage(),
            ]);
            return false;
        }
    }

    /**
     * Сгенерировать correlation ID.
     */
    private function generateCorrelationId(): string
    {
        return 'crm_' . uniqid() . '_' . bin2hex(random_bytes(4));
    }

    /**
     * Маскировать чувствительные данные в логах.
     */
    private function maskSensitiveData(array $data): array
    {
        $masked = $data;
        
        $sensitiveKeys = ['phone', 'email', 'card_number', 'passport', 'inn', 'kpp'];
        
        foreach ($sensitiveKeys as $key) {
            if (isset($masked[$key])) {
                $value = (string) $masked[$key];
                $masked[$key] = substr($value, 0, 2) . '***' . substr($value, -2);
            }
        }

        if (isset($masked['buyer_phone'])) {
            $value = (string) $masked['buyer_phone'];
            $masked['buyer_phone'] = substr($value, 0, 2) . '***' . substr($value, -2);
        }

        if (isset($masked['buyer_email'])) {
            $value = (string) $masked['buyer_email'];
            $parts = explode('@', $value);
            $masked['buyer_email'] = substr($parts[0], 0, 2) . '***@' . ($parts[1] ?? '***');
        }

        return $masked;
    }
}
