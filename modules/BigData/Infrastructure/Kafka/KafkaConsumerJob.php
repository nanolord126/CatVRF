<?php

declare(strict_types=1);

namespace Modules\BigData\Infrastructure\Kafka;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Redis;
use Modules\BigData\Infrastructure\ClickHouse\ClickHouseService;

/**
 * Kafka Consumer Job
 *
 * Consumes events from:
 * 1. Confluent Cloud REST Proxy (HTTP-based)
 * 2. Redis Streams (fallback when Kafka unavailable)
 *
 * Batches events and inserts into ClickHouse.
 */
final class KafkaConsumerJob implements ShouldQueue
{
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    private const MAX_MESSAGES = 1000;
    private const BATCH_SIZE = 100;
    private const CONSUME_TIMEOUT_MS = 1000;

    public function __construct(
        private readonly string $brokers,
        private readonly string $topic,
        private readonly string $groupId,
        private readonly ?string $username = null,
        private readonly ?string $password = null,
        private readonly int $maxMessages = self::MAX_MESSAGES,
        private readonly ?string $restProxyUrl = null,
    ) {}

    public function handle(ClickHouseService $clickHouse): void
    {
        if (!empty($this->restProxyUrl)) {
            $this->consumeFromRestProxy($clickHouse);
        } else {
            $this->consumeFromRedis($clickHouse);
        }
    }

    /**
     * Consume from Confluent REST Proxy
     */
    private function consumeFromRestProxy(ClickHouseService $clickHouse): void
    {
        try {
            // Create consumer instance
            $instanceName = "catvrf-{$this->groupId}-" . uniqid();
            $instanceUrl = "{$this->restProxyUrl}/consumers/{$this->groupId}/instances/{$instanceName}";

            $response = Http::withOptions(['timeout' => 10])
                ->withHeaders([
                    'Content-Type' => 'application/vnd.kafka.json.v2+json',
                    'Accept' => 'application/vnd.kafka.json.v2+json',
                ]);

            if ($this->username && $this->password) {
                $response = $response->withBasicAuth($this->username, $this->password);
            }

            // Create consumer
            $response->post("{$this->restProxyUrl}/consumers/{$this->groupId}", [
                'name' => $instanceName,
                'format' => 'json',
                'auto.offset.reset' => 'latest',
                'auto.enable.auto.commit' => true,
            ]);

            // Subscribe to topic
            $response->post("{$instanceUrl}/subscription", [
                'topics' => [$this->topic],
            ]);

            // Consume messages
            $batch = [];
            $messageCount = 0;

            while ($messageCount < $this->maxMessages) {
                $consumeResponse = $response->get("{$instanceUrl}/records", [
                    'timeout' => self::CONSUME_TIMEOUT_MS,
                    'max_bytes' => 1048576,
                ]);

                if (!$consumeResponse->successful()) {
                    break;
                }

                $records = $consumeResponse->json() ?? [];

                if (empty($records)) {
                    break;
                }

                foreach ($records as $record) {
                    $this->processRecord($record, $batch, $clickHouse);
                    $messageCount++;

                    if (count($batch) >= self::BATCH_SIZE) {
                        $this->flushBatch($batch, $clickHouse);
                        $batch = [];
                    }
                }
            }

            // Flush remaining
            if (!empty($batch)) {
                $this->flushBatch($batch, $clickHouse);
            }

            // Delete consumer instance
            $response->delete($instanceUrl);

        } catch (\Exception $e) {
            Log::error('Kafka REST Proxy consumer error', [
                'error' => $e->getMessage(),
                'topic' => $this->topic,
            ]);
        }
    }

    /**
     * Consume from Redis Streams (fallback)
     */
    private function consumeFromRedis(ClickHouseService $clickHouse): void
    {
        $streamKey = "bigdata:events:{$this->topic}";
        $consumerGroup = $this->groupId;
        $consumerName = 'consumer-' . getmypid();

        try {
            // Create consumer group if not exists
            try {
                Redis::xgroup('CREATE', $streamKey, $consumerGroup, '0', 'MKSTREAM');
            } catch (\Exception $e) {
                // Group already exists
            }

            $batch = [];
            $messageCount = 0;

            while ($messageCount < $this->maxMessages) {
                $messages = Redis::xreadgroup(
                    'GROUP', $consumerGroup, $consumerName,
                    'COUNT', self::BATCH_SIZE,
                    'BLOCK', self::CONSUME_TIMEOUT_MS,
                    'STREAMS', $streamKey, '>'
                );

                if (empty($messages) || empty($messages[$streamKey])) {
                    // No new messages, try pending
                    $messages = Redis::xreadgroup(
                        'GROUP', $consumerGroup, $consumerName,
                        'COUNT', 10,
                        'STREAMS', $streamKey, '0'
                    );

                    if (empty($messages) || empty($messages[$streamKey])) {
                        break;
                    }
                }

                foreach ($messages[$streamKey] as $messageId => $fields) {
                    $this->processRedisMessage($messageId, $fields, $batch, $clickHouse);
                    $messageCount++;

                    // Acknowledge message
                    Redis::xack($streamKey, $consumerGroup, $messageId);

                    if (count($batch) >= self::BATCH_SIZE) {
                        $this->flushBatch($batch, $clickHouse);
                        $batch = [];
                    }
                }
            }

            if (!empty($batch)) {
                $this->flushBatch($batch, $clickHouse);
            }

        } catch (\Exception $e) {
            Log::error('Redis Streams consumer error', [
                'error' => $e->getMessage(),
                'stream' => $streamKey,
            ]);
        }
    }

    /**
     * Process a REST Proxy record
     */
    private function processRecord(array $record, array &$batch, ClickHouseService $clickHouse): void
    {
        try {
            $value = $record['value'] ?? null;

            if (is_string($value)) {
                $value = json_decode($value, true, 512, JSON_THROW_ON_ERROR);
            }

            if ($value && isset($value['event_type'])) {
                $batch[] = $value;
            }
        } catch (\Exception $e) {
            Log::warning('Failed to process REST Proxy record', [
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Process a Redis Stream message
     */
    private function processRedisMessage(string $messageId, array $fields, array &$batch, ClickHouseService $clickHouse): void
    {
        try {
            $payload = $fields['payload'] ?? null;

            if ($payload) {
                $data = json_decode($payload, true, 512, JSON_THROW_ON_ERROR);

                if ($data && isset($data['event_type'])) {
                    $batch[] = $data;
                }
            }
        } catch (\Exception $e) {
            Log::warning('Failed to process Redis Stream message', [
                'error' => $e->getMessage(),
                'message_id' => $messageId,
            ]);
        }
    }

    /**
     * Flush batch to ClickHouse
     */
    private function flushBatch(array $batch, ClickHouseService $clickHouse): void
    {
        try {
            $clickHouse->insertEventsBatch($batch);
            Log::info('Flushed event batch to ClickHouse', [
                'count' => count($batch),
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to flush batch to ClickHouse', [
                'error' => $e->getMessage(),
                'batch_size' => count($batch),
            ]);

            $this->sendToDLQ($batch, $e->getMessage());
        }
    }

    /**
     * Send failed messages to Dead Letter Queue
     */
    private function sendToDLQ(array $batch, string $error): void
    {
        try {
            $dlqKey = 'bigdata:dlq:events';

            foreach ($batch as $item) {
                Redis::xadd($dlqKey, '*', [
                    'payload' => json_encode($item, JSON_UNESCAPED_UNICODE),
                    'error' => $error,
                    'failed_at' => (string) now()->timestamp,
                ]);
            }

            Log::warning('Sent messages to DLQ', [
                'count' => count($batch),
                'error' => $error,
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to send to DLQ', [
                'error' => $e->getMessage(),
            ]);
        }
    }
}
