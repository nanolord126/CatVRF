<?php

declare(strict_types=1);

namespace App\Domains\Education\Services;

use Psr\Log\LoggerInterface;

use App\Domains\Education\DTOs\BookSlotDto;
use App\Domains\Education\DTOs\SlotHoldDto;
use App\Domains\Education\Models\Slot;
use App\Services\FraudControlService;
use App\Services\AuditService;
use App\Services\Security\IdempotencyService;
use App\Services\ML\AnonymizationService;
use Illuminate\Database\DatabaseManager;
use Illuminate\Log\LogManager;
use Illuminate\Redis\Connections\Connection as RedisConnection;
use Illuminate\Support\Str;
use Carbon\CarbonImmutable;

final readonly class EducationSlotBookingService
{
    private const HOLD_DURATION_MINUTES = 15;

    private const CACHE_TTL = 900;

    public function __construct(private readonly LoggerInterface $logger,
        private readonly FraudControlService $fraud,
        private readonly AuditService $audit,
        private readonly IdempotencyService $idempotency,
        private readonly AnonymizationService $anonymizer,
        private readonly DatabaseManager $db,
        private readonly LogManager $log,
        private readonly RedisConnection $redis,) {}

    public function holdSlot(int $slotId, int $userId, string $correlationId): SlotHoldDto
    {
        $slot = Slot::findOrFail($slotId);

        if ($slot->status !== 'available' || $slot->booked_count >= $slot->capacity) {
            throw new \DomainException('Slot is not available');
        }

        $holdKey = $this->getHoldKey($slotId, $userId);
        $existingHold = $this->redis->get($holdKey);

        if ($existingHold !== null) {
            return SlotHoldDto::fromArray(json_decode($existingHold, true));
        }

        $holdId = (string) Str::uuid();
        $holdExpiresAt = CarbonImmutable::now()->addMinutes(self::HOLD_DURATION_MINUTES)->toIso8601String();

        $holdDto = new SlotHoldDto(
            holdId: $holdId,
            slotId: $slotId,
            userId: $userId,
            holdExpiresAt: $holdExpiresAt,
            status: 'held',
            createdAt: CarbonImmutable::now()->toIso8601String(),
        );

        $this->redis->setex($holdKey, self::HOLD_DURATION_MINUTES * 60, json_encode($holdDto->toArray()));

        $slot->update([
            'status' => 'held',
            'booked_count' => $slot->booked_count + 1,
        ]);

        $this->audit->record('education_slot_held', 'SlotHoldDto', $slotId, [], [
            'correlation_id' => $correlationId,
            'tenant_id' => $slot->tenant_id,
            'slot_id' => $slotId,
            'user_id' => $userId,
            'hold_id' => $holdId,
            'hold_expires_at' => $holdExpiresAt,
        ], $correlationId);

        $this->log->channel('audit')->$this->logger->info('Slot held successfully', [
            'correlation_id' => $correlationId,
            'slot_id' => $slotId,
            'user_id' => $userId,
            'hold_id' => $holdId,
        ]);

        return $holdDto;
    }

    public function releaseSlotHold(int $slotId, int $userId, string $correlationId): void
    {
        $holdKey = $this->getHoldKey($slotId, $userId);
        $this->redis->del($holdKey);

        $slot = Slot::findOrFail($slotId);

        if ($slot->status === 'held') {
            $slot->update([
                'status' => 'available',
                'booked_count' => max(0, $slot->booked_count - 1),
            ]);
        }

        $this->audit->record('education_slot_hold_released', 'Slot', $slotId, [], [
            'correlation_id' => $correlationId,
            'slot_id' => $slotId,
            'user_id' => $userId,
        ], $correlationId);
    }

    public function bookSlot(BookSlotDto $dto): array
    {
        $this->fraud->check($dto);

        if ($dto->idempotencyKey !== null) {
            $cached = $this->idempotency->check('education_slot_booking', $dto->idempotencyKey, $dto->toArray(), $dto->tenantId);
            if (! empty($cached)) {
                return $cached;
            }
        }

        return $this->db->transaction(function () use ($dto) {
            $slot = Slot::findOrFail($dto->slotId);

            $this->validateSlotAvailability($slot, $dto);
            $this->validateBiometric($dto);

            $bookingReference = $this->generateBookingReference();

            $booking = $this->db->table('education_slot_bookings')->insertGetId([
                'uuid' => (string) Str::uuid(),
                'tenant_id' => $dto->tenantId,
                'business_group_id' => $dto->businessGroupId,
                'user_id' => $dto->userId,
                'slot_id' => $dto->slotId,
                'booking_reference' => $bookingReference,
                'status' => 'confirmed',
                'booked_at' => CarbonImmutable::now(),
                'confirmed_at' => CarbonImmutable::now(),
                'biometric_hash' => $dto->biometricHash,
                'device_fingerprint' => $dto->deviceFingerprint,
                'metadata' => json_encode([
                    'is_corporate' => $dto->isCorporate,
                    'payment_method_id' => $dto->paymentMethodId,
                ]),
                'correlation_id' => $dto->correlationId,
                'created_at' => CarbonImmutable::now(),
                'updated_at' => CarbonImmutable::now(),
            ]);

            $slot->update([
                'status' => 'booked',
                'booked_count' => $slot->booked_count + 1,
            ]);

            $this->releaseSlotHold($dto->slotId, $dto->userId, $dto->correlationId);

            $result = [
                'booking_id' => $booking,
                'booking_reference' => $bookingReference,
                'slot_id' => $dto->slotId,
                'status' => 'confirmed',
                'meeting_link' => $slot->meeting_link,
                'meeting_password' => $slot->meeting_password,
                'start_time' => $slot->start_time->toIso8601String(),
                'end_time' => $slot->end_time->toIso8601String(),
            ];

            if ($dto->idempotencyKey !== null) {
                $this->idempotency->record('education_slot_booking', $dto->idempotencyKey, $dto->toArray(), $result, $dto->tenantId, 1440);
            }

            $this->audit->record('education_slot_booked', 'SlotBooking', $booking, [], [
                'correlation_id' => $dto->correlationId,
                'tenant_id' => $dto->tenantId,
                'user_id' => $dto->userId,
                'slot_id' => $dto->slotId,
                'booking_reference' => $bookingReference,
                'is_corporate' => $dto->isCorporate,
            ], $dto->correlationId);

            $this->log->channel('audit')->$this->logger->info('Slot booked successfully', [
                'correlation_id' => $dto->correlationId,
                'booking_id' => $booking,
                'booking_reference' => $bookingReference,
            ]);

            return $result;
        });
    }

    public function cancelBooking(int $bookingId, int $userId, string $correlationId): void
    {
        $this->db->transaction(function () use ($bookingId, $userId, $correlationId) {
            $booking = $this->db->table('education_slot_bookings')
                ->where('id', $bookingId)
                ->where('user_id', $userId)
                ->first();

            if ($booking === null) {
                throw new \DomainException('Booking not found');
            }

            if ($booking->status === 'cancelled' || $booking->status === 'completed') {
                throw new \DomainException('Booking cannot be cancelled');
            }

            $this->db->table('education_slot_bookings')
                ->where('id', $bookingId)
                ->update([
                    'status' => 'cancelled',
                    'cancelled_at' => CarbonImmutable::now(),
                ]);

            $slot = Slot::findOrFail($booking->slot_id);

            $slot->update([
                'status' => 'available',
                'booked_count' => max(0, $slot->booked_count - 1),
            ]);

            $this->audit->record('education_slot_booking_cancelled', 'SlotBooking', $bookingId, [], [
                'correlation_id' => $correlationId,
                'booking_id' => $bookingId,
                'user_id' => $userId,
            ], $correlationId);
        });
    }

    private function validateSlotAvailability(Slot $slot, BookSlotDto $dto): void
    {
        if ($slot->status !== 'available' && $slot->status !== 'held') {
            throw new \DomainException('Slot is not available for booking');
        }

        if ($slot->booked_count >= $slot->capacity) {
            throw new \DomainException('Slot is fully booked');
        }

        if ($slot->start_time->isPast()) {
            throw new \DomainException('Slot has already started');
        }

        $userExistingBookings = $this->db->table('education_slot_bookings')
            ->where('user_id', $dto->userId)
            ->where('slot_id', $dto->slotId)
            ->where('status', '!=', 'cancelled')
            ->exists();

        if ($userExistingBookings) {
            throw new \DomainException('User already has a booking for this slot');
        }
    }

    private function validateBiometric(BookSlotDto $dto): void
    {
        if ($dto->biometricHash !== null) {
            $recentBiometrics = $this->db->table('education_slot_bookings')
                ->where('user_id', $dto->userId)
                ->where('biometric_hash', $dto->biometricHash)
                ->where('created_at', '>=', CarbonImmutable::now()->subHours(1))
                ->exists();

            if ($recentBiometrics) {
                throw new \DomainException('Suspicious activity: biometric hash reused within 1 hour');
            }
        }

        if ($dto->deviceFingerprint !== null) {
            $recentBookings = $this->db->table('education_slot_bookings')
                ->where('device_fingerprint', $dto->deviceFingerprint)
                ->where('created_at', '>=', CarbonImmutable::now()->subMinutes(30))
                ->count();

            if ($recentBookings > 5) {
                throw new \DomainException('Too many bookings from this device in 30 minutes');
            }
        }
    }

    private function generateBookingReference(): string
    {
        return 'EDU-'.strtoupper(Str::random(8)).'-'.CarbonImmutable::now()->format('Ymd');
    }

    private function getHoldKey(int $slotId, int $userId): string
    {
        return "education:slot:hold:{$slotId}:user:{$userId}";
    }
}
