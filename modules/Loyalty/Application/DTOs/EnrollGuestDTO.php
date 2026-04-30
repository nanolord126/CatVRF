<?php

declare(strict_types=1);

namespace Modules\Loyalty\Application\DTOs;

use DateTimeImmutable;

final readonly class EnrollGuestDTO
{
    private function __construct(
        public string $programId,
        public int $guestId,
        public ?int $userId,
        public ?DateTimeImmutable $birthday,
        public ?int $referredBy,
        public ?array $preferences,
        public ?string $correlationId
    ) {
    }

    public static function fromArray(array $data): self
    {
        return new self(
            programId: $data['program_id'],
            guestId: $data['guest_id'],
            userId: $data['user_id'] ?? null,
            birthday: isset($data['birthday']) ? DateTimeImmutable::createFromFormat('Y-m-d', $data['birthday']) : null,
            referredBy: $data['referred_by'] ?? null,
            preferences: $data['preferences'] ?? null,
            correlationId: $data['correlation_id'] ?? null
        );
    }

    public function toArray(): array
    {
        return [
            'program_id' => $this->programId,
            'guest_id' => $this->guestId,
            'user_id' => $this->userId,
            'birthday' => $this->birthday?->format('Y-m-d'),
            'referred_by' => $this->referredBy,
            'preferences' => $this->preferences,
            'correlation_id' => $this->correlationId,
        ];
    }
}
