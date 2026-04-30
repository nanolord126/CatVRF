<?php

declare(strict_types=1);

namespace Modules\Video\Domain\DTOs;

use Modules\Video\Domain\ValueObjects\RoomType;
use Spatie\LaravelData\Attributes\WithCast;
use Spatie\LaravelData\Casts\EnumCast;
use Spatie\LaravelData\Data;

final readonly class CreateRoomDTO extends Data
{
    public function __construct(
        #[WithCast(EnumCast::class)]
        public RoomType $type,
        public string $hostId,
        public ?string $petId = null,
        public ?\DateTimeImmutable $scheduledAt = null,
        public ?array $participantIds = null,
        public bool $requireRecordingConsent = false,
        public ?array $metadata = null,
    ) {
    }

    public static function fromArray(array $data): self
    {
        return new self(
            type: RoomType::from($data['type']),
            hostId: $data['host_id'],
            petId: $data['pet_id'] ?? null,
            scheduledAt: isset($data['scheduled_at']) ? new \DateTimeImmutable($data['scheduled_at']) : null,
            participantIds: $data['participant_ids'] ?? null,
            requireRecordingConsent: $data['require_recording_consent'] ?? false,
            metadata: $data['metadata'] ?? null,
        );
    }
}
