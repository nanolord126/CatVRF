<?php

declare(strict_types=1);

namespace App\Domains\Shared\CRM\Events;

use App\Domains\Supermarket\Models\SupermarketOrder;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * OrderCreated - Событие создания заказа для синхронизации с CRM.
 *
 * Запускается после успешного создания заказа в любой вертикали.
 * Диспетчеризируется через CRMEventDispatcher для отправки в CRM.
 */
final class OrderCreated
{
    use Dispatchable, SerializesModels;

    /**
     * @param array{order_id: int, external_id: string|null, buyer_id: int, buyer_phone: string, buyer_email: string|null, total_amount: int, sub_vertical: string|null, is_b2b: bool, items_count: int, vertical: string, created_at: string, correlation_id: string} $data
     */
    public function __construct(
        public readonly array $data
    ) {}

    /**
     * Создать событие из модели SupermarketOrder.
     */
    public static function fromOrder(SupermarketOrder $order): self
    {
        return new self([
            'event' => 'order.created',
            'order_id' => $order->id,
            'external_id' => $order->uuid,
            'buyer_id' => $order->user_id,
            'buyer_phone' => $order->buyer->phone ?? '',
            'buyer_email' => $order->buyer->email ?? null,
            'total_amount' => $order->total_amount,
            'delivery_cost' => $order->delivery_cost ?? 0,
            'sub_vertical' => $order->sub_vertical,
            'vertical' => 'supermarket',
            'is_b2b' => $order->is_b2b ?? false,
            'b2b_company_id' => $order->b2b_company_id ?? null,
            'items_count' => count($order->items ?? []),
            'delivery_address' => $order->delivery_address,
            'delivery_slot' => $order->delivery_slot,
            'cold_chain_required' => $order->cold_chain_required ?? false,
            'payment_id' => $order->payment_id,
            'created_at' => $order->created_at->toIso8601String(),
            'correlation_id' => $order->correlation_id ?? self::generateCorrelationId(),
        ]);
    }

    /**
     * Создать событие из массива данных.
     */
    public static function fromArray(array $data): self
    {
        return new self(array_merge([
            'event' => 'order.created',
            'correlation_id' => self::generateCorrelationId(),
        ], $data));
    }

    private static function generateCorrelationId(): string
    {
        return 'crm_' . uniqid() . '_' . bin2hex(random_bytes(4));
    }
}
