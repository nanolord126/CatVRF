<?php declare(strict_types=1);

namespace App\Events\Analytics;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

final class GeoEventsSyncedToClickHouse extends Model
{
    use HasFactory;

    // TODO: Проверить и восстановить содержимое класса, если оно было утеряно
    use Dispatchable;
        use InteractsWithSockets;
        use SerializesModels;

        public int $tenantId;
        public string $correlationId;
        public array $metadata;
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
