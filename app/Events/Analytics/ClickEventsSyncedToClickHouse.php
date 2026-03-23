<?php

declare(strict_types=1);

namespace App\Events\Analytics;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Str;

/**
 * Событие: Click события синхронизированы в ClickHouse
 * 
 * Транслируется после SyncClickEventsToClickHouseJob завершён
 * Слушатели: TimeSeriesChartComponent (Livewire)
 */
final class ClickEventsSyncedToClickHouse implements ShouldBroadcast
{
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
        return 'click-events-synced';
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
            'message' => 'Новые данные по кликам доступны в аналитике',
        ];
    }
}
