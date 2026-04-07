<?php

declare(strict_types=1);

namespace App\Domains\VerticalName\Events;

use App\Domains\VerticalName\Models\VerticalItem;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * Событие: VerticalItem обновлён.
 *
 * CANON 2026 — Layer 4: Events.
 * Содержит correlation_id, tenant_id и список изменённых полей.
 *
 * @package App\Domains\VerticalName\Events
 */
final class VerticalItemUpdatedEvent
{
    use Dispatchable;
    use InteractsWithSockets;
    use SerializesModels;

    /**
     * @param VerticalItem $item          Обновлённый товар
     * @param string       $correlationId ID для трассировки
     * @param int          $tenantId      ID тенанта
     * @param array        $changedFields Список изменённых полей
     */
    public function __construct(
        public readonly VerticalItem $item,
        public readonly string $correlationId,
        public readonly int $tenantId,
        public readonly array $changedFields = [],
    ) {
    }

    /**
     * Данные для логирования.
     */
    public function toLogContext(): array
    {
        return [
            'event' => 'vertical_name_item_updated',
            'item_id' => $this->item->id,
            'item_uuid' => $this->item->uuid,
            'tenant_id' => $this->tenantId,
            'changed_fields' => $this->changedFields,
            'correlation_id' => $this->correlationId,
        ];
    }
}
