<?php

declare(strict_types=1);

namespace App\Domains\RealEstate\Services;

use Illuminate\Contracts\Events\Dispatcher as EventDispatcher;

use Psr\Log\LoggerInterface;

use Carbon\CarbonImmutable;

use App\Domains\RealEstate\Exceptions\RealEstateValidationException;

use App\Services\FraudControlService;
use App\Services\AuditService;
use App\Services\Security\IdempotencyService;
use App\Domains\RealEstate\Models\ViewingAppointment;
use App\Domains\RealEstate\Models\Property;
use App\Domains\RealEstate\DTOs\BookViewingDto;
use App\Domains\RealEstate\DTOs\ConfirmViewingDto;
use App\Domains\RealEstate\DTOs\CancelViewingDto;
use App\Domains\RealEstate\Domain\Enums\ViewingStatusEnum;
use App\Domains\RealEstate\Domain\Events\ViewingBooked;
use App\Domains\RealEstate\Domain\Events\ViewingConfirmed;
use App\Domains\RealEstate\Domain\Events\ViewingCancelled;
use App\Domains\RealEstate\Domain\Events\ViewingStarted;
use Illuminate\Cache\CacheManager;
use Illuminate\Database\DatabaseManager;
use Illuminate\Log\LogManager;
use Illuminate\Support\Str;
use Exception;
use Carbon\Carbon;

final readonly class RealTimeViewingBookingService
{
    private const SLOT_HOLD_DURATION_B2C_MINUTES = 15;

    private const SLOT_HOLD_DURATION_B2B_MINUTES = 60;

    private const MAX_VIEWINGS_PER_DAY = 10;

    private const MIN_VIEWING_INTERVAL_MINUTES = 30;

    private const FACEID_VERIFICATION_TTL_SECONDS = 300;

    public function __construct(private readonly EventDispatcher $eventDispatcher,
        private readonly LoggerInterface $logger,
        private readonly FraudControlService $fraud,
        private readonly AuditService $audit,
        private readonly IdempotencyService $idempotency,
        private readonly DatabaseManager $db,
        private readonly LogManager $log,
        private readonly CacheManager $cache,) {}

    public function bookViewingSlot(BookViewingDto $dto): ViewingAppointment
    {
        $this->fraud->check(
            userId: $dto->buyerId,
            operationType: 'viewing_booking',
            amount: 0,
            ipAddress: request()->ip(),
            deviceFingerprint: request()->header('X-Device-Fingerprint'),
            correlationId: $dto->correlationId,
        );

        $this->validateViewingLimits($dto->buyerId, $dto->scheduledAt);
        $this->validatePropertyAvailability($dto->propertyId, $dto->scheduledAt);
        $this->validateSlotAvailability($dto->propertyId, $dto->scheduledAt, $dto->durationMinutes);

        if ($dto->idempotencyKey !== null) {
            $existing = $this->idempotency->checkAndLock($dto->idempotencyKey, 'viewing_booking');
            if ($existing !== null) {
                return ViewingAppointment::findOrFail($existing['viewing_id']);
            }
        }

        return $this->db->transaction(function () use ($dto) {
            $holdDuration = $dto->isB2b
                ? self::SLOT_HOLD_DURATION_B2B_MINUTES
                : self::SLOT_HOLD_DURATION_B2C_MINUTES;

            $viewing = ViewingAppointment::create([
                'tenant_id' => $dto->tenantId,
                'business_group_id' => $dto->businessGroupId,
                'uuid' => Str::uuid()->toString(),
                'correlation_id' => $dto->correlationId,
                'property_id' => $dto->propertyId,
                'buyer_id' => $dto->buyerId,
                'agent_id' => $dto->agentId,
                'scheduled_at' => $dto->scheduledAt,
                'duration_minutes' => $dto->durationMinutes,
                'status' => ViewingStatusEnum::PENDING->value,
                'hold_until' => CarbonImmutable::now()->addMinutes($holdDuration),
                'is_b2b' => $dto->isB2b,
                'faceid_verification_token' => $this->generateFaceIdToken($dto->buyerId),
                'faceid_verified_at' => null,
                'contact_phone' => $dto->contactPhone,
                'contact_email' => $dto->contactEmail,
                'special_requests' => $dto->specialRequests,
                'metadata' => [
                    'ip_address' => request()->ip(),
                    'user_agent' => request()->userAgent(),
                    'device_fingerprint' => request()->header('X-Device-Fingerprint'),
                ],
                'tags' => array_merge($dto->tags ?? [], ['viewing', 'booking']),
            ]);

            $this->reserveSlotInCache($viewing);

            $this->log->channel('audit')->$this->logger->info('Viewing slot booked', [
                'viewing_id' => $viewing->id,
                'viewing_uuid' => $viewing->uuid,
                'property_id' => $dto->propertyId,
                'buyer_id' => $dto->buyerId,
                'scheduled_at' => $dto->scheduledAt,
                'hold_until' => $viewing->hold_until,
                'correlation_id' => $dto->correlationId,
                'tenant_id' => $dto->tenantId,
            ]);

            $this->eventDispatcher->dispatch(new ViewingBooked($viewing, $dto->correlationId));

            if ($dto->idempotencyKey !== null) {
                $this->idempotency->store($dto->idempotencyKey, 'viewing_booking', [
                    'viewing_id' => $viewing->id,
                ]);
            }

            return $viewing;
        });
    }

    public function confirmViewingWithFaceID(ConfirmViewingDto $dto): ViewingAppointment
    {
        $this->fraud->check(
            userId: $dto->buyerId,
            operationType: 'faceid_verification',
            amount: 0,
            ipAddress: request()->ip(),
            deviceFingerprint: request()->header('X-Device-Fingerprint'),
            correlationId: $dto->correlationId,
        );

        $viewing = ViewingAppointment::where('uuid', $dto->viewingUuid)
            ->where('tenant_id', $dto->tenantId)
            ->where('buyer_id', $dto->buyerId)
            ->lockForUpdate()
            ->firstOrFail();

        $this->validateViewingConfirmation($viewing);
        $this->verifyFaceIdToken($viewing, $dto->faceIdToken);

        return $this->db->transaction(function () use ($dto, $viewing) {
            $viewing->update([
                'status' => ViewingStatusEnum::CONFIRMED->value,
                'faceid_verified_at' => CarbonImmutable::now(),
                'faceid_confidence_score' => $dto->confidenceScore,
                'metadata' => array_merge($viewing->metadata ?? [], [
                    'faceid_verified' => true,
                    'faceid_method' => $dto->verificationMethod,
                ]),
            ]);

            $this->log->channel('audit')->$this->logger->info('Viewing confirmed with FaceID', [
                'viewing_id' => $viewing->id,
                'viewing_uuid' => $viewing->uuid,
                'buyer_id' => $dto->buyerId,
                'confidence_score' => $dto->confidenceScore,
                'correlation_id' => $dto->correlationId,
                'tenant_id' => $dto->tenantId,
            ]);

            $this->eventDispatcher->dispatch(new ViewingConfirmed($viewing, $dto->correlationId));

            return $viewing->fresh();
        });
    }

    public function cancelViewing(CancelViewingDto $dto): ViewingAppointment
    {
        $this->fraud->check(
            userId: $dto->buyerId,
            operationType: 'viewing_cancellation',
            amount: 0,
            ipAddress: request()->ip(),
            deviceFingerprint: request()->header('X-Device-Fingerprint'),
            correlationId: $dto->correlationId,
        );

        $viewing = ViewingAppointment::where('uuid', $dto->viewingUuid)
            ->where('tenant_id', $dto->tenantId)
            ->where('buyer_id', $dto->buyerId)
            ->lockForUpdate()
            ->firstOrFail();

        $this->validateViewingCancellation($viewing, $dto->reason);

        return $this->db->transaction(function () use ($dto, $viewing) {
            $viewing->update([
                'status' => ViewingStatusEnum::CANCELLED->value,
                'cancelled_at' => CarbonImmutable::now(),
                'cancellation_reason' => $dto->reason,
                'metadata' => array_merge($viewing->metadata ?? [], [
                    'cancelled_by' => $dto->cancelledBy,
                    'cancellation_notes' => $dto->cancellationNotes,
                ]),
            ]);

            $this->releaseSlotInCache($viewing);

            $this->log->channel('audit')->$this->logger->info('Viewing cancelled', [
                'viewing_id' => $viewing->id,
                'viewing_uuid' => $viewing->uuid,
                'buyer_id' => $dto->buyerId,
                'reason' => $dto->reason,
                'correlation_id' => $dto->correlationId,
                'tenant_id' => $dto->tenantId,
            ]);

            $this->eventDispatcher->dispatch(new ViewingCancelled($viewing, $dto->correlationId));

            return $viewing->fresh();
        });
    }

    public function startViewing(string $viewingUuid, int $tenantId, string $correlationId): ViewingAppointment
    {
        $viewing = ViewingAppointment::where('uuid', $viewingUuid)
            ->where('tenant_id', $tenantId)
            ->lockForUpdate()
            ->firstOrFail();

        $this->validateViewingStart($viewing);

        return $this->db->transaction(function () use ($viewing, $correlationId) {
            $viewing->update([
                'status' => ViewingStatusEnum::IN_PROGRESS->value,
                'started_at' => CarbonImmutable::now(),
            ]);

            $this->log->channel('audit')->$this->logger->info('Viewing started', [
                'viewing_id' => $viewing->id,
                'viewing_uuid' => $viewing->uuid,
                'correlation_id' => $correlationId,
                'tenant_id' => $tenantId,
            ]);

            $this->eventDispatcher->dispatch(new ViewingStarted($viewing, $correlationId));

            return $viewing->fresh();
        });
    }

    public function getAvailableSlots(int $propertyId, string $date, int $tenantId): array
    {
        $property = Property::where('id', $propertyId)
            ->where('tenant_id', $tenantId)
            ->firstOrFail();

        $dateObj = Carbon::parse($date);
        $startOfDay = $dateObj->startOfDay();
        $endOfDay = $dateObj->endOfDay();

        $existingViewings = ViewingAppointment::where('property_id', $propertyId)
            ->whereBetween('scheduled_at', [$startOfDay, $endOfDay])
            ->whereIn('status', [ViewingStatusEnum::PENDING->value, ViewingStatusEnum::CONFIRMED->value])
            ->get();

        $allSlots = $this->generateTimeSlots($property->working_hours_start ?? '09:00', $property->working_hours_end ?? '21:00');
        $availableSlots = [];

        foreach ($allSlots as $slot) {
            $slotStart = Carbon::parse($date.' '.$slot);
            $slotEnd = $slotStart->copy()->addMinutes(30);

            $isAvailable = true;
            foreach ($existingViewings as $viewing) {
                $viewingStart = $viewing->scheduled_at;
                $viewingEnd = $viewingStart->copy()->addMinutes($viewing->duration_minutes);

                if ($slotStart < $viewingEnd && $slotEnd > $viewingStart) {
                    $isAvailable = false;
                    break;
                }
            }

            if ($isAvailable) {
                $availableSlots[] = [
                    'time' => $slot,
                    'available' => true,
                ];
            }
        }

        return [
            'property_id' => $propertyId,
            'date' => $date,
            'available_slots' => $availableSlots,
            'total_available' => count($availableSlots),
        ];
    }

    public function getMyViewings(int $buyerId, int $tenantId, ?string $status = null): array
    {
        $query = ViewingAppointment::where('buyer_id', $buyerId)
            ->where('tenant_id', $tenantId)
            ->with(['property', 'agent']);

        if ($status !== null) {
            $query->where('status', $status);
        }

        $viewings = $query->orderBy('scheduled_at', 'desc')->get();

        return $viewings->map(function ($viewing) {
            return [
                'uuid' => $viewing->uuid,
                'property_id' => $viewing->property_id,
                'property_title' => $viewing->property->title ?? 'N/A',
                'property_address' => $viewing->property->address ?? 'N/A',
                'scheduled_at' => $viewing->scheduled_at->toIso8601String(),
                'duration_minutes' => $viewing->duration_minutes,
                'status' => $viewing->status,
                'hold_until' => $viewing->hold_until?->toIso8601String(),
                'faceid_verified' => $viewing->faceid_verified_at !== null,
                'is_b2b' => $viewing->is_b2b,
                'created_at' => $viewing->created_at->toIso8601String(),
            ];
        })->toArray();
    }

    private function validateViewingLimits(int $buyerId, string $scheduledAt): void
    {
        $today = Carbon::parse($scheduledAt)->startOfDay();
        $endOfDay = $today->copy()->endOfDay();

        $todayCount = ViewingAppointment::where('buyer_id', $buyerId)
            ->whereBetween('scheduled_at', [$today, $endOfDay])
            ->whereIn('status', [
                ViewingStatusEnum::PENDING->value,
                ViewingStatusEnum::CONFIRMED->value,
                ViewingStatusEnum::IN_PROGRESS->value,
            ])
            ->count();

        if ($todayCount >= self::MAX_VIEWINGS_PER_DAY) {
            throw new RealEstateValidationException(
                sprintf('Maximum %d viewings per day allowed', self::MAX_VIEWINGS_PER_DAY)
            );
        }
    }

    private function validatePropertyAvailability(int $propertyId, string $scheduledAt): void
    {
        $property = Property::where('id', $propertyId)
            ->where('status', 'available')
            ->first();

        if ($property === null) {
            throw new RealEstateValidationException('Property is not available for viewing');
        }

        if ($property->available_until !== null && Carbon::parse($scheduledAt)->gt($property->available_until)) {
            throw new RealEstateValidationException('Property is not available on the selected date');
        }
    }

    private function validateSlotAvailability(int $propertyId, string $scheduledAt, int $durationMinutes): void
    {
        $startTime = Carbon::parse($scheduledAt);
        $endTime = $startTime->copy()->addMinutes($durationMinutes);

        $conflictingViewings = ViewingAppointment::where('property_id', $propertyId)
            ->whereBetween('scheduled_at', [$startTime, $endTime])
            ->whereIn('status', [
                ViewingStatusEnum::PENDING->value,
                ViewingStatusEnum::CONFIRMED->value,
                ViewingStatusEnum::IN_PROGRESS->value,
            ])
            ->where(function ($query) use ($startTime, $endTime) {
                $query->where(function ($q) use ($startTime) {
                    $q->where('scheduled_at', '<=', $startTime)
                        ->whereRaw('scheduled_at + (duration_minutes || \' minutes\')::interval > ?', [$startTime]);
                })->orWhere(function ($q) use ($endTime) {
                    $q->where('scheduled_at', '<', $endTime)
                        ->whereRaw('scheduled_at + (duration_minutes || \' minutes\')::interval >= ?', [$endTime]);
                });
            })
            ->count();

        if ($conflictingViewings > 0) {
            throw new RealEstateValidationException('Selected time slot is already booked');
        }
    }

    private function validateViewingConfirmation(ViewingAppointment $viewing): void
    {
        if ($viewing->status !== ViewingStatusEnum::PENDING->value) {
            throw new RealEstateValidationException('Viewing is not in pending status');
        }

        if ($viewing->hold_until->isPast()) {
            throw new RealEstateValidationException('Hold period has expired');
        }

        if ($viewing->scheduled_at->isPast()) {
            throw new RealEstateValidationException('Viewing time has passed');
        }
    }

    private function validateViewingCancellation(ViewingAppointment $viewing, string $reason): void
    {
        $validReasons = ['buyer_cancellation', 'seller_cancellation', 'property_sold', 'schedule_conflict', 'no_show'];
        if (! in_array($reason, $validReasons, true)) {
            throw new RealEstateValidationException('Invalid cancellation reason');
        }

        if ($viewing->status === ViewingStatusEnum::COMPLETED->value) {
            throw new RealEstateValidationException('Cannot cancel completed viewing');
        }

        if ($viewing->status === ViewingStatusEnum::CANCELLED->value) {
            throw new RealEstateValidationException('Viewing is already cancelled');
        }
    }

    private function validateViewingStart(ViewingAppointment $viewing): void
    {
        if ($viewing->status !== ViewingStatusEnum::CONFIRMED->value) {
            throw new RealEstateValidationException('Viewing must be confirmed before starting');
        }

        if (! $viewing->faceid_verified_at) {
            throw new RealEstateValidationException('FaceID verification required before starting viewing');
        }

        $now = CarbonImmutable::now();
        $startTime = $viewing->scheduled_at;
        $gracePeriodMinutes = 15;

        if ($now->lt($startTime->subMinutes($gracePeriodMinutes))) {
            throw new RealEstateValidationException('Too early to start viewing');
        }

        if ($now->gt($startTime->addMinutes($viewing->duration_minutes))) {
            throw new RealEstateValidationException('Viewing time has passed');
        }
    }

    private function generateFaceIdToken(int $buyerId): string
    {
        $token = hash('sha256', $buyerId.Str::random(32).microtime(true));
        $this->cache->put(
            "faceid:token:{$token}",
            ['buyer_id' => $buyerId, 'created_at' => CarbonImmutable::now()],
            self::FACEID_VERIFICATION_TTL_SECONDS
        );

        return $token;
    }

    private function verifyFaceIdToken(ViewingAppointment $viewing, string $providedToken): void
    {
        $cachedData = $this->cache->get("faceid:token:{$viewing->faceid_verification_token}");

        if ($cachedData === null || $cachedData['buyer_id'] !== $viewing->buyer_id) {
            throw new RealEstateValidationException('Invalid or expired FaceID token');
        }

        if ($providedToken !== $viewing->faceid_verification_token) {
            throw new RealEstateValidationException('FaceID token mismatch');
        }
    }

    private function reserveSlotInCache(ViewingAppointment $viewing): void
    {
        $cacheKey = "viewing:slot:{$viewing->property_id}:{$viewing->scheduled_at->toDateString()}";
        $ttl = $viewing->hold_until->diffInSeconds(CarbonImmutable::now());

        $this->cache->lock($cacheKey)->block(5, function () use ($cacheKey, $viewing, $ttl) {
            $slots = $this->cache->get($cacheKey, []);
            $slots[] = [
                'viewing_id' => $viewing->id,
                'time' => $viewing->scheduled_at->toTimeString(),
                'duration' => $viewing->duration_minutes,
            ];
            $this->cache->put($cacheKey, $slots, $ttl);
        });
    }

    private function releaseSlotInCache(ViewingAppointment $viewing): void
    {
        $cacheKey = "viewing:slot:{$viewing->property_id}:{$viewing->scheduled_at->toDateString()}";

        $this->cache->lock($cacheKey)->block(5, function () use ($cacheKey, $viewing) {
            $slots = $this->cache->get($cacheKey, []);
            $slots = array_filter($slots, function ($slot) use ($viewing) {
                return $slot['viewing_id'] !== $viewing->id;
            });
            $this->cache->put($cacheKey, array_values($slots), 86400);
        });
    }

    private function generateTimeSlots(string $startTime, string $endTime): array
    {
        $slots = [];
        $current = Carbon::parse($startTime);
        $end = Carbon::parse($endTime);

        while ($current->lt($end)) {
            $slots[] = $current->format('H:i');
            $current->addMinutes(30);
        }

        return $slots;
    }
}
