<?php declare(strict_types=1);

namespace App\Domains\Education\DTOs;

final readonly class SlotHoldDto
{
    public function __construct(
        public string $holdId,
        public int $slotId,
        public int $userId,
        public string $holdExpiresAt,
        public string $status,
        public string $createdAt,
    ) {}

    public function toArray(): array
    {
        return [
            'hold_id' => $this->holdId,
            'slot_id' => $this->slotId,
            'user_id' => $this->userId,
            'hold_expires_at' => $this->holdExpiresAt,
            'status' => $this->status,
            'created_at' => $this->createdAt,
        ];
    }

    public static function fromArray(array $data): self
    {
        return new self(
            holdId: $data['hold_id'],
            slotId: $data['slot_id'],
            userId: $data['user_id'],
            holdExpiresAt: $data['hold_expires_at'],
            status: $data['status'],
            createdAt: $data['created_at'],
        );
    }
}
