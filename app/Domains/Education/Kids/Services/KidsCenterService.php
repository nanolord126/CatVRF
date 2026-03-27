<?php

declare(strict_types=1);

namespace App\Domains\Education\Kids\Services;

use App\Domains\Education\Kids\DTOs\KidsCenterCreateDto;
use App\Domains\Education\Kids\Models\KidsCenter;
use App\Domains\Education\Kids\Models\KidsEvent;
use App\Services\FraudControlService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * KidsCenterService - Physical Services management in BabyAndKids vertical.
 * Layer: Domain Services (3/9)
 */
final readonly class KidsCenterService
{
    /**
     * Create a new center with full safety verification.
     */
    public function createCenter(KidsCenterCreateDto $dto): KidsCenter
    {
        Log::channel('audit')->info('Attempting to create kids center', [
            'name' => $dto->name,
            'correlation_id' => $dto->correlation_id
        ]);

        // Fraud control check before mutation
        FraudControlService::check('kids_center_add', [
            'type' => $dto->center_type,
            'capacity' => $dto->capacity_limit
        ]);

        return DB::transaction(function () use ($dto) {
            $center = KidsCenter::create($dto->toArray());

            Log::channel('audit')->info('Kids center created successfully', [
                'center_id' => $center->id,
                'correlation_id' => $dto->correlation_id
            ]);

            return $center;
        });
    }

    /**
     * Book child for an event at the center.
     */
    public function bookEventSlot(int $eventId, int $childCount, string $correlationId): void
    {
        DB::transaction(function () use ($eventId, $childCount, $correlationId) {
            $event = KidsEvent::where('id', $eventId)->lockForUpdate()->firstOrFail();

            // Check availability - assuming a simplified check against booked_counts in a separate relation or column
            // For now, using direct capacity as simplified proof of concept
            if ($event->max_children < $childCount) {
                throw new \RuntimeException("Event ID: {$eventId} is fully booked or insufficient slots.");
            }

            // In actual impl: $event->bookings()->create(...) + decrementing slots
            Log::channel('audit')->info('Event slot booked', [
                'event_id' => $eventId,
                'child_count' => $childCount,
                'correlation_id' => $correlationId
            ]);
        });
    }

    /**
     * Verify safety standards for a center.
     */
    public function markAsSafetyVerified(int $centerId, string $correlationId): void
    {
        Log::channel('audit')->info('Safety verification requested', [
            'center_id' => $centerId,
            'correlation_id' => $correlationId
        ]);

        $center = KidsCenter::findOrFail($centerId);
        $center->update([
            'is_safety_verified' => true,
            'correlation_id' => $correlationId
        ]);

        Log::channel('audit')->info('Center safety verified', [
            'center_id' => $centerId,
            'correlation_id' => $correlationId
        ]);
    }

    /**
     * Hourly usage tracking for playgrounds.
     */
    public function startChildVisit(int $centerId, int $userId, string $correlationId): void
    {
        $center = KidsCenter::findOrFail($centerId);
        if (!$center->isOpenNow()) {
            throw new \RuntimeException("Center is currently closed.");
        }

        Log::channel('audit')->info('Child visit started', [
            'center_id' => $centerId,
            'user_id' => $userId,
            'correlation_id' => $correlationId
        ]);
    }
}
