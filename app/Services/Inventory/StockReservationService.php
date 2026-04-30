<?php

declare(strict_types=1);

namespace App\Services\Inventory;

use App\Services\Security\AuditService;
use App\Services\FraudControl\FraudControlService;
use App\Traits\WithAuditLogging;
use Illuminate\Database\DatabaseManager;
use Illuminate\Support\Facades\Redis;
use Illuminate\Support\Str;
use Psr\Log\LoggerInterface;

/**
 * Stock Reservation Service
 *
 * Manages stock reservations with Redis-backed atomic operations:
 * - Hold stock for orders (with TTL)
 * - Release reservations on order cancellation
 * - Auto-expire stale reservations
 * - Prevent over-reservation
 *
 * @author CatVRF Team
 * @version 2026.04.28
 */
final readonly class StockReservationService
{
    use WithAuditLogging;

    private const RESERVATION_TTL = 1800; // 30 minutes
    private const RESERVATION_PREFIX = 'stock_reservation:';

    public function __construct(
        private readonly DatabaseManager $db,
        private readonly LoggerInterface $logger,
        private readonly AuditService $auditService,
        private readonly FraudControlService $fraudService,
    ) {}

    /**
     * Reserve stock for order
     *
     * @param  int  $inventoryItemId  Inventory item ID
     * @param  int  $quantity  Quantity to reserve
     * @param  string  $orderType  Order type (sales_order, transfer_order, etc.)
     * @param  int  $orderId  Order ID
     * @param  int  $userId  User ID
     * @param  int  $tenantId  Tenant ID
     * @param  int|null  $ttl  TTL in seconds (default: 1800)
     * @return string Reservation ID
     */
    public function reserveStock(
        int $inventoryItemId,
        int $quantity,
        string $orderType,
        int $orderId,
        int $userId,
        int $tenantId,
        ?int $ttl = null
    ): string {
        $reservationId = Str::uuid()->toString();
        $ttl ??= self::RESERVATION_TTL;
        $correlationId = Str::uuid()->toString();

        // Fraud check for large reservations
        $item = $this->db->table('inventory_items')
            ->where('id', $inventoryItemId)
            ->first();

        if ($item && $quantity > ($item->current_stock * 0.5)) {
            $this->fraudService->check([
                'operation_type' => 'large_stock_reservation',
                'inventory_item_id' => $inventoryItemId,
                'quantity' => $quantity,
                'order_type' => $orderType,
                'order_id' => $orderId,
                'user_id' => $userId,
                'tenant_id' => $tenantId,
                'correlation_id' => $correlationId,
            ]);
        }

        return $this->db->transaction(function () use (
            $inventoryItemId,
            $quantity,
            $orderType,
            $orderId,
            $userId,
            $tenantId,
            $reservationId,
            $ttl,
            $correlationId
        ) {
            $item = $this->db->table('inventory_items')
                ->where('id', $inventoryItemId)
                ->lockForUpdate()
                ->first();

            if (! $item) {
                throw new \RuntimeException("Inventory item {$inventoryItemId} not found");
            }

            $availableForReservation = $item->current_stock - $item->reserved_stock;

            if ($availableForReservation < $quantity) {
                throw new \RuntimeException(
                    "Insufficient stock for reservation. Available: {$availableForReservation}, Requested: {$quantity}"
                );
            }

            // Update database
            $this->db->table('inventory_items')
                ->where('id', $inventoryItemId)
                ->increment('reserved_stock', $quantity);

            $this->db->table('stock_reservations')->insert([
                'uuid' => Str::uuid()->toString(),
                'reservation_id' => $reservationId,
                'inventory_item_id' => $inventoryItemId,
                'quantity' => $quantity,
                'order_type' => $orderType,
                'order_id' => $orderId,
                'status' => 'active',
                'expires_at' => now()->addSeconds($ttl),
                'tenant_id' => $tenantId,
                'created_by' => $userId,
                'created_at' => now(),
            ]);

            // Set Redis with TTL for auto-expiration
            $redisKey = self::RESERVATION_PREFIX.$reservationId;
            Redis::setex($redisKey, $ttl, json_encode([
                'inventory_item_id' => $inventoryItemId,
                'quantity' => $quantity,
                'order_type' => $orderType,
                'order_id' => $orderId,
                'tenant_id' => $tenantId,
            ]));

            $this->logAction(
                action: 'stock_reserved',
                entityType: 'StockReservation',
                entityId: $reservationId,
                context: [
                    'correlation_id' => $correlationId,
                    'inventory_item_id' => $inventoryItemId,
                    'quantity' => $quantity,
                    'order_type' => $orderType,
                    'order_id' => $orderId,
                    'ttl' => $ttl,
                ],
                userId: $userId,
                tenantId: $tenantId
            );

            return $reservationId;
        });
    }

    /**
     * Release reservation
     *
     * @param  string  $reservationId  Reservation ID
     * @param  int  $userId  User ID
     * @param  int  $tenantId  Tenant ID
     * @return bool Success
     */
    public function releaseReservation(string $reservationId, int $userId, int $tenantId): bool
    {
        $correlationId = Str::uuid()->toString();

        return $this->db->transaction(function () use ($reservationId, $userId, $tenantId, $correlationId) {
            $reservation = $this->db->table('stock_reservations')
                ->where('reservation_id', $reservationId)
                ->where('status', 'active')
                ->lockForUpdate()
                ->first();

            if (! $reservation) {
                throw new \RuntimeException("Active reservation {$reservationId} not found");
            }

            // Release stock in database
            $this->db->table('inventory_items')
                ->where('id', $reservation->inventory_item_id)
                ->decrement('reserved_stock', $reservation->quantity);

            // Update reservation status
            $this->db->table('stock_reservations')
                ->where('id', $reservation->id)
                ->update([
                    'status' => 'released',
                    'released_at' => now(),
                    'released_by' => $userId,
                ]);

            // Remove from Redis
            $redisKey = self::RESERVATION_PREFIX.$reservationId;
            Redis::del($redisKey);

            $this->logAction(
                action: 'reservation_released',
                entityType: 'StockReservation',
                entityId: $reservationId,
                context: [
                    'correlation_id' => $correlationId,
                    'inventory_item_id' => $reservation->inventory_item_id,
                    'quantity' => $reservation->quantity,
                    'order_type' => $reservation->order_type,
                    'order_id' => $reservation->order_id,
                ],
                userId: $userId,
                tenantId: $tenantId
            );

            return true;
        });
    }

    /**
     * Confirm reservation (convert to actual stock movement)
     *
     * @param  string  $reservationId  Reservation ID
     * @param  string  $movementType  Movement type (out, transfer, etc.)
     * @param  string  $reason  Reason for movement
     * @param  int  $userId  User ID
     * @param  int  $tenantId  Tenant ID
     * @return bool Success
     */
    public function confirmReservation(
        string $reservationId,
        string $movementType,
        string $reason,
        int $userId,
        int $tenantId
    ): bool {
        $correlationId = Str::uuid()->toString();

        return $this->db->transaction(function () use (
            $reservationId,
            $movementType,
            $reason,
            $userId,
            $tenantId,
            $correlationId
        ) {
            $reservation = $this->db->table('stock_reservations')
                ->where('reservation_id', $reservationId)
                ->where('status', 'active')
                ->lockForUpdate()
                ->first();

            if (! $reservation) {
                throw new \RuntimeException("Active reservation {$reservationId} not found");
            }

            // Deduct from current stock
            $this->db->table('inventory_items')
                ->where('id', $reservation->inventory_item_id)
                ->decrement('current_stock', $reservation->quantity);

            // Release reserved stock
            $this->db->table('inventory_items')
                ->where('id', $reservation->inventory_item_id)
                ->decrement('reserved_stock', $reservation->quantity);

            // Create stock movement
            $this->db->table('stock_movements')->insert([
                'uuid' => Str::uuid()->toString(),
                'correlation_id' => $correlationId,
                'inventory_item_id' => $reservation->inventory_item_id,
                'type' => $movementType,
                'quantity' => -$reservation->quantity,
                'reason' => $reason,
                'source_type' => $reservation->order_type,
                'source_id' => $reservation->order_id,
                'created_by' => $userId,
                'created_at' => now(),
            ]);

            // Update reservation status
            $this->db->table('stock_reservations')
                ->where('id', $reservation->id)
                ->update([
                    'status' => 'confirmed',
                    'confirmed_at' => now(),
                    'confirmed_by' => $userId,
                ]);

            // Remove from Redis
            $redisKey = self::RESERVATION_PREFIX.$reservationId;
            Redis::del($redisKey);

            $this->logAction(
                action: 'reservation_confirmed',
                entityType: 'StockReservation',
                entityId: $reservationId,
                context: [
                    'correlation_id' => $correlationId,
                    'inventory_item_id' => $reservation->inventory_item_id,
                    'quantity' => $reservation->quantity,
                    'movement_type' => $movementType,
                ],
                userId: $userId,
                tenantId: $tenantId
            );

            return true;
        });
    }

    /**
     * Get active reservations for item
     *
     * @param  int  $inventoryItemId  Inventory item ID
     * @return array Active reservations
     */
    public function getActiveReservations(int $inventoryItemId): array
    {
        return $this->db->table('stock_reservations')
            ->where('inventory_item_id', $inventoryItemId)
            ->where('status', 'active')
            ->where('expires_at', '>', now())
            ->get()
            ->map(fn ($r) => [
                'reservation_id' => $r->reservation_id,
                'quantity' => $r->quantity,
                'order_type' => $r->order_type,
                'order_id' => $r->order_id,
                'expires_at' => $r->expires_at->toIso8601String(),
                'created_at' => $r->created_at->toIso8601String(),
            ])
            ->toArray();
    }

    /**
     * Get total reserved quantity for item
     *
     * @param  int  $inventoryItemId  Inventory item ID
     * @return int Total reserved quantity
     */
    public function getTotalReservedQuantity(int $inventoryItemId): int
    {
        return (int) $this->db->table('stock_reservations')
            ->where('inventory_item_id', $inventoryItemId)
            ->where('status', 'active')
            ->where('expires_at', '>', now())
            ->sum('quantity');
    }

    /**
     * Clean up expired reservations
     *
     * @param  int  $userId  User ID (system user)
     * @param  int  $tenantId  Tenant ID
     * @return int Number of reservations cleaned up
     */
    public function cleanupExpiredReservations(int $userId, int $tenantId): int
    {
        $expiredReservations = $this->db->table('stock_reservations')
            ->where('status', 'active')
            ->where('expires_at', '<=', now())
            ->get();

        $cleanedCount = 0;

        foreach ($expiredReservations as $reservation) {
            try {
                $this->releaseReservation($reservation->reservation_id, $userId, $tenantId);
                $cleanedCount++;
            } catch (\Exception $e) {
                $this->logger->error('Failed to release expired reservation', [
                    'reservation_id' => $reservation->reservation_id,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        return $cleanedCount;
    }

    /**
     * Extend reservation TTL
     *
     * @param  string  $reservationId  Reservation ID
     * @param  int  $additionalSeconds  Additional seconds to add
     * @param  int  $userId  User ID
     * @param  int  $tenantId  Tenant ID
     * @return bool Success
     */
    public function extendReservationTTL(
        string $reservationId,
        int $additionalSeconds,
        int $userId,
        int $tenantId
    ): bool {
        $correlationId = Str::uuid()->toString();

        return $this->db->transaction(function () use (
            $reservationId,
            $additionalSeconds,
            $userId,
            $tenantId,
            $correlationId
        ) {
            $reservation = $this->db->table('stock_reservations')
                ->where('reservation_id', $reservationId)
                ->where('status', 'active')
                ->first();

            if (! $reservation) {
                throw new \RuntimeException("Active reservation {$reservationId} not found");
            }

            $newExpiry = $reservation->expires_at->addSeconds($additionalSeconds);

            $this->db->table('stock_reservations')
                ->where('id', $reservation->id)
                ->update([
                    'expires_at' => $newExpiry,
                    'extended_by' => $userId,
                    'extended_at' => now(),
                ]);

            // Update Redis TTL
            $redisKey = self::RESERVATION_PREFIX.$reservationId;
            $currentTTL = Redis::ttl($redisKey);
            if ($currentTTL > 0) {
                Redis::expire($redisKey, $currentTTL + $additionalSeconds);
            }

            $this->logAction(
                action: 'reservation_extended',
                entityType: 'StockReservation',
                entityId: $reservationId,
                context: [
                    'correlation_id' => $correlationId,
                    'additional_seconds' => $additionalSeconds,
                    'new_expiry' => $newExpiry->toIso8601String(),
                ],
                userId: $userId,
                tenantId: $tenantId
            );

            return true;
        });
    }
}
