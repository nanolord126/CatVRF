<?php

declare(strict_types=1);

namespace Modules\Fitness\Domain\Corporate\Entities;

use Carbon\CarbonImmutable;
use Modules\Fitness\Domain\Corporate\Enums\CorporateEnrollmentStatus;

final readonly class CorporateEnrollment
{
    public function __construct(
        public int $id,
        public int $tenantId,
        public int $corporateClientId,
        public int $corporatePackageId,
        public int $numberOfEmployees,
        public float $totalAmount,
        public CorporateEnrollmentStatus $status,
        public CarbonImmutable $startDate,
        public CarbonImmutable $endDate,
        public ?string $invoiceNumber,
        public ?CarbonImmutable $invoiceDate,
        public ?CarbonImmutable $paidAt,
        public ?string $notes,
        public CarbonImmutable $createdAt,
        public CarbonImmutable $updatedAt,
    ) {}

    public static function create(
        int $tenantId,
        int $corporateClientId,
        int $corporatePackageId,
        int $numberOfEmployees,
        float $totalAmount,
        CarbonImmutable $startDate,
        CarbonImmutable $endDate,
        ?string $invoiceNumber = null,
        ?string $notes = null,
    ): self {
        return new self(
            id: 0,
            tenantId: $tenantId,
            corporateClientId: $corporateClientId,
            corporatePackageId: $corporatePackageId,
            numberOfEmployees: $numberOfEmployees,
            totalAmount: $totalAmount,
            status: CorporateEnrollmentStatus::DRAFT,
            startDate: $startDate,
            endDate: $endDate,
            invoiceNumber: $invoiceNumber,
            invoiceDate: null,
            paidAt: null,
            notes: $notes,
            createdAt: CarbonImmutable::now(),
            updatedAt: CarbonImmutable::now(),
        );
    }

    public function activate(): self
    {
        return new self(
            ...get_object_vars($this),
            status: CorporateEnrollmentStatus::ACTIVE,
            updatedAt: CarbonImmutable::now(),
        );
    }

    public function suspend(): self
    {
        return new self(
            ...get_object_vars($this),
            status: CorporateEnrollmentStatus::SUSPENDED,
            updatedAt: CarbonImmutable::now(),
        );
    }

    public function complete(): self
    {
        return new self(
            ...get_object_vars($this),
            status: CorporateEnrollmentStatus::COMPLETED,
            updatedAt: CarbonImmutable::now(),
        );
    }

    public function cancel(): self
    {
        return new self(
            ...get_object_vars($this),
            status: CorporateEnrollmentStatus::CANCELLED,
            updatedAt: CarbonImmutable::now(),
        );
    }

    public function markAsPaid(?CarbonImmutable $paidAt = null): self
    {
        return new self(
            ...get_object_vars($this),
            paidAt: $paidAt ?? CarbonImmutable::now(),
            updatedAt: CarbonImmutable::now(),
        );
    }

    public function setInvoice(string $invoiceNumber, ?CarbonImmutable $invoiceDate = null): self
    {
        return new self(
            ...get_object_vars($this),
            invoiceNumber: $invoiceNumber,
            invoiceDate: $invoiceDate ?? CarbonImmutable::now(),
            updatedAt: CarbonImmutable::now(),
        );
    }

    public function isActive(): bool
    {
        return $this->status === CorporateEnrollmentStatus::ACTIVE;
    }

    public function isPaid(): bool
    {
        return $this->paidAt !== null;
    }

    public function isOngoing(): bool
    {
        $now = CarbonImmutable::now();
        return $this->isActive() 
            && $this->startDate->isPast() 
            && $this->endDate->isFuture();
    }

    public function isExpiringSoon(int $daysThreshold = 30): bool
    {
        return $this->endDate->diffInDays(CarbonImmutable::now()) <= $daysThreshold;
    }
}
