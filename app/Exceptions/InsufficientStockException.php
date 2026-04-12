<?php declare(strict_types=1);

namespace App\Exceptions;

use RuntimeException;
use Throwable;

/**
 * InsufficientStockException
 *
 * Выбрасывается при попытке зарезервировать или списать товар,
 * которого нет в нужном количестве на складе.
 *
 * Канон CatVRF 2026 — PRODUCTION MANDATORY.
 * Использовать везде вместо generic Exception при нехватке остатков.
 *
 * @see InventoryService::reserve()
 * @see CartService::addItem()
 */
final class InsufficientStockException extends RuntimeException
{
    private readonly int    $productId;
    private readonly int    $warehouseId;
    private readonly int    $requested;
    private readonly int    $available;
    private readonly string $correlationId;

    public function __construct(
        int       $productId,
        int       $warehouseId,
        int       $requested,
        int       $available,
        string    $correlationId = '',
        ?Throwable $previous      = null,
    ) {
        $this->productId     = $productId;
        $this->warehouseId   = $warehouseId;
        $this->requested     = $requested;
        $this->available     = $available;
        $this->correlationId = $correlationId ?: \Illuminate\Support\Str::uuid()->toString();

        parent::__construct(
            sprintf(
                'Insufficient stock: product=%d warehouse=%d requested=%d available=%d correlation_id=%s',
                $productId,
                $warehouseId,
                $requested,
                $available,
                $this->correlationId,
            ),
            422,
            $previous,
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

    public function toArray(): array
    {
        return [
            'exception'      => self::class,
            'product_id'     => $this->productId,
            'warehouse_id'   => $this->warehouseId,
            'requested'      => $this->requested,
            'available'      => $this->available,
            'correlation_id' => $this->correlationId,
            'message'        => $this->getMessage(),
        ];
    }
}
