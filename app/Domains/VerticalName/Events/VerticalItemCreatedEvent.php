<?php

declare(strict_types=1);

namespace App\Domains\VerticalName\Events;

use App\Domains\VerticalName\Models\VerticalItem;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * Событие: VerticalItem создан.
 *
 * CANON 2026 — Layer 4: Events.
 * Все события содержат correlation_id и tenant_id для traceability.
 * Слушатели обрабатывают пост-логику: уведомления, ML-обновления, кэш-инвалидация.
 *
 * @package App\Domains\VerticalName\Events
 */
final class VerticalItemCreatedEvent
{
    use Dispatchable;
    use InteractsWithSockets;
    use SerializesModels;

    /**
     * @param VerticalItem $item          Созданный товар
     * @param string       $correlationId ID для сквозной трассировки
     * @param int          $tenantId      ID тенанта
     * @param bool         $isB2B         Был ли создан через B2B-поток
     */
    public function __construct(
        public readonly VerticalItem $item,
        public readonly string $correlationId,
        public readonly int $tenantId,
        public readonly bool $isB2B = false,
    ) {
    }

    /**
     * Получить массив данных для логирования/аудита.
     */
    public function toLogContext(): array
    {
        return [
            'event' => 'vertical_name_item_created',
            'item_id' => $this->item->id,
            'item_uuid' => $this->item->uuid,
            'tenant_id' => $this->tenantId,
            'is_b2b' => $this->isB2B,
            'correlation_id' => $this->correlationId,
        ];
    }
}
