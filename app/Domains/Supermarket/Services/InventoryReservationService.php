<?php

declare(strict_types=1);

namespace App\Domains\Supermarket\Services;

use App\Domains\Shared\Inventory\DTOs\CreateReservationDto;
use App\Domains\Shared\Inventory\Services\InventoryService;
use App\Services\FraudControlService;
use App\Services\AuditService;
use App\Services\RedisDistributedLockService;
use App\Traits\WithAuditLogging;
use Carbon\CarbonImmutable;
use Illuminate\Database\DatabaseManager;
use Illuminate\Support\Facades\Cache;
use Psr\Log\LoggerInterface;

/**
 * InventoryReservationService - сервис резервирования товаров для Supermarket.
 *
 * Предоставляет Supermarket-специфичную логику резервирования с:
 * - 20-минутным резервированием для B2C
 * - 7-дневным резервированием для B2B
 * - Автоматическим освобождением истекших резервов
 * - Проверкой доступности товара
 */
final readonly class InventoryReservationService
{
    use WithAuditLogging;

    private const int B2C_RESERVATION_MINUTES = 20;
    private const int B2B_RESERVATION_DAYS = 7;

    public function __construct(
        private readonly InventoryService $inventoryService,
        private readonly FraudControlService $fraudControl,
        private readonly AuditService $audit,
        private readonly DatabaseManager $db,
        private readonly LoggerInterface $logger,
        private readonly RedisDistributedLockService $distributedLock,
    ) {}

    /**
     * Зарезервировать товары для корзины/заказа.
     *
     * @param  int  $userId
     * @param  array<int, array{product_id: int, quantity: int, warehouse_id: int}>  $items
     * @param  int|null  $cartId
     * @param  int|null  $orderId
     * @param  int|null  $businessGroupId
     * @return array{reservation_ids: array<int>, expires_at: string}
     */
    public function reserveItems(
        int $userId,
        array $items,
        ?int $cartId = null,
        ?int $orderId = null,
        ?int $businessGroupId = null,
    ): array {
        $correlationId = $this->generateCorrelationId();
        $reservationIds = [];
        $isB2B = $businessGroupId !== null;

        // Fraud check
        $totalAmount = array_sum(array_map(fn ($item) => $item['quantity'], $items));
        $this->fraudControl->check([
            'operation_type' => 'supermarket_inventory_reserve',
            'vertical' => 'supermarket',
            'user_id' => $userId,
            'amount' => $totalAmount,
            'correlation_id' => $correlationId,
        ]);

        return $this->db->transaction(function () use (
            $userId,
            $items,
            $cartId,
            $orderId,
            $businessGroupId,
            $isB2B,
            $correlationId,
            &$reservationIds,
        ) {
            $tenantId = function_exists('tenant') && tenant() ? tenant()->id : $userId;

            foreach ($items as $item) {
                // Проверка доступности
                $available = $this->inventoryService->getAvailableStock(
                    $item['product_id'],
                    $item['warehouse_id'] ?? 1
                );

                if ($available < $item['quantity']) {
                    throw new \RuntimeException(sprintf(
                        'Insufficient stock for product %d. Available: %d, Requested: %d',
                        $item['product_id'],
                        $available,
                        $item['quantity']
                    ));
                }

                // Создание резерва
                $reservationDto = new CreateReservationDto(
                    tenantId: $tenantId,
                    productId: $item['product_id'],
                    warehouseId: $item['warehouse_id'] ?? 1,
                    quantity: $item['quantity'],
                    sourceType: 'cart',
                    sourceId: $cartId ?? 0,
                    correlationId: $correlationId,
                    businessGroupId: $businessGroupId,
                    cartId: $cartId,
                    orderId: $orderId,
                );

                $reservation = $this->inventoryService->reserve($reservationDto);
                $reservationIds[] = $reservation->id;

                // Cache для быстрого доступа
                Cache::put("reservation:{$reservation->id}", [
                    'product_id' => $item['product_id'],
                    'quantity' => $item['quantity'],
                    'user_id' => $userId,
                    'expires_at' => $reservation->expires_at,
                ], now()->addHours(1));
            }

            $expiresAt = $isB2B
                ? CarbonImmutable::now()->addDays(self::B2B_RESERVATION_DAYS)->toDateTimeString()
                : CarbonImmutable::now()->addMinutes(self::B2C_RESERVATION_MINUTES)->toDateTimeString();

            $this->logAction(
                action: 'inventory_reserved',
                entityType: 'supermarket_reservation',
                entityId: null,
                userId: $userId,
                context: [
                    'correlation_id' => $correlationId,
                    'cart_id' => $cartId,
                    'order_id' => $orderId,
                    'business_group_id' => $businessGroupId,
                    'reservation_count' => count($reservationIds),
                    'is_b2b' => $isB2B,
                    'expires_at' => $expiresAt,
                ],
            );

            $this->logger->info('Supermarket inventory reserved', [
                'user_id' => $userId,
                'reservation_count' => count($reservationIds),
                'correlation_id' => $correlationId,
                'expires_at' => $expiresAt,
            ]);

            return [
                'reservation_ids' => $reservationIds,
                'expires_at' => $expiresAt,
                'correlation_id' => $correlationId,
            ];
        });
    }

    /**
     * Освободить резервы.
     *
     * @param  array<int>  $reservationIds
     * @param  int  $userId
     * @return void
     */
    public function releaseReservations(array $reservationIds, int $userId): void
    {
        $correlationId = $this->generateCorrelationId();

        foreach ($reservationIds as $reservationId) {
            $this->inventoryService->releaseReservation($reservationId, $correlationId);
            Cache::forget("reservation:{$reservationId}");
        }

        $this->logAction(
            action: 'inventory_reservations_released',
            entityType: 'supermarket_reservation',
            entityId: null,
            userId: $userId,
            context: [
                'correlation_id' => $correlationId,
                'reservation_count' => count($reservationIds),
            ],
        );

        $this->logger->info('Supermarket inventory reservations released', [
            'user_id' => $userId,
            'reservation_count' => count($reservationIds),
            'correlation_id' => $correlationId,
        ]);
    }

    /**
     * Продлить резерв (только для B2B).
     *
     * @param  int  $reservationId
     * @param  int  $userId
     * @param  int  $additionalDays
     * @return array{success: bool, new_expires_at: string}
     */
    public function extendReservation(int $reservationId, int $userId, int $additionalDays = 7): array
    {
        $correlationId = $this->generateCorrelationId();

        return $this->db->transaction(function () use ($reservationId, $userId, $additionalDays, $correlationId) {
            $reservation = $this->db->table('inventory_reservations')
                ->where('id', $reservationId)
                ->where('tenant_id', $userId)
                ->first();

            if (!$reservation) {
                throw new \RuntimeException('Reservation not found');
            }

            // Проверка, что это B2B резерв
            if ($reservation->business_group_id === null) {
                throw new \RuntimeException('Reservation extension only available for B2B');
            }

            $newExpiresAt = CarbonImmutable::parse($reservation->expires_at)->addDays($additionalDays);

            $this->db->table('inventory_reservations')
                ->where('id', $reservationId)
                ->update([
                    'expires_at' => $newExpiresAt,
                    'updated_at' => now(),
                ]);

            Cache::forget("reservation:{$reservationId}");

            $this->logAction(
                action: 'reservation_extended',
                entityType: 'supermarket_reservation',
                entityId: $reservationId,
                userId: $userId,
                context: [
                    'correlation_id' => $correlationId,
                    'additional_days' => $additionalDays,
                    'new_expires_at' => $newExpiresAt->toDateTimeString(),
                ],
            );

            return [
                'success' => true,
                'new_expires_at' => $newExpiresAt->toDateTimeString(),
            ];
        });
    }

    /**
     * Освободить истекшие резервы (для планировщика).
     *
     * @return int Количество освобождённых резервов
     */
    public function releaseExpiredReservations(): int
    {
        $correlationId = $this->generateCorrelationId();

        $expiredReservations = $this->db->table('inventory_reservations')
            ->where('expires_at', '<', now())
            ->where('order_id', null) // только не привязанные к заказу
            ->get();

        $releasedCount = 0;

        foreach ($expiredReservations as $reservation) {
            try {
                $this->inventoryService->releaseReservation($reservation->id, $correlationId);
                Cache::forget("reservation:{$reservation->id}");
                $releasedCount++;
            } catch (\Exception $e) {
                $this->logger->error('Failed to release expired reservation', [
                    'reservation_id' => $reservation->id,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        $this->logger->info('Expired reservations released', [
            'released_count' => $releasedCount,
            'correlation_id' => $correlationId,
        ]);

        return $releasedCount;
    }

    /**
     * Получить статус резервирования для товаров.
     *
     * @param  array<int, int>  $productIds
     * @param  int  $warehouseId
     * @return array<int, array{product_id: int, available: int, reserved: int}>
     */
    public function getReservationStatus(array $productIds, int $warehouseId = 1): array
    {
        $status = [];

        foreach ($productIds as $productId) {
            $available = $this->inventoryService->getAvailableStock($productId, $warehouseId);
            $reserved = $this->db->table('inventory_reservations')
                ->join('inventory_items', 'inventory_reservations.inventory_id', '=', 'inventory_items.id')
                ->where('inventory_items.product_id', $productId)
                ->where('inventory_items.warehouse_id', $warehouseId)
                ->where('inventory_reservations.expires_at', '>', now())
                ->sum('inventory_reservations.quantity');

            $status[$productId] = [
                'product_id' => $productId,
                'available' => $available,
                'reserved' => (int) $reserved,
            ];
        }

        return $status;
    }

    /**
     * Подтвердить резерв после успешной оплаты.
     *
     * Обновляет резервы, связывая их с заказом и продлевая время жизни.
     * Резервы будут списаны при отгрузке через confirmShipment().
     *
     * @param  string  $orderId
     * @return void
     */
    public function confirmReservation(string $orderId): void
    {
        $correlationId = $this->generateCorrelationId();

        $this->db->transaction(function () use ($orderId, $correlationId) {
            $reservations = $this->db->table('inventory_reservations')
                ->where('order_id', $orderId)
                ->get();

            if ($reservations->isEmpty()) {
                $this->logger->warning('No reservations found for order', [
                    'order_id' => $orderId,
                    'correlation_id' => $correlationId,
                ]);
                return;
            }

            // Продлеваем время жизни резервов (для B2C - до 24 часов, для B2B - до 7 дней)
            foreach ($reservations as $reservation) {
                $isB2B = $reservation->business_group_id !== null;
                $newExpiresAt = $isB2B
                    ? CarbonImmutable::now()->addDays(7)->toDateTimeString()
                    : CarbonImmutable::now()->addHours(24)->toDateTimeString();

                $this->db->table('inventory_reservations')
                    ->where('id', $reservation->id)
                    ->update([
                        'expires_at' => $newExpiresAt,
                        'updated_at' => now(),
                    ]);

                Cache::forget("reservation:{$reservation->id}");
            }

            $this->logAction(
                action: 'inventory_reservation_confirmed',
                entityType: 'supermarket_reservation',
                entityId: null,
                userId: null,
                context: [
                    'correlation_id' => $correlationId,
                    'order_id' => $orderId,
                    'reservation_count' => $reservations->count(),
                ],
            );

            $this->logger->info('Supermarket inventory reservations confirmed', [
                'order_id' => $orderId,
                'reservation_count' => $reservations->count(),
                'correlation_id' => $correlationId,
            ]);
        });
    }
}
