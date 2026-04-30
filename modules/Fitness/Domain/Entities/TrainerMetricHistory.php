<?php

declare(strict_types=1);

namespace Modules\Fitness\Domain\Entities;

use Carbon\CarbonImmutable;

final readonly class TrainerMetricHistory
{
    public function __construct(
        public int $id,
        public int $tenantId,
        public ?int $businessGroupId,
        public int $trainerId,
        public int $effectivenessId,
        public string $uuid,
        public ?string $correlationId,
        public string $metricName,
        public ?float $oldValue,
        public ?float $newValue,
        public ?float $changeDelta,
        public ?string $changeReason,
        public ?string $notes,
        public CarbonImmutable $changedAt,
        public ?array $tags,
        public ?array $metadata,
        public CarbonImmutable $createdAt,
        public CarbonImmutable $updatedAt,
    ) {}

    public static function create(
        int $tenantId,
        int $trainerId,
        int $effectivenessId,
        string $metricName,
        ?float $oldValue,
        ?float $newValue,
        ?int $businessGroupId = null,
    ): self {
        $changeDelta = $newValue !== null && $oldValue !== null
            ? $newValue - $oldValue
            : null;

        return new self(
            id: 0,
            tenantId: $tenantId,
            businessGroupId: $businessGroupId,
            trainerId: $trainerId,
            effectivenessId: $effectivenessId,
            uuid: '',
            correlationId: null,
            metricName: $metricName,
            oldValue: $oldValue,
            newValue: $newValue,
            changeDelta: $changeDelta,
            changeReason: null,
            notes: null,
            changedAt: CarbonImmutable::now(),
            tags: null,
            metadata: null,
            createdAt: CarbonImmutable::now(),
            updatedAt: CarbonImmutable::now(),
        );
    }

    public function isImprovement(): bool
    {
        return $this->changeDelta !== null && $this->changeDelta > 0;
    }

    public function isDecline(): bool
    {
        return $this->changeDelta !== null && $this->changeDelta < 0;
    }
}
