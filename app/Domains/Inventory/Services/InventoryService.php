<?php

declare(strict_types=1);

namespace App\Domains\Inventory\Services;

use Carbon\Carbon;

use App\Domains\Inventory\DTOs\CreateAdjustmentDto;
use App\Domains\Inventory\DTOs\CreateReservationDto;
use App\Domains\Inventory\DTOs\CreateStockMovementDto;
use App\Domains\Inventory\Enums\StockMovementType;
use App\Domains\Inventory\Events\StockReleased;
use App\Domains\Inventory\Events\StockReserved;
use App\Domains\Inventory\Events\StockUpdated;
use App\Domains\Inventory\Exceptions\InsufficientStockException;
use App\Domains\Inventory\Models\InventoryItem;
use App\Domains\Inventory\Models\Reservation;
use App\Domains\Inventory\Models\StockMovement;
use App\Services\AuditService;
use App\Services\FraudControlService;
use Illuminate\Database\DatabaseManager;
use Illuminate\Contracts\Events\Dispatcher;
use Psr\Log\LoggerInterface;

/**
 * Главный сервис управления остатками.
 *
 * Все мутации проходят через этот сервис.
 * Гарантирует: fraud-check → DB::transaction → event → audit.
 */
final readonly class InventoryService
{
    public function __construct(
        private DatabaseManager    $db,
        private FraudControlService $fraud,
        private AuditService        $audit,
        private Dispatcher          $events,
        private LoggerInterface     $logger,
    ) {}

    /* ================================================================== */
    /*  Reserve (блокирование товара для корзины/заказа)                  */
    /* ================================================================== */

    public function reserve(CreateReservationDto $dto): Reservation
    {
        $this->fraud->check(
            userId: $dto->tenantId,
            operationType: 'inventory_reserve',
            amount: $dto->quantity,
            correlationId: $dto->correlationId,
        );

        return $this->db->transaction(function () use ($dto): Reservation {
            /** @var InventoryItem $item */
            $item = InventoryItem::where('product_id', $dto->productId)
                ->where('warehouse_id', $dto->warehouseId)
                ->lockForUpdate()
                ->firstOrFail();

            if ($item->available < $dto->quantity) {
                throw new InsufficientStockException(
                    productId: $dto->productId,
                    warehouseId: $dto->warehouseId,
                    requested: $dto->quantity,
                    available: $item->available,
                    correlationId: $dto->correlationId,
                );
            }

            // B2B vs B2C logic
            // B2C - 20 minutes reserve. B2B - 7 days (or payment term) + MOQ checks.
            $isB2B = $dto->businessGroupId !== null;

            if ($isB2B) {
                // Minimum Order Quantity check for B2B
                $moq = (int) ($item->tags['moq'] ?? 5); // Default MOQ for B2B is 5 if not set
                if ($dto->quantity < $moq) {
                    throw new \RuntimeException(sprintf(
                        'Business rules violation: MOQ (Minimum Order Quantity) not met. Requested: %d, Required: %d',
                        $dto->quantity,
                        $moq
                    ));
                }
                $expiresAt = $dto->expiresAt ?? Carbon::now()->addDays(7)->toDateTimeString();
            } else {
                $expiresAt = $dto->expiresAt ?? Carbon::now()->addMinutes(20)->toDateTimeString();
            }

            $item->increment('reserved', $dto->quantity);

            $reservation = Reservation::create([
                'inventory_id'   => $item->id,
                'tenant_id'      => $dto->tenantId,
                'cart_id'        => $dto->cartId,
                'order_id'       => $dto->orderId,
                'quantity'       => $dto->quantity,
                'expires_at'     => $expiresAt,
                'correlation_id' => $dto->correlationId,
            ]);

            StockMovement::create([
                'inventory_id'   => $item->id,
                'warehouse_id'   => $dto->warehouseId,
                'tenant_id'      => $dto->tenantId,
                'type'           => StockMovementType::RESERVE->value,
                'quantity'       => $dto->quantity,
                'source_type'    => $dto->sourceType,
                'source_id'      => $dto->sourceId,
                'correlation_id' => $dto->correlationId,
            ]);

            $this->events->dispatch(new StockReserved(
                productId: $dto->productId,
                warehouseId: $dto->warehouseId,
                quantity: $dto->quantity,
                reservationId: $reservation->id,
                tenantId: $dto->tenantId,
                correlationId: $dto->correlationId,
            ));

            $this->audit->record(
                action: 'inventory_reserved',
                subjectType: 'inventory_item',
                subjectId: $item->id,
                newValues: ['reserved' => $dto->quantity, 'reservation_id' => $reservation->id],
                correlationId: $dto->correlationId,
            );

            return $reservation;
        });
    }

    /* ================================================================== */
    /*  Release (снятие резерва)                                          */
    /* ================================================================== */

    public function releaseReservation(int $reservationId, string $correlationId): void
    {
        $this->db->transaction(function () use ($reservationId, $correlationId): void {
            /** @var Reservation $reservation */
            $reservation = Reservation::findOrFail($reservationId);
            /** @var InventoryItem $item */
            $item = $reservation->inventoryItem()->lockForUpdate()->firstOrFail();

            $item->decrement('reserved', $reservation->quantity);

            StockMovement::create([
                'inventory_id'   => $item->id,
                'warehouse_id'   => $item->warehouse_id,
                'tenant_id'      => $item->tenant_id,
                'type'           => StockMovementType::RELEASE->value,
                'quantity'        => $reservation->quantity,
                'source_type'    => 'reservation',
                'source_id'      => $reservationId,
                'correlation_id' => $correlationId,
            ]);

            $productId   = $item->product_id;
            $warehouseId = $item->warehouse_id;
            $qty         = $reservation->quantity;
            $tenantId    = $item->tenant_id;

            $reservation->delete();

            $this->events->dispatch(new StockReleased(
                productId: $productId,
                warehouseId: $warehouseId,
                quantity: $qty,
                tenantId: $tenantId,
                correlationId: $correlationId,
            ));

            $this->audit->record(
                action: 'reservation_released',
                subjectType: 'reservation',
                subjectId: $reservationId,
                oldValues: ['quantity' => $qty],
                correlationId: $correlationId,
            );
        });
    }

    /* ================================================================== */
    /*  Add stock (приход)                                                */
    /* ================================================================== */

    public function addStock(CreateStockMovementDto $dto): InventoryItem
    {
        $this->fraud->check(
            userId: $dto->tenantId,
            operationType: 'inventory_add',
            amount: $dto->quantity,
            correlationId: $dto->correlationId,
        );

        return $this->db->transaction(function () use ($dto): InventoryItem {
            /** @var InventoryItem $item */
            $item = InventoryItem::where('id', $dto->inventoryId)
                ->lockForUpdate()
                ->firstOrFail();

            $oldQty = $item->quantity;
            $item->increment('quantity', $dto->quantity);
            $item->refresh();

            StockMovement::create([
                'inventory_id'   => $item->id,
                'warehouse_id'   => $dto->warehouseId,
                'tenant_id'      => $dto->tenantId,
                'type'           => StockMovementType::IN->value,
                'quantity'        => $dto->quantity,
                'source_type'    => $dto->sourceType,
                'source_id'      => $dto->sourceId,
                'correlation_id' => $dto->correlationId,
                'metadata'       => $dto->metadata,
            ]);

            $this->events->dispatch(new StockUpdated(
                productId: $item->product_id,
                warehouseId: $dto->warehouseId,
                newQuantity: $item->quantity,
                newReserved: $item->reserved,
                available: $item->available,
                tenantId: $dto->tenantId,
                correlationId: $dto->correlationId,
            ));

            $this->audit->record(
                action: 'stock_added',
                subjectType: 'inventory_item',
                subjectId: $item->id,
                oldValues: ['quantity' => $oldQty],
                newValues: ['quantity' => $item->quantity],
                correlationId: $dto->correlationId,
            );

            return $item;
        });
    }

    /* ================================================================== */
    /*  Deduct stock (списание при отгрузке)                              */
    /* ================================================================== */

    public function deductStock(CreateStockMovementDto $dto): InventoryItem
    {
        $this->fraud->check(
            userId: $dto->tenantId,
            operationType: 'inventory_deduct',
            amount: $dto->quantity,
            correlationId: $dto->correlationId,
        );

        return $this->db->transaction(function () use ($dto): InventoryItem {
            /** @var InventoryItem $item */
            $item = InventoryItem::where('id', $dto->inventoryId)
                ->lockForUpdate()
                ->firstOrFail();

            if ($item->available < $dto->quantity) {
                throw new InsufficientStockException(
                    productId: $item->product_id,
                    warehouseId: $dto->warehouseId,
                    requested: $dto->quantity,
                    available: $item->available,
                    correlationId: $dto->correlationId,
                );
            }

            $oldQty = $item->quantity;
            $item->decrement('quantity', $dto->quantity);
            $item->refresh();

            StockMovement::create([
                'inventory_id'   => $item->id,
                'warehouse_id'   => $dto->warehouseId,
                'tenant_id'      => $dto->tenantId,
                'type'           => StockMovementType::OUT->value,
                'quantity'        => $dto->quantity,
                'source_type'    => $dto->sourceType,
                'source_id'      => $dto->sourceId,
                'correlation_id' => $dto->correlationId,
                'metadata'       => $dto->metadata,
            ]);

            $this->events->dispatch(new StockUpdated(
                productId: $item->product_id,
                warehouseId: $dto->warehouseId,
                newQuantity: $item->quantity,
                newReserved: $item->reserved,
                available: $item->available,
                tenantId: $dto->tenantId,
                correlationId: $dto->correlationId,
            ));

            $this->audit->record(
                action: 'stock_deducted',
                subjectType: 'inventory_item',
                subjectId: $item->id,
                oldValues: ['quantity' => $oldQty],
                newValues: ['quantity' => $item->quantity],
                correlationId: $dto->correlationId,
            );

            return $item;
        });
    }

    /* ================================================================== */
    /*  Adjust (ручная корректировка при инвентаризации)                  */
    /* ================================================================== */

    public function adjust(CreateAdjustmentDto $dto): InventoryItem
    {
        $this->fraud->check(
            userId: $dto->tenantId,
            operationType: 'inventory_adjust',
            amount: abs($dto->newQuantity),
            correlationId: $dto->correlationId,
        );

        return $this->db->transaction(function () use ($dto): InventoryItem {
            /** @var InventoryItem $item */
            $item = InventoryItem::where('product_id', $dto->productId)
                ->where('warehouse_id', $dto->warehouseId)
                ->lockForUpdate()
                ->firstOrFail();

            $oldQty    = $item->quantity;
            $diff      = $dto->newQuantity - $oldQty;
            $item->quantity = $dto->newQuantity;
            $item->save();

            StockMovement::create([
                'inventory_id'   => $item->id,
                'warehouse_id'   => $dto->warehouseId,
                'tenant_id'      => $dto->tenantId,
                'type'           => StockMovementType::ADJUSTMENT->value,
                'quantity'        => abs($diff),
                'source_type'    => 'adjustment',
                'source_id'      => $dto->employeeId,
                'correlation_id' => $dto->correlationId,
                'metadata'       => ['reason' => $dto->reason, 'direction' => $diff >= 0 ? 'increase' : 'decrease'],
            ]);

            $this->events->dispatch(new StockUpdated(
                productId: $dto->productId,
                warehouseId: $dto->warehouseId,
                newQuantity: $item->quantity,
                newReserved: $item->reserved,
                available: $item->available,
                tenantId: $dto->tenantId,
                correlationId: $dto->correlationId,
            ));

            $this->audit->record(
                action: 'stock_adjusted',
                subjectType: 'inventory_item',
                subjectId: $item->id,
                oldValues: ['quantity' => $oldQty],
                newValues: ['quantity' => $dto->newQuantity, 'reason' => $dto->reason],
                correlationId: $dto->correlationId,
            );

            return $item;
        });
    }

    /* ================================================================== */
    /*  Confirm shipment (финальное списание после отгрузки)              */
    /* ================================================================== */

    public function confirmShipment(int $orderId, string $correlationId): void
    {
        $this->db->transaction(function () use ($orderId, $correlationId): void {
            $reservations = Reservation::where('order_id', $orderId)->get();

            foreach ($reservations as $reservation) {
                /** @var InventoryItem $item */
                $item = $reservation->inventoryItem()->lockForUpdate()->firstOrFail();

                $oldQty = $item->quantity;
                $item->decrement('quantity', $reservation->quantity);
                $item->decrement('reserved', $reservation->quantity);
                $item->refresh();

                StockMovement::create([
                    'inventory_id'   => $item->id,
                    'warehouse_id'   => $item->warehouse_id,
                    'tenant_id'      => $item->tenant_id,
                    'type'           => StockMovementType::OUT->value,
                    'quantity'        => $reservation->quantity,
                    'source_type'    => 'order',
                    'source_id'      => $orderId,
                    'correlation_id' => $correlationId,
                ]);

                $this->events->dispatch(new StockUpdated(
                    productId: $item->product_id,
                    warehouseId: $item->warehouse_id,
                    newQuantity: $item->quantity,
                    newReserved: $item->reserved,
                    available: $item->available,
                    tenantId: $item->tenant_id,
                    correlationId: $correlationId,
                ));

                $reservation->delete();
            }

            $this->audit->record(
                action: 'shipment_confirmed',
                subjectType: 'order',
                subjectId: $orderId,
                newValues: ['reservations_cleared' => $reservations->count()],
                correlationId: $correlationId,
            );
        });
    }

    /* ================================================================== */
    /*  Read-only helpers                                                  */
    /* ================================================================== */

    public function getAvailableStock(int $productId, ?int $warehouseId = null): int
    {
        $query = InventoryItem::where('product_id', $productId);

        if ($warehouseId !== null) {
            $query->where('warehouse_id', $warehouseId);
        }

        return (int) $query->sum($this->db->raw('quantity - reserved'));
    }
}
