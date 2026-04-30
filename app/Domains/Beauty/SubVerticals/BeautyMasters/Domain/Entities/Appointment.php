<?php

declare(strict_types=1);

namespace Modules\BeautyMasters\Domain\Entities;

final readonly class Appointment
{
    public function __construct(
        public int $id,
        public int $venueId,
        public int $masterId,
        public int $clientId,
        public int $serviceId,
        public \DateTimeImmutable $startTime,
        public \DateTimeImmutable $endTime,
        public string $status,
        public float $price,
        public float $discountAmount,
        public float $finalPrice,
        public string $currency,
        public string $paymentStatus,
        public ?int $paymentId,
        public ?string $notes,
        public ?array $clientNotes,
        public bool $isOnlineBooking,
        public string $bookingSource,
        public ?\DateTimeImmutable $confirmedAt,
        public ?\DateTimeImmutable $completedAt,
        public ?\DateTimeImmutable $cancelledAt,
        public ?string $cancellationReason,
        public int $reminderSent24h,
        public int $reminderSent2h,
        public ?array $metadata,
        public \DateTimeImmutable $createdAt,
        public ?\DateTimeImmutable $updatedAt,
        public ?\DateTimeImmutable $deletedAt,
    ) {
    }

    public static function fromArray(array $data): self
    {
        return new self(
            id: $data['id'],
            venueId: $data['venue_id'],
            masterId: $data['master_id'],
            clientId: $data['client_id'],
            serviceId: $data['service_id'],
            startTime: new \DateTimeImmutable($data['start_time']),
            endTime: new \DateTimeImmutable($data['end_time']),
            status: $data['status'],
            price: (float) $data['price'],
            discountAmount: (float) $data['discount_amount'],
            finalPrice: (float) $data['final_price'],
            currency: $data['currency'] ?? 'RUB',
            paymentStatus: $data['payment_status'],
            paymentId: $data['payment_id'] ?? null,
            notes: $data['notes'] ?? null,
            clientNotes: $data['client_notes'] ?? null,
            isOnlineBooking: (bool) $data['is_online_booking'],
            bookingSource: $data['booking_source'] ?? 'manual',
            confirmedAt: $data['confirmed_at'] ? new \DateTimeImmutable($data['confirmed_at']) : null,
            completedAt: $data['completed_at'] ? new \DateTimeImmutable($data['completed_at']) : null,
            cancelledAt: $data['cancelled_at'] ? new \DateTimeImmutable($data['cancelled_at']) : null,
            cancellationReason: $data['cancellation_reason'] ?? null,
            reminderSent24h: (int) $data['reminder_sent_24h'],
            reminderSent2h: (int) $data['reminder_sent_2h'],
            metadata: $data['metadata'] ?? null,
            createdAt: new \DateTimeImmutable($data['created_at']),
            updatedAt: $data['updated_at'] ? new \DateTimeImmutable($data['updated_at']) : null,
            deletedAt: $data['deleted_at'] ? new \DateTimeImmutable($data['deleted_at']) : null,
        );
    }

    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'venue_id' => $this->venueId,
            'master_id' => $this->masterId,
            'client_id' => $this->clientId,
            'service_id' => $this->serviceId,
            'start_time' => $this->startTime->format('Y-m-d H:i:s'),
            'end_time' => $this->endTime->format('Y-m-d H:i:s'),
            'status' => $this->status,
            'price' => $this->price,
            'discount_amount' => $this->discountAmount,
            'final_price' => $this->finalPrice,
            'currency' => $this->currency,
            'payment_status' => $this->paymentStatus,
            'payment_id' => $this->paymentId,
            'notes' => $this->notes,
            'client_notes' => $this->clientNotes,
            'is_online_booking' => $this->isOnlineBooking,
            'booking_source' => $this->bookingSource,
            'confirmed_at' => $this->confirmedAt?->format('Y-m-d H:i:s'),
            'completed_at' => $this->completedAt?->format('Y-m-d H:i:s'),
            'cancelled_at' => $this->cancelledAt?->format('Y-m-d H:i:s'),
            'cancellation_reason' => $this->cancellationReason,
            'reminder_sent_24h' => $this->reminderSent24h,
            'reminder_sent_2h' => $this->reminderSent2h,
            'metadata' => $this->metadata,
            'created_at' => $this->createdAt->format('Y-m-d H:i:s'),
            'updated_at' => $this->updatedAt?->format('Y-m-d H:i:s'),
            'deleted_at' => $this->deletedAt?->format('Y-m-d H:i:s'),
        ];
    }

    public function isConfirmed(): bool
    {
        return in_array($this->status, ['confirmed', 'in_progress', 'completed', 'paid'], true);
    }

    public function isPaid(): bool
    {
        return $this->paymentStatus === 'paid';
    }

    public function canBeCancelled(): bool
    {
        return in_array($this->status, ['pending', 'confirmed'], true) && $this->startTime > new \DateTimeImmutable();
    }

    public function isPast(): bool
    {
        return $this->endTime < new \DateTimeImmutable();
    }
}
