<?php

declare(strict_types=1);

namespace Modules\BeautyMasters\Domain\DTOs;

use readonly;

final readonly class CreateAppointmentDTO
{
    public function __construct(
        public int $venueId,
        public int $masterId,
        public int $clientId,
        public int $serviceId,
        public \DateTimeImmutable $startTime,
        public \DateTimeImmutable $endTime,
        public float $price,
        public ?float $discountAmount,
        public string $currency,
        public ?string $notes,
        public ?array $clientNotes,
        public bool $isOnlineBooking,
        public string $bookingSource,
    ) {
    }

    public static function fromArray(array $data): self
    {
        return new self(
            venueId: $data['venue_id'],
            masterId: $data['master_id'],
            clientId: $data['client_id'],
            serviceId: $data['service_id'],
            startTime: new \DateTimeImmutable($data['start_time']),
            endTime: new \DateTimeImmutable($data['end_time']),
            price: (float) $data['price'],
            discountAmount: $data['discount_amount'] ? (float) $data['discount_amount'] : null,
            currency: $data['currency'] ?? 'RUB',
            notes: $data['notes'] ?? null,
            clientNotes: $data['client_notes'] ?? null,
            isOnlineBooking: (bool) ($data['is_online_booking'] ?? false),
            bookingSource: $data['booking_source'] ?? 'manual',
        );
    }

    public function getFinalPrice(): float
    {
        return $this->price - ($this->discountAmount ?? 0);
    }

    public function toArray(): array
    {
        return [
            'venue_id' => $this->venueId,
            'master_id' => $this->masterId,
            'client_id' => $this->clientId,
            'service_id' => $this->serviceId,
            'start_time' => $this->startTime->format('Y-m-d H:i:s'),
            'end_time' => $this->endTime->format('Y-m-d H:i:s'),
            'price' => $this->price,
            'discount_amount' => $this->discountAmount,
            'currency' => $this->currency,
            'notes' => $this->notes,
            'client_notes' => $this->clientNotes,
            'is_online_booking' => $this->isOnlineBooking,
            'booking_source' => $this->bookingSource,
        ];
    }
}
