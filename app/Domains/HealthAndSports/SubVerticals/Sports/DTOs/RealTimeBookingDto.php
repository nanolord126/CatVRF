<?php

declare(strict_types=1);

namespace App\Domains\Sports\DTOs;

final readonly class RealTimeBookingDto
{
    public function __construct(
        public int $userId,
        public int $tenantId,
        public ?int $businessGroupId,
        public int $venueId,
        public ?int $trainerId,
        public string $slotStart,
        public string $slotEnd,
        public string $bookingType,
        public array $biometricData,
        public bool $extendedHold,
        public string $correlationId,
        public ?string $idempotencyKey = null,
    ) {}

    public static function from(array $data): self
    {
        return new self(
            userId: $data['user_id'],
            tenantId: $data['tenant_id'],
            businessGroupId: $data['business_group_id'] ?? null,
            venueId: $data['venue_id'],
            trainerId: $data['trainer_id'] ?? null,
            slotStart: $data['slot_start'],
            slotEnd: $data['slot_end'],
            bookingType: $data['booking_type'],
            biometricData: $data['biometric_data'] ?? [],
            extendedHold: $data['extended_hold'] ?? false,
            correlationId: $data['correlation_id'],
            idempotencyKey: $data['idempotency_key'] ?? null,
        );
    }

    public function toArray(): array
    {
        return [
            'user_id' => $this->userId,
            'tenant_id' => $this->tenantId,
            'business_group_id' => $this->businessGroupId,
            'venue_id' => $this->venueId,
            'trainer_id' => $this->trainerId,
            'slot_start' => $this->slotStart,
            'slot_end' => $this->slotEnd,
            'booking_type' => $this->bookingType,
            'biometric_data' => $this->biometricData,
            'extended_hold' => $this->extendedHold,
            'correlation_id' => $this->correlationId,
            'idempotency_key' => $this->idempotencyKey,
        ];
    }

    public static function fromJson(string $json): self
    {
        return self::from(json_decode($json, true, 512, JSON_THROW_ON_ERROR));
    }

    public function toJson(): string
    {
        return json_encode($this->toArray(), JSON_THROW_ON_ERROR);
    }
}
