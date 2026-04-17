<?php

declare(strict_types=1);

namespace App\Domains\Sports\Services;

use App\Domains\Sports\DTOs\RealTimeBookingDto;
use App\Domains\Sports\Events\BookingConfirmedEvent;
use App\Domains\Sports\Models\Booking;
use App\Services\FraudControlService;
use App\Services\AuditService;
use Illuminate\Database\DatabaseManager;
use Illuminate\Contracts\Cache\Repository as Cache;
use Illuminate\Redis\Connections\Connection as RedisConnection;
use Psr\Log\LoggerInterface;
use Illuminate\Support\Str;

final readonly class SportsRealTimeBookingService
{
    private const SLOT_HOLD_MINUTES = 15;
    private const SLOT_HOLD_EXTENDED_MINUTES = 60;
    private const BIOMETRIC_HASH_SALT = 'sports_biometric_salt_2026';

    public function __construct(
        private FraudControlService $fraud,
        private AuditService $audit,
        private DatabaseManager $db,
        private Cache $cache,
        private LoggerInterface $logger,
        private RedisConnection $redis,
    ) {}

    public function holdSlot(RealTimeBookingDto $dto): array
    {
        $this->fraud->check(
            userId: $dto->userId,
            operationType: 'slot_hold',
            amount: 0,
            correlationId: $dto->correlationId,
        );

        $holdMinutes = $dto->extendedHold ? self::SLOT_HOLD_EXTENDED_MINUTES : self::SLOT_HOLD_MINUTES;
        $slotKey = $this->generateSlotKey($dto->venueId, $dto->trainerId, $dto->slotStart);

        if ($this->redis->exists($slotKey)) {
            $existingHold = json_decode($this->redis->get($slotKey), true);
            if (intval($existingHold['user_id']) !== $dto->userId) {
                return [
                    'success' => false,
                    'message' => 'Slot already held by another user',
                    'hold_until' => $existingHold['hold_until'] ?? null,
                ];
            }
        }

        $holdUntil = now()->addMinutes($holdMinutes);
        $biometricHash = $this->hashBiometricData($dto->biometricData);

        $holdData = [
            'user_id' => $dto->userId,
            'tenant_id' => $dto->tenantId,
            'business_group_id' => $dto->businessGroupId,
            'venue_id' => $dto->venueId,
            'trainer_id' => $dto->trainerId,
            'slot_start' => $dto->slotStart,
            'slot_end' => $dto->slotEnd,
            'booking_type' => $dto->bookingType,
            'biometric_hash' => $biometricHash,
            'held_at' => now()->toIso8601String(),
            'hold_until' => $holdUntil->toIso8601String(),
            'correlation_id' => $dto->correlationId,
            'extended' => $dto->extendedHold,
        ];

        $this->redis->setex($slotKey, $holdMinutes * 60, json_encode($holdData));

        $this->logger->info('Sports slot held', [
            'user_id' => $dto->userId,
            'venue_id' => $dto->venueId,
            'trainer_id' => $dto->trainerId,
            'slot_start' => $dto->slotStart,
            'hold_until' => $holdUntil->toIso8601String(),
            'correlation_id' => $dto->correlationId,
            'extended' => $dto->extendedHold,
        ]);

        return [
            'success' => true,
            'message' => 'Slot successfully held',
            'hold_until' => $holdUntil->toIso8601String(),
            'hold_id' => $slotKey,
            'biometric_verified' => !empty($dto->biometricData),
        ];
    }

    public function confirmBooking(RealTimeBookingDto $dto, array $paymentData): Booking
    {
        $this->fraud->check(
            userId: $dto->userId,
            operationType: 'booking_confirmation',
            amount: intval($paymentData['amount'] ?? 0),
            correlationId: $dto->correlationId,
        );

        return $this->db->transaction(function () use ($dto, $paymentData) {
            $slotKey = $this->generateSlotKey($dto->venueId, $dto->trainerId, $dto->slotStart);
            $holdData = $this->redis->get($slotKey);

            if ($holdData === null) {
                throw new \RuntimeException('Slot hold has expired. Please select another time.');
            }

            $holdInfo = json_decode($holdData, true);
            if (intval($holdInfo['user_id']) !== $dto->userId) {
                throw new \RuntimeException('This slot is held by another user.');
            }

            $biometricHash = $this->hashBiometricData($dto->biometricData);
            if (!empty($dto->biometricData) && $holdInfo['biometric_hash'] !== $biometricHash) {
                throw new \RuntimeException('Biometric verification failed.');
            }

            $booking = Booking::create([
                'uuid' => Str::uuid()->toString(),
                'tenant_id' => $dto->tenantId,
                'business_group_id' => $dto->businessGroupId,
                'user_id' => $dto->userId,
                'venue_id' => $dto->venueId,
                'trainer_id' => $dto->trainerId,
                'slot_start' => $dto->slotStart,
                'slot_end' => $dto->slotEnd,
                'booking_type' => $dto->bookingType,
                'status' => 'confirmed',
                'biometric_hash' => $biometricHash,
                'biometric_data' => !empty($dto->biometricData) ? json_encode($dto->biometricData) : null,
                'payment_transaction_id' => $paymentData['transaction_id'] ?? null,
                'amount' => floatval($paymentData['amount'] ?? 0),
                'correlation_id' => $dto->correlationId,
                'tags' => json_encode(['real_time_booking', 'biometric_verified']),
            ]);

            $this->redis->del($slotKey);

            event(new BookingConfirmedEvent(
                bookingId: $booking->id,
                userId: $dto->userId,
                venueId: $dto->venueId,
                trainerId: $dto->trainerId,
                slotStart: $dto->slotStart,
                slotEnd: $dto->slotEnd,
                bookingType: $dto->bookingType,
                correlationId: $dto->correlationId,
            ));

            $this->audit->log(
                action: 'booking_confirmed',
                entityType: 'sports_booking',
                entityId: $booking->id,
                metadata: [
                    'venue_id' => $dto->venueId,
                    'trainer_id' => $dto->trainerId,
                    'booking_type' => $dto->bookingType,
                    'biometric_verified' => !empty($dto->biometricData),
                    'correlation_id' => $dto->correlationId,
                ]
            );

            $this->logger->info('Sports booking confirmed', [
                'booking_id' => $booking->id,
                'user_id' => $dto->userId,
                'venue_id' => $dto->venueId,
                'trainer_id' => $dto->trainerId,
                'amount' => $paymentData['amount'] ?? 0,
                'correlation_id' => $dto->correlationId,
            ]);

            return $booking;
        });
    }

    public function releaseSlot(int $venueId, ?int $trainerId, string $slotStart, int $userId, string $correlationId): void
    {
        $slotKey = $this->generateSlotKey($venueId, $trainerId, $slotStart);
        $holdData = $this->redis->get($slotKey);

        if ($holdData !== null) {
            $holdInfo = json_decode($holdData, true);
            if (intval($holdInfo['user_id']) === $userId) {
                $this->redis->del($slotKey);

                $this->logger->info('Sports slot released', [
                    'user_id' => $userId,
                    'venue_id' => $venueId,
                    'trainer_id' => $trainerId,
                    'slot_start' => $slotStart,
                    'correlation_id' => $correlationId,
                ]);
            }
        }
    }

    public function getAvailableSlots(int $venueId, ?int $trainerId, string $date): array
    {
        $cacheKey = "sports:available_slots:{$venueId}:{$trainerId}:{$date}";
        
        $cached = $this->cache->get($cacheKey);
        if ($cached !== null) {
            return json_decode($cached, true);
        }

        $slots = [];
        $startTime = \Carbon\Carbon::parse($date)->startOfDay();
        $endTime = \Carbon\Carbon::parse($date)->endOfDay();

        $currentTime = $startTime->copy();
        while ($currentTime->addHour()->lte($endTime)) {
            $slotStart = $currentTime->format('Y-m-d H:i:s');
            $slotEnd = $currentTime->copy()->addHour()->format('Y-m-d H:i:s');
            
            $slotKey = $this->generateSlotKey($venueId, $trainerId, $slotStart);
            $isHeld = $this->redis->exists($slotKey);
            
            $isBooked = Booking::where('venue_id', $venueId)
                ->when($trainerId, fn ($q) => $q->where('trainer_id', $trainerId))
                ->where('slot_start', $slotStart)
                ->whereIn('status', ['confirmed', 'checked_in'])
                ->exists();

            if (!$isHeld && !$isBooked) {
                $slots[] = [
                    'start' => $slotStart,
                    'end' => $slotEnd,
                    'available' => true,
                ];
            }

            $currentTime->addHour();
        }

        $this->cache->put($cacheKey, json_encode($slots), 300);

        return $slots;
    }

    public function verifyBiometricOnCheckIn(int $bookingId, int $userId, array $biometricData, string $correlationId): bool
    {
        $booking = Booking::findOrFail($bookingId);

        if ($booking->user_id !== $userId) {
            throw new \RuntimeException('Unauthorized access to booking.');
        }

        if ($booking->status !== 'confirmed') {
            throw new \RuntimeException('Booking is not in confirmed status.');
        }

        $currentHash = $this->hashBiometricData($biometricData);
        
        if ($booking->biometric_hash !== null && $booking->biometric_hash !== $currentHash) {
            $this->logger->warning('Biometric verification failed', [
                'booking_id' => $bookingId,
                'user_id' => $booking->user_id,
                'correlation_id' => $correlationId,
            ]);

            return false;
        }

        $booking->update([
            'status' => 'checked_in',
            'check_in_time' => now(),
        ]);

        $this->audit->log(
            action: 'biometric_check_in',
            entityType: 'sports_booking',
            entityId: $bookingId,
            metadata: [
                'verified' => true,
                'correlation_id' => $correlationId,
            ]
        );

        return true;
    }

    public function extendHold(int $venueId, ?int $trainerId, string $slotStart, int $userId, string $correlationId): array
    {
        $slotKey = $this->generateSlotKey($venueId, $trainerId, $slotStart);
        $holdData = $this->redis->get($slotKey);

        if ($holdData === null) {
            throw new \RuntimeException('Slot hold not found or has expired.');
        }

        $holdInfo = json_decode($holdData, true);
        if (intval($holdInfo['user_id']) !== $userId) {
            throw new \RuntimeException('This slot is held by another user.');
        }

        $extendedHoldUntil = now()->addMinutes(self::SLOT_HOLD_EXTENDED_MINUTES);
        $holdInfo['hold_until'] = $extendedHoldUntil->toIso8601String();
        $holdInfo['extended'] = true;
        $holdInfo['correlation_id'] = $correlationId;

        $this->redis->setex($slotKey, self::SLOT_HOLD_EXTENDED_MINUTES * 60, json_encode($holdInfo));

        $this->logger->info('Sports slot hold extended', [
            'user_id' => $userId,
            'venue_id' => $venueId,
            'trainer_id' => $trainerId,
            'slot_start' => $slotStart,
            'extended_until' => $extendedHoldUntil->toIso8601String(),
            'correlation_id' => $correlationId,
        ]);

        return [
            'success' => true,
            'hold_until' => $extendedHoldUntil->toIso8601String(),
        ];
    }

    private function generateSlotKey(int $venueId, ?int $trainerId, string $slotStart): string
    {
        $trainerPart = $trainerId !== null ? ":{$trainerId}" : '';
        return "sports:slot:hold:{$venueId}{$trainerPart}:{$slotStart}";
    }

    private function hashBiometricData(array $biometricData): string
    {
        if (empty($biometricData)) {
            return '';
        }

        $dataString = json_encode($biometricData, JSON_THROW_ON_ERROR);
        return hash('sha256', $dataString . self::BIOMETRIC_HASH_SALT);
    }
}
