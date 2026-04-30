<?php

declare(strict_types=1);

namespace App\Domains\Supermarket\Events;

/**
 * InventoryUpdated - событие обновления инвентаря в Supermarket.
 *
 * Триггерится при изменении остатков товаров (резервирование, подтверждение, отмена).
 * Используется для инвалидации кэша, обновления поискового индекса и аналитики.
 */
final class InventoryUpdated
{
    /**
     * @param  string  $productId ID продукта
     * @param  int  $delta Изменение количества (отрицательное для списания, положительное для добавления)
     * @param  string  $reason Причина изменения (order_confirmed, order_cancelled, manual, reservation_released)
     * @param  string|null  $orderId ID заказа (если применимо)
     * @param  string|null  $correlationId Correlation ID для трассировки
     */
    public function __construct(
        public readonly string $productId,
        public readonly int $delta,
        public readonly string $reason,
        public readonly ?string $orderId = null,
        public readonly ?string $correlationId = null,
    ) {}
}
