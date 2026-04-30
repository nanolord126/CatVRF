<?php

declare(strict_types=1);

namespace Modules\Video\Domain\DTOs;

use Modules\Video\Domain\ValueObjects\ParticipantRole;
use Spatie\LaravelData\Attributes\WithCast;
use Spatie\LaravelData\Casts\EnumCast;
use Spatie\LaravelData\Data;

final readonly class JoinRoomDTO extends Data
{
    public function __construct(
        public string $roomId,
        public string $userId,
        #[WithCast(EnumCast::class)]
        public ParticipantRole $role,
        public ?string $displayName = null,
        public ?array $metadata = null,
    ) {
    }

    public static function fromArray(array $data): self
    {
        return new self(
            roomId: $data['room_id'],
            userId: $data['user_id'],
            role: ParticipantRole::from($data['role']),
            displayName: $data['display_name'] ?? null,
            metadata: $data['metadata'] ?? null,
        );
    }
}
