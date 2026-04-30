<?php

declare(strict_types=1);

namespace Modules\Veterinary\Domain\Entities;

use Carbon\CarbonImmutable;

final readonly class VeterinaryAppointment
{
    public function __construct(
        public int $id,
        public string $uuid,
        public int $tenantId,
        public int $clinicId,
        public ?int $veterinarianId,
        public int $petId,
        public ?int $serviceId,
        public int $clientId,
        public CarbonImmutable $appointmentAt,
        public string $status,
        public ?int $finalPrice,
        public ?string $paymentStatus,
        public ?string $symptoms,
        public ?string $cancellationReason,
        public ?array $tags,
        public ?string $correlationId,
        public CarbonImmutable $createdAt,
        public CarbonImmutable $updatedAt,
    ) {}

    public function isPending(): bool
    {
        return $this->status === 'pending';
    }

    public function isConfirmed(): bool
    {
        return $this->status === 'confirmed';
    }

    public function isCancelled(): bool
    {
        return $this->status === 'cancelled';
    }

    public function isCompleted(): bool
    {
        return $this->status === 'completed';
    }

    public function canComplete(): bool
    {
        return $this->isConfirmed() || $this->status === 'in_progress';
    }
}
