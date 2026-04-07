<?php

declare(strict_types=1);

namespace App\Domains\Inventory\Exceptions;

use RuntimeException;

/**
 * Бросается когда доступного остатка недостаточно для операции.
 *
 * Содержит product_id, warehouse_id и запрошенное/доступное количество
 * для диагностики и отображения пользователю.
 */
final class InsufficientStockException extends RuntimeException
{
    public function __construct(
        private readonly int    $productId,
        private readonly int    $warehouseId,
        private readonly int    $requested,
        private readonly int    $available,
        private readonly string $correlationId,
    ) {
        parent::__construct(
            sprintf(
                'Insufficient stock for product %d at warehouse %d: requested %d, available %d [%s]',
                $this->productId,
                $this->warehouseId,
                $this->requested,
                $this->available,
                $this->correlationId,
            ),
        );
    }

    public function getProductId(): int
    {
        return $this->productId;
    }

    public function getWarehouseId(): int
    {
        return $this->warehouseId;
    }

    public function getRequested(): int
    {
        return $this->requested;
    }

    public function getAvailable(): int
    {
        return $this->available;
    }

    public function getCorrelationId(): string
    {
        return $this->correlationId;
    }

    /** @return array<string, mixed> */
    public function context(): array
    {
        return [
            'product_id'     => $this->productId,
            'warehouse_id'   => $this->warehouseId,
            'requested'      => $this->requested,
            'available'      => $this->available,
            'correlation_id' => $this->correlationId,
        ];
    }
}
