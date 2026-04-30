<?php

declare(strict_types=1);

namespace Modules\Dental\Domain\Entities;

use Modules\Dental\Domain\Enums\ToothStatus as ToothStatusEnum;
use Modules\Dental\Domain\ValueObjects\ToothStatusId;
use Modules\Dental\Domain\ValueObjects\ToothChartId;
use Modules\Dental\Domain\ValueObjects\DoctorId;

final readonly class ToothStatus
{
    public function __construct(
        public ToothStatusId $id,
        public ToothChartId $toothChartId,
        public string $toothNumber,
        public ToothStatusEnum $status,
        public ?string $color,
        public ?string $description,
        public ?string $notes,
        public ?DoctorId $performedBy,
        public ?\DateTimeImmutable $performedAt,
        public ?array $metadata,
        public \DateTimeImmutable $createdAt,
        public \DateTimeImmutable $updatedAt,
    ) {}

    public static function create(
        ToothChartId $toothChartId,
        string $toothNumber,
        ToothStatusEnum $status,
        ?string $description = null,
        ?string $notes = null,
        ?DoctorId $performedBy = null,
    ): self {
        return new self(
            id: ToothStatusId::generate(),
            toothChartId: $toothChartId,
            toothNumber: $toothNumber,
            status: $status,
            color: $status->getColor(),
            description: $description,
            notes: $notes,
            performedBy: $performedBy,
            performedAt: new \DateTimeImmutable(),
            metadata: null,
            createdAt: new \DateTimeImmutable(),
            updatedAt: new \DateTimeImmutable(),
        );
    }

    public function updateStatus(
        ToothStatusEnum $status,
        ?string $description = null,
        ?string $notes = null,
        ?DoctorId $performedBy = null,
    ): self {
        return new self(
            id: $this->id,
            toothChartId: $this->toothChartId,
            toothNumber: $this->toothNumber,
            status: $status,
            color: $status->getColor(),
            description: $description ?? $this->description,
            notes: $notes ?? $this->notes,
            performedBy: $performedBy ?? $this->performedBy,
            performedAt: new \DateTimeImmutable(),
            metadata: $this->metadata,
            createdAt: $this->createdAt,
            updatedAt: new \DateTimeImmutable(),
        );
    }
}
