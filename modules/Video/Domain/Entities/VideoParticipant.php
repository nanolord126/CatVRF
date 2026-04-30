<?php

declare(strict_types=1);

namespace Modules\Video\Domain\Entities;

use Modules\Video\Domain\ValueObjects\ParticipantRole;
use Modules\Video\Domain\ValueObjects\ParticipantStatus;

final readonly class VideoParticipant
{
    public function __construct(
        public string $id,
        public string $roomId,
        public string $userId,
        public ParticipantRole $role,
        public ParticipantStatus $status,
        public ?string $livekitParticipantIdentity = null,
        public ?\DateTimeImmutable $joinedAt = null,
        public ?\DateTimeImmutable $leftAt = null,
        public ?array $metadata = null,
        public ?\DateTimeImmutable $createdAt = null,
    ) {
        $this->createdAt ??= new \DateTimeImmutable();
    }

    public static function create(
        string $roomId,
        string $userId,
        ParticipantRole $role,
    ): self {
        return new self(
            id: (string) \Illuminate\Support\Str::uuid(),
            roomId: $roomId,
            userId: $userId,
            role: $role,
            status: ParticipantStatus::INVITED,
        );
    }

    public function join(): self
    {
        if ($this->status !== ParticipantStatus::INVITED && $this->status !== ParticipantStatus::LEFT) {
            throw new \RuntimeException('Participant can only join from INVITED or LEFT status');
        }

        return new self(
            id: $this->id,
            roomId: $this->roomId,
            userId: $this->userId,
            role: $this->role,
            status: ParticipantStatus::JOINED,
            livekitParticipantIdentity: $this->livekitParticipantIdentity,
            joinedAt: new \DateTimeImmutable(),
            leftAt: $this->leftAt,
            metadata: $this->metadata,
            createdAt: $this->createdAt,
        );
    }

    public function leave(): self
    {
        if ($this->status !== ParticipantStatus::JOINED) {
            throw new \RuntimeException('Participant can only leave from JOINED status');
        }

        return new self(
            id: $this->id,
            roomId: $this->roomId,
            userId: $this->userId,
            role: $this->role,
            status: ParticipantStatus::LEFT,
            livekitParticipantIdentity: $this->livekitParticipantIdentity,
            joinedAt: $this->joinedAt,
            leftAt: new \DateTimeImmutable(),
            metadata: $this->metadata,
            createdAt: $this->createdAt,
        );
    }

    public function withLivekitIdentity(string $identity): self
    {
        return new self(
            id: $this->id,
            roomId: $this->roomId,
            userId: $this->userId,
            role: $this->role,
            status: $this->status,
            livekitParticipantIdentity: $identity,
            joinedAt: $this->joinedAt,
            leftAt: $this->leftAt,
            metadata: $this->metadata,
            createdAt: $this->createdAt,
        );
    }

    public function isHost(): bool
    {
        return $this->role === ParticipantRole::HOST;
    }

    public function isCoHost(): bool
    {
        return $this->role === ParticipantRole::CO_HOST;
    }

    public function isViewer(): bool
    {
        return $this->role === ParticipantRole::VIEWER;
    }

    public function isInRoom(): bool
    {
        return $this->status === ParticipantStatus::JOINED;
    }

    public function getDuration(): ?int
    {
        if ($this->joinedAt === null || $this->leftAt === null) {
            return null;
        }

        return $this->leftAt->getTimestamp() - $this->joinedAt->getTimestamp();
    }
}
