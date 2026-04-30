<?php

declare(strict_types=1);

namespace Modules\VetGrooming\Domain\Entities;

use Modules\VetGrooming\Domain\Enums\CertificationLevel;
use Modules\VetGrooming\Domain\Enums\ExoticCategory;
use Modules\VetGrooming\Domain\Enums\ExoticGroup;
use Carbon\CarbonImmutable;

final readonly class ExoticCertification
{
    public function __construct(
        public int $id,
        public int $masterId,
        public int $tenantId,
        public ExoticCategory $exoticCategory,
        public ?string $subcategory,
        public ExoticGroup $exoticGroup,
        public CertificationLevel $certificationLevel,
        public ?string $practicalExamVideo,
        public CarbonImmutable $issueDate,
        public CarbonImmutable $expiryDate,
        public string $status,
        public ?string $certificateNumber,
        public ?array $metadata,
        public CarbonImmutable $createdAt,
        public CarbonImmutable $updatedAt,
    ) {}

    public static function create(
        int $masterId,
        int $tenantId,
        ExoticCategory $exoticCategory,
        ?string $subcategory,
        ExoticGroup $exoticGroup,
        CertificationLevel $certificationLevel,
        ?string $practicalExamVideo = null,
        ?string $certificateNumber = null,
        ?array $metadata = null,
    ): self {
        $issueDate = CarbonImmutable::now();
        $expiryDate = $issueDate->addYear(); // 1 year validity

        return new self(
            id: 0,
            masterId: $masterId,
            tenantId: $tenantId,
            exoticCategory: $exoticCategory,
            subcategory: $subcategory,
            exoticGroup: $exoticGroup,
            certificationLevel: $certificationLevel,
            practicalExamVideo: $practicalExamVideo,
            issueDate: $issueDate,
            expiryDate: $expiryDate,
            status: 'active',
            certificateNumber: $certificateNumber ?? self::generateCertificateNumber(),
            metadata: $metadata,
            createdAt: CarbonImmutable::now(),
            updatedAt: CarbonImmutable::now(),
        );
    }

    public static function generateCertificateNumber(): string
    {
        return 'EXO-' . strtoupper(bin2hex(random_bytes(8)));
    }

    public function isValid(): bool
    {
        return $this->status === 'active' && $this->expiryDate->isFuture();
    }

    public function isExpired(): bool
    {
        return $this->expiryDate->isPast();
    }

    public function isRevoked(): bool
    {
        return $this->status === 'revoked';
    }

    public function isSuspended(): bool
    {
        return $this->status === 'suspended';
    }

    public function canWorkWithCategory(ExoticCategory $category): bool
    {
        return $this->exoticCategory === $category && $this->isValid();
    }

    public function canWorkWithGroup(ExoticGroup $group): bool
    {
        return $this->exoticGroup === $group && $this->isValid();
    }

    public function requiresRenewal(): bool
    {
        return $this->expiryDate->diffInDays(CarbonImmutable::now()) <= 30;
    }

    public function revoke(): self
    {
        return new self(
            id: $this->id,
            masterId: $this->masterId,
            tenantId: $this->tenantId,
            exoticCategory: $this->exoticCategory,
            subcategory: $this->subcategory,
            exoticGroup: $this->exoticGroup,
            certificationLevel: $this->certificationLevel,
            practicalExamVideo: $this->practicalExamVideo,
            issueDate: $this->issueDate,
            expiryDate: $this->expiryDate,
            status: 'revoked',
            certificateNumber: $this->certificateNumber,
            metadata: $this->metadata,
            createdAt: $this->createdAt,
            updatedAt: CarbonImmutable::now(),
        );
    }

    public function suspend(): self
    {
        return new self(
            id: $this->id,
            masterId: $this->masterId,
            tenantId: $this->tenantId,
            exoticCategory: $this->exoticCategory,
            subcategory: $this->subcategory,
            exoticGroup: $this->exoticGroup,
            certificationLevel: $this->certificationLevel,
            practicalExamVideo: $this->practicalExamVideo,
            issueDate: $this->issueDate,
            expiryDate: $this->expiryDate,
            status: 'suspended',
            certificateNumber: $this->certificateNumber,
            metadata: $this->metadata,
            createdAt: $this->createdAt,
            updatedAt: CarbonImmutable::now(),
        );
    }

    public function renew(): self
    {
        return new self(
            id: $this->id,
            masterId: $this->masterId,
            tenantId: $this->tenantId,
            exoticCategory: $this->exoticCategory,
            subcategory: $this->subcategory,
            exoticGroup: $this->exoticGroup,
            certificationLevel: $this->certificationLevel,
            practicalExamVideo: $this->practicalExamVideo,
            issueDate: $this->issueDate,
            expiryDate: CarbonImmutable::now()->addYear(),
            status: 'active',
            certificateNumber: $this->certificateNumber,
            metadata: $this->metadata,
            createdAt: $this->createdAt,
            updatedAt: CarbonImmutable::now(),
        );
    }

    public function upgrade(CertificationLevel $newLevel): self
    {
        if ($newLevel->getHierarchy() <= $this->certificationLevel->getHierarchy()) {
            throw new \InvalidArgumentException('New level must be higher than current level');
        }

        return new self(
            id: $this->id,
            masterId: $this->masterId,
            tenantId: $this->tenantId,
            exoticCategory: $this->exoticCategory,
            subcategory: $this->subcategory,
            exoticGroup: $this->exoticGroup,
            certificationLevel: $newLevel,
            practicalExamVideo: $this->practicalExamVideo,
            issueDate: $this->issueDate,
            expiryDate: CarbonImmutable::now()->addYear(),
            status: 'active',
            certificateNumber: $this->certificateNumber,
            metadata: $this->metadata,
            createdAt: $this->createdAt,
            updatedAt: CarbonImmutable::now(),
        );
    }
}
