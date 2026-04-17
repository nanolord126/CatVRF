<?php

declare(strict_types=1);

namespace App\Domains\Sports\DTOs;

final readonly class LiveStreamSessionDto
{
    public function __construct(
        public int $userId,
        public int $tenantId,
        public ?int $businessGroupId,
        public int $trainerId,
        public string $sessionTitle,
        public string $sessionDescription,
        public string $scheduledStart,
        public string $scheduledEnd,
        public string $streamType,
        public int $maxParticipants,
        public array $tags,
        public string $correlationId,
        public ?string $idempotencyKey = null,
    ) {}

    public static function from(array $data): self
    {
        return new self(
            userId: $data['user_id'],
            tenantId: $data['tenant_id'],
            businessGroupId: $data['business_group_id'] ?? null,
            trainerId: $data['trainer_id'],
            sessionTitle: $data['session_title'],
            sessionDescription: $data['session_description'],
            scheduledStart: $data['scheduled_start'],
            scheduledEnd: $data['scheduled_end'],
            streamType: $data['stream_type'],
            maxParticipants: $data['max_participants'] ?? 50,
            tags: $data['tags'] ?? [],
            correlationId: $data['correlation_id'],
            idempotencyKey: $data['idempotency_key'] ?? null,
        );
    }

    public function toArray(): array
    {
        return [
            'user_id' => $this->userId,
            'tenant_id' => $this->tenantId,
            'business_group_id' => $this->businessGroupId,
            'trainer_id' => $this->trainerId,
            'session_title' => $this->sessionTitle,
            'session_description' => $this->sessionDescription,
            'scheduled_start' => $this->scheduledStart,
            'scheduled_end' => $this->scheduledEnd,
            'stream_type' => $this->streamType,
            'max_participants' => $this->maxParticipants,
            'tags' => $this->tags,
            'correlation_id' => $this->correlationId,
            'idempotency_key' => $this->idempotencyKey,
        ];
    }

    public static function fromJson(string $json): self
    {
        return self::from(json_decode($json, true, 512, JSON_THROW_ON_ERROR));
    }

    public function toJson(): string
    {
        return json_encode($this->toArray(), JSON_THROW_ON_ERROR);
    }
}
