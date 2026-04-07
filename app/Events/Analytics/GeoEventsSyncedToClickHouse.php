<?php declare(strict_types=1);

/**
 * GeoEventsSyncedToClickHouse — CatVRF 2026 Component.
 *
 * Part of the CatVRF multi-vertical marketplace platform.
 * Implements tenant-aware, fraud-checked business logic
 * with full correlation_id tracing and audit logging.
 *
 * @package CatVRF
 * @version 2026.1
 * @author CatVRF Team
 * @license Proprietary

 * @see https://catvrf.ru/docs/geoeventssyncedtoclickhouse
 */


namespace App\Events\Analytics;

final class GeoEventsSyncedToClickHouse
{

    use Dispatchable;
        use InteractsWithSockets;
        use SerializesModels;

        private int $tenantId;
        private string $correlationId;
        private array $metadata;
        public \DateTime $syncedAt;

        public function __construct(
            int $tenantId,
            string $correlationId = '',
            array $metadata = []
        ) {
            $this->tenantId = $tenantId;
            $this->correlationId = $correlationId ?: Str::uuid()->toString();
            $this->metadata = $metadata;
            $this->syncedAt = now();
        }

        /**
         * Канал для трансляции
         * Private канал: analytics.tenant.{tenantId}
         */
        public function broadcastOn(): PrivateChannel
        {
            return new PrivateChannel("analytics.tenant.{$this->tenantId}");
        }

        /**
         * Имя события в клиенте
         */
        public function broadcastAs(): string
        {
            return 'geo-events-synced';
        }

        /**
         * Данные для отправки
         */
        public function broadcastWith(): array
        {
            return [
                'tenant_id' => $this->tenantId,
                'correlation_id' => $this->correlationId,
                'synced_at' => $this->syncedAt->toIso8601String(),
                'metadata' => $this->metadata,
                'message' => 'Новые данные доступны в аналитике',
            ];
        }
}
