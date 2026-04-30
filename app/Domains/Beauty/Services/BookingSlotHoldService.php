<?php

declare(strict_types=1);

namespace App\Domains\Beauty\Services;

use Illuminate\Contracts\Events\Dispatcher as EventDispatcher;

use Psr\Log\LoggerInterface;

use App\Domains\Beauty\DTOs\HoldBookingSlotDto;
use App\Domains\Beauty\Events\SlotHeldEvent;
use App\Domains\Beauty\Events\SlotReleasedEvent;
use App\Domains\Beauty\Models\BookingSlot;
use App\Octane\Services\SwooleTableService;
use App\Services\AuditService;
use App\Services\FraudControlService;
use App\Services\IdempotencyService;
use App\Services\CRMService;
use Illuminate\Database\ConnectionInterface;
use Illuminate\Log\LogManager;
use Illuminate\Redis\Connections\Connection as RedisConnection;
use Illuminate\Support\Str;
use RuntimeException;
use Carbon\CarbonImmutable;

final readonly class BookingSlotHoldService
{
    public function __construct(private readonly EventDispatcher $eventDispatcher,
        private readonly LoggerInterface $logger,
        private readonly FraudControlService $fraudControl,
        private readonly AuditService $auditService,
        private readonly IdempotencyService $idempotencyService,
        private readonly CRMService $crmService,
        private readonly ConnectionInterface $db,
        private readonly LogManager $log,
        private readonly RedisConnection $redis,
        private readonly ?SwooleTableService $swooleTableService = null,) {}

    public function holdSlot(HoldBookingSlotDto $dto): BookingSlot
    {
        $correlationId = $dto->correlationId ?? Str::uuid()->toString();

        $this->fraudControl->check($dto);
        $this->idempotencyService->checkOrSkip($dto->idempotencyKey, 'booking_slot_hold');

        $this->log->channel('audit')->$this->logger->info('beauty.slot.hold.start', [
            'correlation_id' => $correlationId,
            'booking_slot_id' => $dto->bookingSlotId,
            'customer_id' => $dto->customerId,
            'tenant_id' => $dto->tenantId,
            'is_b2b' => $dto->isB2b,
        ]);

        $slot = $this->db->transaction(function () use ($dto, $correlationId) {
            $slot = BookingSlot::query()
                ->where('id', $dto->bookingSlotId)
                ->where('tenant_id', $dto->tenantId)
                ->where('status', 'available')
                ->lockForUpdate()
                ->first();

            if ($slot === null) {
                throw new RuntimeException('Booking slot not available or does not exist');
            }

            $holdMinutes = $dto->isB2b ? 60 : 15;
            $expiresAt = CarbonImmutable::now()->addMinutes($holdMinutes);

            $slot->update([
                'status' => 'held',
                'customer_id' => $dto->customerId,
                'held_at' => CarbonImmutable::now(),
                'expires_at' => $expiresAt,
                'correlation_id' => $correlationId,
                'metadata' => array_merge($slot->metadata ?? [], [
                    'hold_source' => $dto->isB2b ? 'b2b_booking' : 'b2c_booking',
                    'hold_duration_minutes' => $holdMinutes,
                    'business_group_id' => $dto->businessGroupId,
                ]),
            ]);

            $this->auditService->log(
                action: 'booking_slot_held',
                entityType: 'BookingSlot',
                entityId: $slot->id,
                tenantId: $dto->tenantId,
                correlationId: $correlationId,
                metadata: [
                    'customer_id' => $dto->customerId,
                    'expires_at' => $expiresAt->toIso8601String(),
                    'is_b2b' => $dto->isB2b,
                ],
            );

            $this->eventDispatcher->dispatch(new SlotHeldEvent($slot, $correlationId));

            // Store in Swoole Table for fast access (fallback to Redis if Swoole not available)
            $this->storeSlotHoldInTable($slot->id, $dto->customerId, $dto->tenantId, $expiresAt->timestamp, $correlationId);

            return $slot->fresh();
        });

        $this->log->channel('audit')->$this->logger->info('beauty.slot.hold.success', [
            'correlation_id' => $correlationId,
            'booking_slot_id' => $slot->id,
            'expires_at' => $slot->expires_at->toIso8601String(),
        ]);

        return $slot;
    }

    public function releaseSlot(
        int $bookingSlotId,
        int $tenantId,
        string $reason = 'payment_failed',
        ?string $correlationId = null,
    ): BookingSlot {
        $correlationId ??= Str::uuid()->toString();

        $this->log->channel('audit')->$this->logger->info('beauty.slot.release.start', [
            'correlation_id' => $correlationId,
            'booking_slot_id' => $bookingSlotId,
            'tenant_id' => $tenantId,
            'reason' => $reason,
        ]);

        $slot = $this->db->transaction(function () use ($bookingSlotId, $tenantId, $reason, $correlationId) {
            $slot = BookingSlot::query()
                ->where('id', $bookingSlotId)
                ->where('tenant_id', $tenantId)
                ->whereIn('status', ['held', 'booked'])
                ->lockForUpdate()
                ->first();

            if ($slot === null) {
                throw new RuntimeException('Booking slot not found or not in hold/booked state');
            }

            $previousStatus = $slot->status;
            $customerId = $slot->customer_id;

            $slot->update([
                'status' => 'available',
                'customer_id' => null,
                'held_at' => null,
                'expires_at' => null,
                'correlation_id' => $correlationId,
                'metadata' => array_merge($slot->metadata ?? [], [
                    'release_reason' => $reason,
                    'previous_status' => $previousStatus,
                    'released_at' => CarbonImmutable::now()->toIso8601String(),
                ]),
            ]);

            $this->auditService->log(
                action: 'booking_slot_released',
                entityType: 'BookingSlot',
                entityId: $slot->id,
                tenantId: $tenantId,
                correlationId: $correlationId,
                metadata: [
                    'previous_status' => $previousStatus,
                    'reason' => $reason,
                    'customer_id' => $customerId,
                ],
            );

            $this->eventDispatcher->dispatch(new SlotReleasedEvent($slot, $correlationId, $reason));

            // Remove from Swoole Table
            $this->removeSlotHoldFromTable($slot->id);

            if ($previousStatus === 'held' && $reason === 'payment_failed') {
                $this->crmService->createAppeal(
                    tenantId: $tenantId,
                    customerId: $customerId,
                    type: 'slot_release',
                    title: 'Слот освобождён (неуспешная оплата)',
                    description: sprintf(
                        'Слот #%d был освобождён из-за неуспешной оплаты. Причина: %s',
                        $slot->id,
                        $reason,
                    ),
                    correlationId: $correlationId,
                );
            }

            return $slot->fresh();
        });

        $this->log->channel('audit')->$this->logger->info('beauty.slot.release.success', [
            'correlation_id' => $correlationId,
            'booking_slot_id' => $slot->id,
        ]);

        return $slot;
    }

    public function confirmSlotAsBooked(
        int $bookingSlotId,
        int $tenantId,
        int $orderId,
        ?string $correlationId = null,
    ): BookingSlot {
        $correlationId ??= Str::uuid()->toString();

        $this->log->channel('audit')->$this->logger->info('beauty.slot.confirm.start', [
            'correlation_id' => $correlationId,
            'booking_slot_id' => $bookingSlotId,
            'tenant_id' => $tenantId,
            'order_id' => $orderId,
        ]);

        $slot = $this->db->transaction(function () use ($bookingSlotId, $tenantId, $orderId, $correlationId) {
            $slot = BookingSlot::query()
                ->where('id', $bookingSlotId)
                ->where('tenant_id', $tenantId)
                ->where('status', 'held')
                ->lockForUpdate()
                ->first();

            if ($slot === null) {
                throw new RuntimeException('Booking slot not found or not in held state');
            }

            if ($slot->expires_at->isPast()) {
                $this->releaseSlot($bookingSlotId, $tenantId, 'expired', $correlationId);
                throw new RuntimeException('Booking slot has expired');
            }

            $slot->update([
                'status' => 'booked',
                'order_id' => $orderId,
                'booked_at' => CarbonImmutable::now(),
                'correlation_id' => $correlationId,
                'metadata' => array_merge($slot->metadata ?? [], [
                    'confirmed_via' => 'payment_success',
                    'order_id' => $orderId,
                ]),
            ]);

            $this->auditService->log(
                action: 'booking_slot_confirmed',
                entityType: 'BookingSlot',
                entityId: $slot->id,
                tenantId: $tenantId,
                correlationId: $correlationId,
                metadata: [
                    'order_id' => $orderId,
                    'customer_id' => $slot->customer_id,
                ],
            );

            $this->crmService->createBooking(
                tenantId: $tenantId,
                customerId: $slot->customer_id,
                bookingSlotId: $slot->id,
                orderId: $orderId,
                correlationId: $correlationId,
            );

            return $slot->fresh();
        });

        $this->log->channel('audit')->$this->logger->info('beauty.slot.confirm.success', [
            'correlation_id' => $correlationId,
            'booking_slot_id' => $slot->id,
            'order_id' => $orderId,
        ]);

        return $slot;
    }

    public function expireHeldSlots(int $tenantId): int
    {
        $correlationId = Str::uuid()->toString();

        $this->log->channel('audit')->$this->logger->info('beauty.slot.expire.start', [
            'correlation_id' => $correlationId,
            'tenant_id' => $tenantId,
        ]);

        $expiredSlots = BookingSlot::query()
            ->where('tenant_id', $tenantId)
            ->where('status', 'held')
            ->where('expires_at', '<', CarbonImmutable::now())
            ->get();

        $count = 0;

        foreach ($expiredSlots as $slot) {
            try {
                $this->releaseSlot($slot->id, $tenantId, 'expired', $correlationId);
                $count++;
            } catch (RuntimeException $e) {
                $this->log->channel('audit')->warning('beauty.slot.expire.failed', [
                    'correlation_id' => $correlationId,
                    'booking_slot_id' => $slot->id,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        $this->log->channel('audit')->$this->logger->info('beauty.slot.expire.complete', [
            'correlation_id' => $correlationId,
            'tenant_id' => $tenantId,
            'expired_count' => $count,
        ]);

        return $count;
    }

    public function isSlotHeld(int $slotId): bool
    {
        if ($this->swooleTableService === null) {
            // Fallback to Redis
            return $this->redis->exists("beauty:slot_hold:{$slotId}") > 0;
        }

        $slotHoldsTable = $this->swooleTableService->slotHolds();
        if ($slotHoldsTable) {
            $hold = $slotHoldsTable->get("beauty:{$slotId}");
            if ($hold && $hold['expires_at'] > time()) {
                return true;
            }
            // Clean up expired holds
            if ($hold) {
                $slotHoldsTable->del("beauty:{$slotId}");
            }
        }

        return false;
    }

    private function storeSlotHoldInTable(int $slotId, int $customerId, int $tenantId, int $expiresAt, string $correlationId): void
    {
        if ($this->swooleTableService === null) {
            // Fallback to Redis if Swoole not available
            $key = "beauty:slot_hold:{$slotId}";
            $this->redis->setex($key, $expiresAt - time(), json_encode([
                'slot_id' => $slotId,
                'customer_id' => $customerId,
                'tenant_id' => $tenantId,
                'expires_at' => $expiresAt,
                'correlation_id' => $correlationId,
                'status' => 'active',
            ]));

            return;
        }

        // Use Swoole Table for faster access
        $slotHoldsTable = $this->swooleTableService->slotHolds();
        if ($slotHoldsTable) {
            $slotHoldsTable->set("beauty:{$slotId}", [
                'user_id' => $customerId,
                'doctor_id' => 0, // Not applicable for beauty
                'clinic_id' => $tenantId,
                'slot_time' => 0, // Not applicable
                'expires_at' => $expiresAt,
                'status' => 'active',
                'created_at' => time(),
            ]);
        }
    }

    private function removeSlotHoldFromTable(int $slotId): void
    {
        if ($this->swooleTableService === null) {
            // Fallback to Redis
            $this->redis->del("beauty:slot_hold:{$slotId}");

            return;
        }

        $slotHoldsTable = $this->swooleTableService->slotHolds();
        if ($slotHoldsTable) {
            $slotHoldsTable->del("beauty:{$slotId}");
        }
    }
}
