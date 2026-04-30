<?php

declare(strict_types=1);

namespace Modules\VetGrooming\Domain\Entities;

use Carbon\CarbonImmutable;

final readonly class BreedCertification
{
    public function __construct(
        public int $id,
        public string $uuid,
        public int $tenantId,
        public int $masterId,
        public string $certificationType,
        public ?string $breedGroup,
        public ?string $breedId,
        public string $certificationLevel,
        public string $issuer,
        public ?string $externalSchoolName,
        public CarbonImmutable $issueDate,
        public ?CarbonImmutable $expiryDate,
        public ?float $practicalExamScore,
        public ?float $theoryExamScore,
        public ?string $examFeedback,
        public string $status,
        public ?string $certificateFile,
        public ?string $certificateNumber,
        public ?int $verifiedBy,
        public ?CarbonImmutable $verifiedAt,
        public ?string $notes,
        public ?array $specialSkills,
        public ?string $correlationId,
        public ?array $tags,
        public CarbonImmutable $createdAt,
        public CarbonImmutable $updatedAt,
        public ?CarbonImmutable $deletedAt,
    ) {}

    public static function create(
        int $tenantId,
        int $masterId,
        string $certificationType,
        string $certificationLevel,
        ?string $breedGroup = null,
        ?string $breedId = null,
        string $issuer = 'catcrm_internal',
        ?string $externalSchoolName = null,
        ?string $correlationId = null,
    ): self {
        return new self(
            id: 0,
            uuid: (string) str()->uuid(),
            tenantId: $tenantId,
            masterId: $masterId,
            certificationType: $certificationType,
            breedGroup: $breedGroup,
            breedId: $breedId,
            certificationLevel: $certificationLevel,
            issuer: $issuer,
            externalSchoolName: $externalSchoolName,
            issueDate: CarbonImmutable::now(),
            expiryDate: null,
            practicalExamScore: null,
            theoryExamScore: null,
            examFeedback: null,
            status: 'active',
            certificateFile: null,
            certificateNumber: null,
            verifiedBy: null,
            verifiedAt: null,
            notes: null,
            specialSkills: null,
            correlationId: $correlationId,
            tags: null,
            createdAt: CarbonImmutable::now(),
            updatedAt: CarbonImmutable::now(),
            deletedAt: null,
        );
    }

    public function verify(int $verifiedBy, ?string $certificateNumber = null): self
    {
        return new self(
            id: $this->id,
            uuid: $this->uuid,
            tenantId: $this->tenantId,
            masterId: $this->masterId,
            certificationType: $this->certificationType,
            breedGroup: $this->breedGroup,
            breedId: $this->breedId,
            certificationLevel: $this->certificationLevel,
            issuer: $this->issuer,
            externalSchoolName: $this->externalSchoolName,
            issueDate: $this->issueDate,
            expiryDate: $this->expiryDate,
            practicalExamScore: $this->practicalExamScore,
            theoryExamScore: $this->theoryExamScore,
            examFeedback: $this->examFeedback,
            status: $this->status,
            certificateFile: $this->certificateFile,
            certificateNumber: $certificateNumber ?? $this->certificateNumber,
            verifiedBy: $verifiedBy,
            verifiedAt: CarbonImmutable::now(),
            notes: $this->notes,
            specialSkills: $this->specialSkills,
            correlationId: $this->correlationId,
            tags: $this->tags,
            createdAt: $this->createdAt,
            updatedAt: CarbonImmutable::now(),
            deletedAt: $this->deletedAt,
        );
    }

    public function expire(): self
    {
        return new self(
            id: $this->id,
            uuid: $this->uuid,
            tenantId: $this->tenantId,
            masterId: $this->masterId,
            certificationType: $this->certificationType,
            breedGroup: $this->breedGroup,
            breedId: $this->breedId,
            certificationLevel: $this->certificationLevel,
            issuer: $this->issuer,
            externalSchoolName: $this->externalSchoolName,
            issueDate: $this->issueDate,
            expiryDate: $this->expiryDate,
            practicalExamScore: $this->practicalExamScore,
            theoryExamScore: $this->theoryExamScore,
            examFeedback: $this->examFeedback,
            status: 'expired',
            certificateFile: $this->certificateFile,
            certificateNumber: $this->certificateNumber,
            verifiedBy: $this->verifiedBy,
            verifiedAt: $this->verifiedAt,
            notes: $this->notes,
            specialSkills: $this->specialSkills,
            correlationId: $this->correlationId,
            tags: $this->tags,
            createdAt: $this->createdAt,
            updatedAt: CarbonImmutable::now(),
            deletedAt: $this->deletedAt,
        );
    }

    public function revoke(): self
    {
        return new self(
            id: $this->id,
            uuid: $this->uuid,
            tenantId: $this->tenantId,
            masterId: $this->masterId,
            certificationType: $this->certificationType,
            breedGroup: $this->breedGroup,
            breedId: $this->breedId,
            certificationLevel: $this->certificationLevel,
            issuer: $this->issuer,
            externalSchoolName: $this->externalSchoolName,
            issueDate: $this->issueDate,
            expiryDate: $this->expiryDate,
            practicalExamScore: $this->practicalExamScore,
            theoryExamScore: $this->theoryExamScore,
            examFeedback: $this->examFeedback,
            status: 'revoked',
            certificateFile: $this->certificateFile,
            certificateNumber: $this->certificateNumber,
            verifiedBy: $this->verifiedBy,
            verifiedAt: $this->verifiedAt,
            notes: $this->notes,
            specialSkills: $this->specialSkills,
            correlationId: $this->correlationId,
            tags: $this->tags,
            createdAt: $this->createdAt,
            updatedAt: CarbonImmutable::now(),
            deletedAt: $this->deletedAt,
        );
    }

    public function addExamScores(?float $practicalScore, ?float $theoryScore, ?string $feedback = null): self
    {
        return new self(
            id: $this->id,
            uuid: $this->uuid,
            tenantId: $this->tenantId,
            masterId: $this->masterId,
            certificationType: $this->certificationType,
            breedGroup: $this->breedGroup,
            breedId: $this->breedId,
            certificationLevel: $this->certificationLevel,
            issuer: $this->issuer,
            externalSchoolName: $this->externalSchoolName,
            issueDate: $this->issueDate,
            expiryDate: $this->expiryDate,
            practicalExamScore: $practicalScore,
            theoryExamScore: $theoryScore,
            examFeedback: $feedback,
            status: $this->status,
            certificateFile: $this->certificateFile,
            certificateNumber: $this->certificateNumber,
            verifiedBy: $this->verifiedBy,
            verifiedAt: $this->verifiedAt,
            notes: $this->notes,
            specialSkills: $this->specialSkills,
            correlationId: $this->correlationId,
            tags: $this->tags,
            createdAt: $this->createdAt,
            updatedAt: CarbonImmutable::now(),
            deletedAt: $this->deletedAt,
        );
    }

    public function setExpiryDate(CarbonImmutable $expiryDate): self
    {
        return new self(
            id: $this->id,
            uuid: $this->uuid,
            tenantId: $this->tenantId,
            masterId: $this->masterId,
            certificationType: $this->certificationType,
            breedGroup: $this->breedGroup,
            breedId: $this->breedId,
            certificationLevel: $this->certificationLevel,
            issuer: $this->issuer,
            externalSchoolName: $this->externalSchoolName,
            issueDate: $this->issueDate,
            expiryDate: $expiryDate,
            practicalExamScore: $this->practicalExamScore,
            theoryExamScore: $this->theoryExamScore,
            examFeedback: $this->examFeedback,
            status: $this->status,
            certificateFile: $this->certificateFile,
            certificateNumber: $this->certificateNumber,
            verifiedBy: $this->verifiedBy,
            verifiedAt: $this->verifiedAt,
            notes: $this->notes,
            specialSkills: $this->specialSkills,
            correlationId: $this->correlationId,
            tags: $this->tags,
            createdAt: $this->createdAt,
            updatedAt: CarbonImmutable::now(),
            deletedAt: $this->deletedAt,
        );
    }

    public function isActive(): bool
    {
        return $this->status === 'active' && ! $this->isExpired();
    }

    public function isExpired(): bool
    {
        return $this->expiryDate !== null && $this->expiryDate->isPast();
    }

    public function isExpiringWithin(int $days): bool
    {
        if ($this->expiryDate === null) {
            return false;
        }

        return $this->expiryDate->diffInDays(CarbonImmutable::now()) <= $days;
    }

    public function isAtLeastLevel(string $level): bool
    {
        $levels = ['basic', 'certified', 'advanced', 'master'];
        $currentLevelIndex = array_search($this->certificationLevel, $levels);
        $requiredLevelIndex = array_search($level, $levels);

        return $currentLevelIndex >= $requiredLevelIndex;
    }

    public function coversBreedGroup(string $breedGroup): bool
    {
        return $this->certificationType === 'breed_group' && $this->breedGroup === $breedGroup;
    }

    public function coversBreed(string $breed): bool
    {
        return $this->certificationType === 'specific_breed' && $this->breedId === $breed;
    }

    public function isExternal(): bool
    {
        return $this->issuer === 'external_school';
    }
}
