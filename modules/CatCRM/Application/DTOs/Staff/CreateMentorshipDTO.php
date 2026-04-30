<?php

declare(strict_types=1);

namespace Modules\CatCRM\Application\DTOs\Staff;

use Carbon\CarbonImmutable;

/**
 * CreateMentorshipDTO — DTO для создания наставничества
 * 
 * Immutable DTO following CatVRF rules
 */
final readonly class CreateMentorshipDTO
{
    public function __construct(
        public int $tenantId,
        public int $mentorId,
        public int $menteeId,
        public string $goals,
        public ?CarbonImmutable $startDate = null,
        public array $metadata = [],
    ) {}

    public static function fromArray(array $data): self
    {
        return new self(
            tenantId: $data['tenant_id'],
            mentorId: $data['mentor_id'],
            menteeId: $data['mentee_id'],
            goals: $data['goals'],
            startDate: isset($data['start_date']) ? CarbonImmutable::parse($data['start_date']) : null,
            metadata: $data['metadata'] ?? [],
        );
    }

    public function toArray(): array
    {
        return [
            'tenant_id' => $this->tenantId,
            'mentor_id' => $this->mentorId,
            'mentee_id' => $this->menteeId,
            'goals' => $this->goals,
            'start_date' => $this->startDate?->toDateTimeString(),
            'metadata' => $this->metadata,
        ];
    }
}
