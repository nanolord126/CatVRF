<?php

declare(strict_types=1);

namespace App\Domains\VerticalName\Events;

use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * Событие: VerticalItem удалён (soft delete).
 *
 * CANON 2026 — Layer 4: Events.
 * Используется для: инвалидация кэша, уведомление подписчиков, ML-обновление.
 *
 * @package App\Domains\VerticalName\Events
 */
final class VerticalItemDeletedEvent
{
    use Dispatchable;
    use InteractsWithSockets;
    use SerializesModels;

    /**
     * @param int    $itemId        ID удалённого товара
     * @param string $correlationId ID для трассировки
     * @param int    $tenantId      ID тенанта
     */
    public function __construct(
        public readonly int $itemId,
        public readonly string $correlationId,
        public readonly int $tenantId,
    ) {
    }

    /**
     * Данные для логирования.
     */
    public function toLogContext(): array
    {
        return [
            'event' => 'vertical_name_item_deleted',
            'item_id' => $this->itemId,
            'tenant_id' => $this->tenantId,
            'correlation_id' => $this->correlationId,
        ];
    }
}
