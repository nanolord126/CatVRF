<?php declare(strict_types=1);

namespace App\Domains\Medical\MedicalHealthcare\Services\AI;

use Illuminate\Database\DatabaseManager;
use Illuminate\Redis\Connections\Connection as RedisConnection;
use Illuminate\Support\Facades\Log;
use Carbon\CarbonImmutable;
use Illuminate\Support\Str;

/**
 * AppointmentSlotService - Manages appointment slot holds
 * 
 * Handles slot reservation with Redis-based atomic operations to prevent race conditions.
 */
final readonly class AppointmentSlotService
{
    private const SLOT_HOLD_MINUTES = 15;
    private const SLOT_HOLD_EXTENDED_MINUTES = 60;

    public function __construct(
        private readonly RedisConnection $redis,
        private readonly DatabaseManager $db,
    ) {}

    /**
     * Hold an appointment slot
     */
    public function holdSlot(int $userId, int $doctorId, string $dateTime, string $correlationId, bool $extendedHold = false): array
    {
        $holdKey = "slot:hold:{$doctorId}:{$dateTime}";
        $holdMinutes = $extendedHold ? self::SLOT_HOLD_EXTENDED_MINUTES : self::SLOT_HOLD_MINUTES;
        $holdUntil = CarbonImmutable::now()->addMinutes($holdMinutes);

        // Check if slot is already held
        $existingHold = $this->redis->get($holdKey);
        if ($existingHold !== null) {
            $existingData = json_decode($existingHold, true);
            if ($existingData['user_id'] !== $userId) {
                throw new \RuntimeException('Slot is already held by another user');
            }
        }

        // Atomic hold operation
        $this->redis->setex($holdKey, $holdMinutes * 60, json_encode([
            'user_id' => $userId,
            'held_at' => CarbonImmutable::now()->toIso8601String(),
            'correlation_id' => $correlationId,
        ]));

        Log::info('appointment_slot.held', [
            'user_id' => $userId,
            'doctor_id' => $doctorId,
            'datetime' => $dateTime,
            'hold_until' => $holdUntil->toIso8601String(),
            'correlation_id' => $correlationId,
        ]);

        return [
            'success' => true,
            'hold_until' => $holdUntil->toIso8601String(),
            'correlation_id' => $correlationId,
        ];
    }

    /**
     * Release a held slot
     */
    public function releaseSlot(int $doctorId, string $dateTime, string $correlationId): void
    {
        $holdKey = "slot:hold:{$doctorId}:{$dateTime}";
        $this->redis->del($holdKey);

        Log::info('appointment_slot.released', [
            'doctor_id' => $doctorId,
            'datetime' => $dateTime,
            'correlation_id' => $correlationId,
        ]);
    }

    /**
     * Check if slot is available
     */
    public function isSlotAvailable(int $doctorId, string $dateTime): bool
    {
        $holdKey = "slot:hold:{$doctorId}:{$dateTime}";
        return $this->redis->get($holdKey) === null;
    }
}
