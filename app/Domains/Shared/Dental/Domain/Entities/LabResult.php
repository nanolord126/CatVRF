<?php

declare(strict_types=1);

namespace Modules\Dental\Domain\Entities;

use Modules\Dental\Domain\ValueObjects\LabResultId;
use Modules\Dental\Domain\ValueObjects\LabTestId;

final readonly class LabResult
{
    public function __construct(
        public LabResultId $id,
        public LabTestId $labTestId,
        public string $parameterName,
        public string $parameterValue,
        public ?string $unit,
        public ?string $referenceRange,
        public bool $isAbnormal,
        public ?string $notes,
        public ?array $metadata,
        public \DateTimeImmutable $createdAt,
        public \DateTimeImmutable $updatedAt,
    ) {}

    public static function create(
        LabTestId $labTestId,
        string $parameterName,
        string $parameterValue,
        ?string $unit = null,
        ?string $referenceRange = null,
        bool $isAbnormal = false,
        ?string $notes = null,
    ): self {
        return new self(
            id: LabResultId::generate(),
            labTestId: $labTestId,
            parameterName: $parameterName,
            parameterValue: $parameterValue,
            unit: $unit,
            referenceRange: $referenceRange,
            isAbnormal: $isAbnormal,
            notes: $notes,
            metadata: null,
            createdAt: new \DateTimeImmutable(),
            updatedAt: new \DateTimeImmutable(),
        );
    }
}
