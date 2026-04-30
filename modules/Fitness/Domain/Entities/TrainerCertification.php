<?php

declare(strict_types=1);

namespace Modules\Fitness\Domain\Entities;

use Carbon\CarbonImmutable;

final readonly class TrainerCertification
{
    public function __construct(
        public int $id,
        public int $tenantId,
        public ?int $businessGroupId,
        public int $trainerId,
        public string $uuid,
        public ?string $correlationId,
        public string $certificationType, // internal | external
        public string $name,
        public ?string $issuer,
        public CarbonImmutable $issueDate,
        public ?CarbonImmutable $expiryDate,
        public ?string $certificateNumber,
        public ?string $documentFile,
        public string $status, // active, expired, pending_verification, revoked
        public bool $isVerified,
        public ?CarbonImmutable $verifiedAt,
        public ?int $verifiedBy,
        public ?string $notes,
        public ?array $tags,
        public ?array $metadata,
        public CarbonImmutable $createdAt,
        public CarbonImmutable $updatedAt,
    ) {}

    public static function create(
        int $tenantId,
        int $trainerId,
        string $name,
        string $certificationType,
        CarbonImmutable $issueDate,
        ?string $issuer = null,
        ?int $businessGroupId = null,
    ): self {
        return new self(
            id: 0,
            tenantId: $tenantId,
            businessGroupId: $businessGroupId,
            trainerId: $trainerId,
            uuid: '',
            correlationId: null,
            certificationType: $certificationType,
            name: $name,
            issuer: $issuer,
            issueDate: $issueDate,
            expiryDate: null,
            certificateNumber: null,
            documentFile: null,
            status: 'pending_verification',
            isVerified: false,
            verifiedAt: null,
            verifiedBy: null,
            notes: null,
            tags: null,
            metadata: null,
            createdAt: CarbonImmutable::now(),
            updatedAt: CarbonImmutable::now(),
        );
    }

    public function verify(int $verifiedBy): self
    {
        return new self(
            ...get_object_vars($this),
            status: 'active',
            isVerified: true,
            verifiedAt: CarbonImmutable::now(),
            verifiedBy: $verifiedBy,
            updatedAt: CarbonImmutable::now(),
        );
    }

    public function markAsExpired(): self
    {
        return new self(
            ...get_object_vars($this),
            status: 'expired',
            updatedAt: CarbonImmutable::now(),
        );
    }

    public function revoke(): self
    {
        return new self(
            ...get_object_vars($this),
            status: 'revoked',
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

    public function isExpiringSoon(int $days = 30): bool
    {
        if ($this->expiryDate === null) {
            return false;
        }

        return $this->expiryDate->lessThanOrEqualTo(CarbonImmutable::now()->addDays($days))
            && $this->expiryDate->isFuture();
    }

    public function isActive(): bool
    {
        return $this->status === 'active' && !$this->isExpired();
    }
}
