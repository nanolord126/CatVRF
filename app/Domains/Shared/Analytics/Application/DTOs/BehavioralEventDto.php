<?php

declare(strict_types=1);

namespace Modules\Analytics\Application\DTOs;

final readonly class BehavioralEventDto
{
    public function __construct(
        public int $userId,
        public ?int $tenantId,
        public string $eventType,
        public ?string $entityType,
        public ?int $entityId,
        public array $eventData,
    ) {
    }

    public static function fromArray(array $data): self
    {
        return new self(
            userId: (int) ($data['user_id'] ?? 0),
            tenantId: isset($data['tenant_id']) ? (int) $data['tenant_id'] : null,
            eventType: (string) ($data['event_type'] ?? ''),
            entityType: $data['entity_type'] ?? null,
            entityId: isset($data['entity_id']) ? (int) $data['entity_id'] : null,
            eventData: (array) ($data['event_data'] ?? []),
        );
    }

    public function toArray(): array
    {
        return [
            'user_id' => $this->userId,
            'tenant_id' => $this->tenantId,
            'event_type' => $this->eventType,
            'entity_type' => $this->entityType,
            'entity_id' => $this->entityId,
            'event_data' => $this->eventData,
        ];
    }
}
