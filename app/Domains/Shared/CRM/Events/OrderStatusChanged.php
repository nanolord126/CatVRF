<?php

declare(strict_types=1);

namespace App\Domains\Shared\CRM\Events;

use App\Domains\Supermarket\Models\SupermarketOrder;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * OrderStatusChanged - Событие изменения статуса заказа для синхронизации с CRM.
 *
 * Запускается при изменении статуса заказа в любой вертикали.
 * Диспетчеризируется через CRMEventDispatcher для отправки в CRM.
 */
final class OrderStatusChanged
{
    use Dispatchable, SerializesModels;

    /**
     * @param array{order_id: int, external_id: string|null, old_status: string, new_status: string, buyer_id: int, vertical: string, changed_at: string, correlation_id: string} $data
     */
    public function __construct(
        public readonly array $data
    ) {}

    /**
     * Создать событие из модели SupermarketOrder.
     */
    public static function fromOrder(SupermarketOrder $order, string $oldStatus): self
    {
        return new self([
            'event' => 'order.status_changed',
            'order_id' => $order->id,
            'external_id' => $order->uuid,
            'old_status' => $oldStatus,
            'new_status' => $order->status,
            'buyer_id' => $order->user_id,
            'vertical' => 'supermarket',
            'sub_vertical' => $order->sub_vertical,
            'is_b2b' => $order->is_b2b ?? false,
            'changed_at' => $order->updated_at->toIso8601String(),
            'correlation_id' => $order->correlation_id ?? self::generateCorrelationId(),
        ]);
    }

    /**
     * Создать событие из массива данных.
     */
    public static function fromArray(array $data): self
    {
        return new self(array_merge([
            'event' => 'order.status_changed',
            'correlation_id' => self::generateCorrelationId(),
        ], $data));
    }

    private static function generateCorrelationId(): string
    {
        return 'crm_' . uniqid() . '_' . bin2hex(random_bytes(4));
    }
}
