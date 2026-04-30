<?php

declare(strict_types=1);

namespace Modules\Fitness\Domain\Entities;

use Carbon\CarbonImmutable;

final readonly class TrainerSpecialization
{
    public function __construct(
        public int $id,
        public int $tenantId,
        public ?int $businessGroupId,
        public int $trainerId,
        public string $uuid,
        public ?string $correlationId,
        public string $specialization, // Prenatal, Kids, Senior, etc.
        public string $level, // basic, advanced, master
        public ?int $certificationId,
        public bool $isActive,
        public CarbonImmutable $assignedDate,
        public ?CarbonImmutable $expiryDate,
        public ?string $notes,
        public ?array $tags,
        public ?array $metadata,
        public CarbonImmutable $createdAt,
        public CarbonImmutable $updatedAt,
    ) {}

    public static function create(
        int $tenantId,
        int $trainerId,
        string $specialization,
        string $level = 'basic',
        ?int $certificationId = null,
        ?int $businessGroupId = null,
    ): self {
        return new self(
            id: 0,
            tenantId: $tenantId,
            businessGroupId: $businessGroupId,
            trainerId: $trainerId,
            uuid: '',
            correlationId: null,
            specialization: $specialization,
            level: $level,
            certificationId: $certificationId,
            isActive: true,
            assignedDate: CarbonImmutable::now(),
            expiryDate: null,
            notes: null,
            tags: null,
            metadata: null,
            createdAt: CarbonImmutable::now(),
            updatedAt: CarbonImmutable::now(),
        );
    }

    public function deactivate(): self
    {
        return new self(
            ...get_object_vars($this),
            isActive: false,
            updatedAt: CarbonImmutable::now(),
        );
    }

    public function isExpired(): bool
    {
        if ($this->expiryDate === null) {
            return false;
        }

        return $this->expiryDate->isPast();
    }

    public function isAdvanced(): bool
    {
        return $this->level === 'advanced' || $this->level === 'master';
    }

    public function isMaster(): bool
    {
        return $this->level === 'master';
    }
}
