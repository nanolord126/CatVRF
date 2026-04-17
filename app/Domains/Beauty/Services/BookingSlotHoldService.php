<?php declare(strict_types=1);

namespace App\Domains\Beauty\Services;

use App\Domains\Beauty\DTOs\HoldBookingSlotDto;
use App\Domains\Beauty\Events\SlotHeldEvent;
use App\Domains\Beauty\Events\SlotReleasedEvent;
use App\Domains\Beauty\Models\BookingSlot;
use App\Services\AuditService;
use App\Services\FraudControlService;
use App\Services\IdempotencyService;
use App\Services\CRMService;
use Illuminate\Database\ConnectionInterface;
use Illuminate\Log\Logger;
use Illuminate\Support\Str;
use RuntimeException;

final readonly class BookingSlotHoldService
{
    public function __construct(
        private FraudControlService $fraudControl,
        private AuditService $auditService,
        private IdempotencyService $idempotencyService,
        private CRMService $crmService,
        private ConnectionInterface $db,
        private Logger $logger,
    ) {
    }

    public function holdSlot(HoldBookingSlotDto $dto): BookingSlot
    {
        $correlationId = $dto->correlationId ?? Str::uuid()->toString();

        $this->fraudControl->check($dto);
        $this->idempotencyService->checkOrSkip($dto->idempotencyKey, 'booking_slot_hold');

        $this->logger->channel('audit')->info('beauty.slot.hold.start', [
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
            $expiresAt = now()->addMinutes($holdMinutes);

            $slot->update([
                'status' => 'held',
                'customer_id' => $dto->customerId,
                'held_at' => now(),
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

            event(new SlotHeldEvent($slot, $correlationId));

            return $slot->fresh();
        });

        $this->logger->channel('audit')->info('beauty.slot.hold.success', [
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

        $this->logger->channel('audit')->info('beauty.slot.release.start', [
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
                    'released_at' => now()->toIso8601String(),
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

            event(new SlotReleasedEvent($slot, $correlationId, $reason));

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

        $this->logger->channel('audit')->info('beauty.slot.release.success', [
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

        $this->logger->channel('audit')->info('beauty.slot.confirm.start', [
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
                'booked_at' => now(),
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

        $this->logger->channel('audit')->info('beauty.slot.confirm.success', [
            'correlation_id' => $correlationId,
            'booking_slot_id' => $slot->id,
            'order_id' => $orderId,
        ]);

        return $slot;
    }

    public function expireHeldSlots(int $tenantId): int
    {
        $correlationId = Str::uuid()->toString();

        $this->logger->channel('audit')->info('beauty.slot.expire.start', [
            'correlation_id' => $correlationId,
            'tenant_id' => $tenantId,
        ]);

        $expiredSlots = BookingSlot::query()
            ->where('tenant_id', $tenantId)
            ->where('status', 'held')
            ->where('expires_at', '<', now())
            ->get();

        $count = 0;

        foreach ($expiredSlots as $slot) {
            try {
                $this->releaseSlot($slot->id, $tenantId, 'expired', $correlationId);
                $count++;
            } catch (RuntimeException $e) {
                $this->logger->channel('audit')->warning('beauty.slot.expire.failed', [
                    'correlation_id' => $correlationId,
                    'booking_slot_id' => $slot->id,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        $this->logger->channel('audit')->info('beauty.slot.expire.complete', [
            'correlation_id' => $correlationId,
            'tenant_id' => $tenantId,
            'expired_count' => $count,
        ]);

        return $count;
    }
}
