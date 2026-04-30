<?php

declare(strict_types=1);

namespace Modules\BeautyMasters\Domain\DTOs;

final readonly class UpdateAppointmentDTO
{
    public function __construct(
        public int $id,
        public ?\DateTimeImmutable $startTime,
        public ?\DateTimeImmutable $endTime,
        public ?int $masterId,
        public ?string $status,
        public ?float $discountAmount,
        public ?string $notes,
        public ?array $clientNotes,
    ) {
    }

    public static function fromArray(array $data): self
    {
        return new self(
            id: $data['id'],
            startTime: $data['start_time'] ? new \DateTimeImmutable($data['start_time']) : null,
            endTime: $data['end_time'] ? new \DateTimeImmutable($data['end_time']) : null,
            masterId: $data['master_id'] ?? null,
            status: $data['status'] ?? null,
            discountAmount: $data['discount_amount'] ? (float) $data['discount_amount'] : null,
            notes: $data['notes'] ?? null,
            clientNotes: $data['client_notes'] ?? null,
        );
    }

    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'start_time' => $this->startTime?->format('Y-m-d H:i:s'),
            'end_time' => $this->endTime?->format('Y-m-d H:i:s'),
            'master_id' => $this->masterId,
            'status' => $this->status,
            'discount_amount' => $this->discountAmount,
            'notes' => $this->notes,
            'client_notes' => $this->clientNotes,
        ];
    }
}
