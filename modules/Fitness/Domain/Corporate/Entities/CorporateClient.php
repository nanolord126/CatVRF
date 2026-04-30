<?php

declare(strict_types=1);

namespace Modules\Fitness\Domain\Corporate\Entities;

use Carbon\CarbonImmutable;

final readonly class CorporateClient
{
    public function __construct(
        public int $id,
        public int $tenantId,
        public string $name,
        public string $inn,
        public string $legalAddress,
        public string $contactPerson,
        public string $hrEmail,
        public ?string $phoneNumber,
        public ?string $contractNumber,
        public ?CarbonImmutable $contractDate,
        public ?CarbonImmutable $contractEndDate,
        public ?string $notes,
        public CarbonImmutable $createdAt,
        public CarbonImmutable $updatedAt,
    ) {}

    public static function create(
        int $tenantId,
        string $name,
        string $inn,
        string $legalAddress,
        string $contactPerson,
        string $hrEmail,
        ?string $phoneNumber = null,
        ?string $contractNumber = null,
        ?CarbonImmutable $contractDate = null,
        ?CarbonImmutable $contractEndDate = null,
        ?string $notes = null,
    ): self {
        return new self(
            id: 0,
            tenantId: $tenantId,
            name: $name,
            inn: $inn,
            legalAddress: $legalAddress,
            contactPerson: $contactPerson,
            hrEmail: $hrEmail,
            phoneNumber: $phoneNumber,
            contractNumber: $contractNumber,
            contractDate: $contractDate,
            contractEndDate: $contractEndDate,
            notes: $notes,
            createdAt: CarbonImmutable::now(),
            updatedAt: CarbonImmutable::now(),
        );
    }

    public function hasActiveContract(): bool
    {
        if ($this->contractEndDate === null) {
            return true;
        }

        return $this->contractEndDate->isFuture();
    }

    public function isContractExpiringSoon(int $daysThreshold = 30): bool
    {
        if ($this->contractEndDate === null) {
            return false;
        }

        return $this->contractEndDate->diffInDays(CarbonImmutable::now()) <= $daysThreshold;
    }
}
