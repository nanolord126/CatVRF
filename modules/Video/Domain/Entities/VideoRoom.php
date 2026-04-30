<?php

declare(strict_types=1);

namespace Modules\Video\Domain\Entities;

use Modules\Video\Domain\ValueObjects\RoomStatus;
use Modules\Video\Domain\ValueObjects\RoomType;
use Modules\Video\Domain\ValueObjects\ParticipantRole;

final readonly class VideoRoom
{
    public function __construct(
        public string $id,
        public int $tenantId,
        public RoomType $type,
        public string $hostId,
        public ?string $petId,
        public RoomStatus $status,
        public ?string $recordingUrl = null,
        public bool $recordingConsentGiven = false,
        public ?string $livekitRoomName = null,
        public ?string $livekitAccessToken = null,
        public ?\DateTimeImmutable $scheduledAt = null,
        public ?\DateTimeImmutable $startedAt = null,
        public ?\DateTimeImmutable $endedAt = null,
        public ?array $metadata = null,
        public ?\DateTimeImmutable $createdAt = null,
        public ?\DateTimeImmutable $updatedAt = null,
    ) {
        $this->createdAt ??= new \DateTimeImmutable();
        $this->updatedAt ??= new \DateTimeImmutable();
    }

    public static function create(
        RoomType $type,
        string $hostId,
        ?string $petId = null,
        ?\DateTimeImmutable $scheduledAt = null,
        ?array $metadata = null,
    ): self {
        return new self(
            id: (string) \Illuminate\Support\Str::uuid(),
            tenantId: tenant('id') ?? 0,
            type: $type,
            hostId: $hostId,
            petId: $petId,
            status: RoomStatus::SCHEDULED,
            scheduledAt: $scheduledAt,
            metadata: $metadata,
        );
    }

    public function start(): self
    {
        if ($this->status !== RoomStatus::SCHEDULED) {
            throw new \RuntimeException('Room can only be started from SCHEDULED status');
        }

        return new self(
            id: $this->id,
            tenantId: $this->tenantId,
            type: $this->type,
            hostId: $this->hostId,
            petId: $this->petId,
            status: RoomStatus::ACTIVE,
            recordingUrl: $this->recordingUrl,
            recordingConsentGiven: $this->recordingConsentGiven,
            livekitRoomName: $this->livekitRoomName,
            livekitAccessToken: $this->livekitAccessToken,
            scheduledAt: $this->scheduledAt,
            startedAt: new \DateTimeImmutable(),
            endedAt: $this->endedAt,
            metadata: $this->metadata,
            createdAt: $this->createdAt,
            updatedAt: new \DateTimeImmutable(),
        );
    }

    public function end(?string $recordingUrl = null): self
    {
        if ($this->status !== RoomStatus::ACTIVE) {
            throw new \RuntimeException('Room can only be ended from ACTIVE status');
        }

        return new self(
            id: $this->id,
            tenantId: $this->tenantId,
            type: $this->type,
            hostId: $this->hostId,
            petId: $this->petId,
            status: RoomStatus::ENDED,
            recordingUrl: $recordingUrl ?? $this->recordingUrl,
            recordingConsentGiven: $this->recordingConsentGiven,
            livekitRoomName: $this->livekitRoomName,
            livekitAccessToken: null, // Invalidate access token
            scheduledAt: $this->scheduledAt,
            startedAt: $this->startedAt,
            endedAt: new \DateTimeImmutable(),
            metadata: $this->metadata,
            createdAt: $this->createdAt,
            updatedAt: new \DateTimeImmutable(),
        );
    }

    public function giveRecordingConsent(): self
    {
        return new self(
            id: $this->id,
            tenantId: $this->tenantId,
            type: $this->type,
            hostId: $this->hostId,
            petId: $this->petId,
            status: $this->status,
            recordingUrl: $this->recordingUrl,
            recordingConsentGiven: true,
            livekitRoomName: $this->livekitRoomName,
            livekitAccessToken: $this->livekitAccessToken,
            scheduledAt: $this->scheduledAt,
            startedAt: $this->startedAt,
            endedAt: $this->endedAt,
            metadata: $this->metadata,
            createdAt: $this->createdAt,
            updatedAt: new \DateTimeImmutable(),
        );
    }

    public function withLivekitCredentials(string $roomName, string $accessToken): self
    {
        return new self(
            id: $this->id,
            tenantId: $this->tenantId,
            type: $this->type,
            hostId: $this->hostId,
            petId: $this->petId,
            status: $this->status,
            recordingUrl: $this->recordingUrl,
            recordingConsentGiven: $this->recordingConsentGiven,
            livekitRoomName: $roomName,
            livekitAccessToken: $accessToken,
            scheduledAt: $this->scheduledAt,
            startedAt: $this->startedAt,
            endedAt: $this->endedAt,
            metadata: $this->metadata,
            createdAt: $this->createdAt,
            updatedAt: new \DateTimeImmutable(),
        );
    }

    public function canStartRecording(): bool
    {
        return $this->recordingConsentGiven && $this->status === RoomStatus::ACTIVE;
    }

    public function isLive(): bool
    {
        return $this->status === RoomStatus::ACTIVE;
    }

    public function isConsultation(): bool
    {
        return $this->type === RoomType::CONSULTATION;
    }

    public function isBroadcast(): bool
    {
        return $this->type === RoomType::GROOMING_DEMO
            || $this->type === RoomType::MASTERCLASS
            || $this->type === RoomType::SURGERY;
    }

    public function getDuration(): ?int
    {
        if ($this->startedAt === null || $this->endedAt === null) {
            return null;
        }

        return $this->endedAt->getTimestamp() - $this->startedAt->getTimestamp();
    }
}
