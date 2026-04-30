<?php

declare(strict_types=1);

namespace Modules\BigData\Infrastructure\Kafka;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Redis;
use Modules\BigData\Domain\DTOs\BaseEventDTO;

/**
 * Kafka Producer Service for Event Streaming
 *
 * Supports two modes:
 * 1. Confluent Cloud REST Proxy (HTTP-based, no rdkafka extension needed)
 * 2. Redis Streams fallback (when Kafka is unavailable)
 *
 * Confluent Cloud: https://confluent.cloud/
 * REST Proxy API: https://docs.confluent.io/platform/current/kafka-rest/api.html
 */
final class KafkaProducerService
{
    private bool $useRestProxy;
    private bool $useRedisFallback;

    public function __construct(
        private readonly string $brokers,
        private readonly string $topic,
        private readonly ?string $username = null,
        private readonly ?string $password = null,
        private readonly int $timeoutMs = 10000,
        private readonly ?string $restProxyUrl = null,
        private readonly ?string $clusterId = null,
    ) {
        $this->useRestProxy = !empty($this->restProxyUrl);
        $this->useRedisFallback = !$this->useRestProxy;
    }

    /**
     * Publish a single event
     */
    public function publish(BaseEventDTO $event, ?string $key = null): bool
    {
        $payload = $event->toJson();
        $key = $key ?? (string) $event->tenantId;

        if ($this->useRestProxy) {
            return $this->publishViaRestProxy($payload, $key);
        }

        return $this->publishViaRedis($payload, $key);
    }

    /**
     * Publish multiple events in batch
     *
     * @param array<BaseEventDTO> $events
     */
    public function publishBatch(array $events): int
    {
        $successCount = 0;

        if ($this->useRestProxy) {
            $records = [];
            foreach ($events as $event) {
                $records[] = [
                    'key' => ['type' => 'string', 'data' => (string) $event->tenantId],
                    'value' => ['type' => 'JSON', 'data' => $event->toJson()],
                ];
            }

            $success = $this->publishBatchViaRestProxy($records);
            return $success ? count($events) : 0;
        }

        foreach ($events as $event) {
            if ($this->publishViaRedis($event->toJson(), (string) $event->tenantId)) {
                $successCount++;
            }
        }

        return $successCount;
    }

    /**
     * Publish raw JSON payload
     */
    public function publishRaw(string $json, ?string $key = null): bool
    {
        if ($this->useRestProxy) {
            return $this->publishViaRestProxy($json, $key ?? 'default');
        }

        return $this->publishViaRedis($json, $key ?? 'default');
    }

    /**
     * Publish via Confluent REST Proxy
     */
    private function publishViaRestProxy(string $payload, string $key): bool
    {
        try {
            $url = "{$this->restProxyUrl}/topics/{$this->topic}";

            $response = Http::withOptions([
                'timeout' => $this->timeoutMs / 1000,
                'connect_timeout' => 5,
            ])->withHeaders([
                'Content-Type' => 'application/vnd.kafka.json.v2+json',
                'Accept' => 'application/vnd.kafka.v2+json',
            ]);

            if ($this->username && $this->password) {
                $response = $response->withBasicAuth($this->username, $this->password);
            }

            $response = $response->post($url, [
                'records' => [
                    [
                        'key' => ['type' => 'string', 'data' => $key],
                        'value' => ['type' => 'JSON', 'data' => $payload],
                    ],
                ],
            ]);

            if ($response->successful()) {
                return true;
            }

            Log::error('Kafka REST Proxy publish failed', [
                'status' => $response->status(),
                'body' => $response->body(),
            ]);

            return false;
        } catch (\Exception $e) {
            Log::error('Kafka REST Proxy exception', [
                'error' => $e->getMessage(),
            ]);

            return false;
        }
    }

    /**
     * Publish batch via Confluent REST Proxy
     */
    private function publishBatchViaRestProxy(array $records): bool
    {
        try {
            $url = "{$this->restProxyUrl}/topics/{$this->topic}";

            $response = Http::withOptions([
                'timeout' => $this->timeoutMs / 1000,
                'connect_timeout' => 5,
            ])->withHeaders([
                'Content-Type' => 'application/vnd.kafka.json.v2+json',
                'Accept' => 'application/vnd.kafka.v2+json',
            ]);

            if ($this->username && $this->password) {
                $response = $response->withBasicAuth($this->username, $this->password);
            }

            $response = $response->post($url, [
                'records' => $records,
            ]);

            return $response->successful();
        } catch (\Exception $e) {
            Log::error('Kafka REST Proxy batch exception', [
                'error' => $e->getMessage(),
            ]);

            return false;
        }
    }

    /**
     * Publish via Redis Streams (fallback when Kafka unavailable)
     */
    private function publishViaRedis(string $payload, string $key): bool
    {
        try {
            $streamKey = "bigdata:events:{$this->topic}";

            Redis::xadd($streamKey, '*', [
                'key' => $key,
                'payload' => $payload,
                'timestamp' => (string) microtime(true),
            ]);

            return true;
        } catch (\Exception $e) {
            Log::error('Redis Streams publish failed', [
                'error' => $e->getMessage(),
                'topic' => $this->topic,
            ]);

            return false;
        }
    }

    /**
     * Flush pending messages (no-op for HTTP/Redis)
     */
    public function flush(int $timeoutMs = 10000): void
    {
        // No-op for HTTP and Redis implementations
    }

    /**
     * Purge pending messages (no-op for HTTP/Redis)
     */
    public function purge(): void
    {
        // No-op for HTTP and Redis implementations
    }

    /**
     * Check if using REST Proxy
     */
    public function isUsingRestProxy(): bool
    {
        return $this->useRestProxy;
    }

    /**
     * Check if using Redis fallback
     */
    public function isUsingRedisFallback(): bool
    {
        return $this->useRedisFallback;
    }

    /**
     * Get consumer info for monitoring
     */
    public function getStatus(): array
    {
        return [
            'mode' => $this->useRestProxy ? 'confluent_rest_proxy' : 'redis_streams_fallback',
            'topic' => $this->topic,
            'brokers' => $this->brokers,
            'rest_proxy_url' => $this->restProxyUrl,
        ];
    }
}
