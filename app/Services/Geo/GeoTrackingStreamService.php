<?php declare(strict_types=1);

namespace App\Services\Geo;

use Illuminate\Contracts\Config\Repository as ConfigRepository;
use Illuminate\Contracts\Redis\Factory as RedisFactory;
use Illuminate\Log\LogManager;

/**
 * Geolocation Tracking Stream Service
 * 
 * Uses Redis Streams for guaranteed delivery of location updates
 * Prevents race conditions and ensures ordering
 */
final readonly class GeoTrackingStreamService
{
    private const STREAM_KEY = 'geo:tracking:stream';
    private const CONSUMER_GROUP = 'geo_tracking_consumers';
    private const MAX_LENGTH = 10000;

    public function __construct(
        private readonly RedisFactory $redis,
        private readonly ConfigRepository $config,
        private readonly LogManager $logger,
    ) {}

    /**
     * Add location update to stream
     */
    public function addLocationUpdate(
        int $entityId,
        string $entityType, // courier, doctor, patient, vehicle
        float $lat,
        float $lon,
        float $speed = 0.0,
        float $bearing = 0.0,
        ?string $correlationId = null,
    ): string {
        $correlationId ??= \Illuminate\Support\Str::uuid()->toString();
        
        $data = [
            'entity_id' => $entityId,
            'entity_type' => $entityType,
            'lat' => $lat,
            'lon' => $lon,
            'speed' => $speed,
            'bearing' => $bearing,
            'correlation_id' => $correlationId,
            'timestamp' => now()->toIso8601String(),
            'ip_address' => request()->ip(),
        ];

        $messageId = $this->redis->connection()->xadd(
            self::STREAM_KEY,
            '*',
            $data,
            self::MAX_LENGTH
        );

        $this->logger->channel('geo')->debug('Location update added to stream', [
            'message_id' => $messageId,
            'entity_id' => $entityId,
            'entity_type' => $entityType,
            'correlation_id' => $correlationId,
        ]);

        return $messageId;
    }

    /**
     * Read location updates for entity
     */
    public function readEntityUpdates(
        int $entityId,
        string $entityType,
        int $count = 50,
    ): array {
        // Read from stream and filter by entity
        $messages = $this->redis->connection()->xrange(
            self::STREAM_KEY,
            '-',
            '+',
            $count
        );

        $updates = [];
        foreach ($messages as $id => $data) {
            if (($data['entity_id'] ?? null) == $entityId && ($data['entity_type'] ?? null) == $entityType) {
                $updates[] = [
                    'message_id' => $id,
                    'lat' => (float) $data['lat'],
                    'lon' => (float) $data['lon'],
                    'speed' => (float) ($data['speed'] ?? 0),
                    'bearing' => (float) ($data['bearing'] ?? 0),
                    'timestamp' => $data['timestamp'],
                    'correlation_id' => $data['correlation_id'] ?? null,
                ];
            }
        }

        return array_reverse($updates); // Most recent first
    }

    /**
     * Create consumer group if not exists
     */
    public function createConsumerGroup(string $consumerName = 'default'): void
    {
        try {
            $this->redis->connection()->xgroup(
                'CREATE',
                self::STREAM_KEY,
                self::CONSUMER_GROUP,
                '0',
                true // MKSTREAM
            );

            $this->logger->channel('geo')->info('Geo tracking consumer group created', [
                'consumer_group' => self::CONSUMER_GROUP,
            ]);
        } catch (\Exception $e) {
            // Group already exists
            $this->logger->channel('geo')->debug('Consumer group already exists', [
                'error' => $e->getMessage(),
            ]);
        }

        // Create consumer
        try {
            $this->redis->connection()->xgroup(
                'CREATECONSUMER',
                self::STREAM_KEY,
                self::CONSUMER_GROUP,
                $consumerName
            );
        } catch (\Exception $e) {
            // Consumer already exists
            $this->logger->channel('geo')->debug('Consumer already exists', [
                'consumer' => $consumerName,
            ]);
        }
    }

    /**
     * Read from consumer group (for background workers)
     */
    public function readFromConsumerGroup(
        string $consumerName,
        int $count = 10,
        int $blockMs = 2000,
    ): array {
        try {
            $messages = $this->redis->connection()->xreadgroup(
                self::CONSUMER_GROUP,
                $consumerName,
                ['streams' => [self::STREAM_KEY => '>']],
                $count,
                $blockMs
            );

            return $messages[self::STREAM_KEY] ?? [];
        } catch (\Exception $e) {
            $this->logger->channel('geo')->error('Failed to read from consumer group', [
                'error' => $e->getMessage(),
                'consumer' => $consumerName,
            ]);

            return [];
        }
    }

    /**
     * Acknowledge message processing
     */
    public function acknowledgeMessage(string $messageId): bool
    {
        try {
            $this->redis->connection()->xack(
                self::STREAM_KEY,
                self::CONSUMER_GROUP,
                [$messageId]
            );

            return true;
        } catch (\Exception $e) {
            $this->logger->channel('geo')->error('Failed to acknowledge message', [
                'error' => $e->getMessage(),
                'message_id' => $messageId,
            ]);

            return false;
        }
    }

    /**
     * Get stream info
     */
    public function getStreamInfo(): array
    {
        try {
            $info = $this->redis->connection()->xinfo('STREAM', self::STREAM_KEY);
            
            return [
                'length' => $info['length'] ?? 0,
                'groups' => $info['groups'] ?? 0,
                'first_entry' => $info['first-entry'] ?? null,
                'last_entry' => $info['last-entry'] ?? null,
            ];
        } catch (\Exception $e) {
            return [
                'length' => 0,
                'groups' => 0,
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Trim stream to max length
     */
    public function trimStream(int $maxLength = null): void
    {
        $maxLength = $maxLength ?? self::MAX_LENGTH;
        
        $this->redis->connection()->xtrim(
            self::STREAM_KEY,
            'MAXLEN',
            '~',
            $maxLength
        );

        $this->logger->channel('geo')->info('Geo tracking stream trimmed', [
            'max_length' => $maxLength,
        ]);
    }

    /**
     * Delete old messages (cleanup job)
     */
    public function deleteOldMessages(int $hoursOld = 24): int
    {
        $cutoffTime = now()->subHours($hoursOld)->timestamp * 1000; // Redis uses milliseconds
        
        $messages = $this->redis->connection()->xrange(
            self::STREAM_KEY,
            '-',
            $cutoffTime,
            1000
        );

        if (empty($messages)) {
            return 0;
        }

        $ids = array_keys($messages);
        $deleted = $this->redis->connection()->xdel(self::STREAM_KEY, $ids);

        $this->logger->channel('geo')->info('Old geo tracking messages deleted', [
            'count' => $deleted,
            'hours_old' => $hoursOld,
        ]);

        return $deleted;
    }
}
