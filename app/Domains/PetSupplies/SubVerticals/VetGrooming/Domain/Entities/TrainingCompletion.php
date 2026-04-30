<?php

declare(strict_types=1);

namespace Modules\VetGrooming\Domain\Entities;

use Carbon\CarbonImmutable;

final readonly class TrainingCompletion
{
    public function __construct(
        public int $id,
        public string $uuid,
        public int $tenantId,
        public int $masterId,
        public int $courseId,
        public ?int $developmentPlanId,
        public CarbonImmutable $completionDate,
        public ?int $durationActualHours,
        public ?float $score,
        public ?string $grade,
        public ?string $certificateFile,
        public ?CarbonImmutable $certificateExpiryDate,
        public string $verificationStatus,
        public ?int $verifiedBy,
        public ?CarbonImmutable $verifiedAt,
        public ?array $evidenceFiles,
        public ?string $feedback,
        public ?string $externalCertificationId,
        public ?string $externalPlatform,
        public ?string $correlationId,
        public ?array $tags,
        public CarbonImmutable $createdAt,
        public CarbonImmutable $updatedAt,
        public ?CarbonImmutable $deletedAt,
    ) {}

    public static function create(
        int $tenantId,
        int $masterId,
        int $courseId,
        ?int $developmentPlanId = null,
        ?int $durationActualHours = null,
        ?float $score = null,
        ?string $certificateFile = null,
        ?CarbonImmutable $certificateExpiryDate = null,
        ?string $correlationId = null,
    ): self {
        $grade = null;
        if ($score !== null) {
            $grade = match (true) {
                $score >= 90 => 'excellent',
                $score >= 75 => 'good',
                $score >= 60 => 'pass',
                default => 'fail',
            };
        }

        return new self(
            id: 0,
            uuid: (string) str()->uuid(),
            tenantId: $tenantId,
            masterId: $masterId,
            courseId: $courseId,
            developmentPlanId: $developmentPlanId,
            completionDate: CarbonImmutable::now(),
            durationActualHours: $durationActualHours,
            score: $score,
            grade: $grade,
            certificateFile: $certificateFile,
            certificateExpiryDate: $certificateExpiryDate,
            verificationStatus: 'pending',
            verifiedBy: null,
            verifiedAt: null,
            evidenceFiles: null,
            feedback: null,
            externalCertificationId: null,
            externalPlatform: null,
            correlationId: $correlationId,
            tags: null,
            createdAt: CarbonImmutable::now(),
            updatedAt: CarbonImmutable::now(),
            deletedAt: null,
        );
    }

    public function verify(int $verifiedBy): self
    {
        return new self(
            id: $this->id,
            uuid: $this->uuid,
            tenantId: $this->tenantId,
            masterId: $this->masterId,
            courseId: $this->courseId,
            developmentPlanId: $this->developmentPlanId,
            completionDate: $this->completionDate,
            durationActualHours: $this->durationActualHours,
            score: $this->score,
            grade: $this->grade,
            certificateFile: $this->certificateFile,
            certificateExpiryDate: $this->certificateExpiryDate,
            verificationStatus: 'verified',
            verifiedBy: $verifiedBy,
            verifiedAt: CarbonImmutable::now(),
            evidenceFiles: $this->evidenceFiles,
            feedback: $this->feedback,
            externalCertificationId: $this->externalCertificationId,
            externalPlatform: $this->externalPlatform,
            correlationId: $this->correlationId,
            tags: $this->tags,
            createdAt: $this->createdAt,
            updatedAt: CarbonImmutable::now(),
            deletedAt: $this->deletedAt,
        );
    }

    public function reject(): self
    {
        return new self(
            id: $this->id,
            uuid: $this->uuid,
            tenantId: $this->tenantId,
            masterId: $this->masterId,
            courseId: $this->courseId,
            developmentPlanId: $this->developmentPlanId,
            completionDate: $this->completionDate,
            durationActualHours: $this->durationActualHours,
            score: $this->score,
            grade: $this->grade,
            certificateFile: $this->certificateFile,
            certificateExpiryDate: $this->certificateExpiryDate,
            verificationStatus: 'rejected',
            verifiedBy: $this->verifiedBy,
            verifiedAt: CarbonImmutable::now(),
            evidenceFiles: $this->evidenceFiles,
            feedback: $this->feedback,
            externalCertificationId: $this->externalCertificationId,
            externalPlatform: $this->externalPlatform,
            correlationId: $this->correlationId,
            tags: $this->tags,
            createdAt: $this->createdAt,
            updatedAt: CarbonImmutable::now(),
            deletedAt: $this->deletedAt,
        );
    }

    public function addEvidence(array $evidenceFiles): self
    {
        return new self(
            id: $this->id,
            uuid: $this->uuid,
            tenantId: $this->tenantId,
            masterId: $this->masterId,
            courseId: $this->courseId,
            developmentPlanId: $this->developmentPlanId,
            completionDate: $this->completionDate,
            durationActualHours: $this->durationActualHours,
            score: $this->score,
            grade: $this->grade,
            certificateFile: $this->certificateFile,
            certificateExpiryDate: $this->certificateExpiryDate,
            verificationStatus: $this->verificationStatus,
            verifiedBy: $this->verifiedBy,
            verifiedAt: $this->verifiedAt,
            evidenceFiles: $evidenceFiles,
            feedback: $this->feedback,
            externalCertificationId: $this->externalCertificationId,
            externalPlatform: $this->externalPlatform,
            correlationId: $this->correlationId,
            tags: $this->tags,
            createdAt: $this->createdAt,
            updatedAt: CarbonImmutable::now(),
            deletedAt: $this->deletedAt,
        );
    }

    public function addFeedback(string $feedback): self
    {
        return new self(
            id: $this->id,
            uuid: $this->uuid,
            tenantId: $this->tenantId,
            masterId: $this->masterId,
            courseId: $this->courseId,
            developmentPlanId: $this->developmentPlanId,
            completionDate: $this->completionDate,
            durationActualHours: $this->durationActualHours,
            score: $this->score,
            grade: $this->grade,
            certificateFile: $this->certificateFile,
            certificateExpiryDate: $this->certificateExpiryDate,
            verificationStatus: $this->verificationStatus,
            verifiedBy: $this->verifiedBy,
            verifiedAt: $this->verifiedAt,
            evidenceFiles: $this->evidenceFiles,
            feedback: $feedback,
            externalCertificationId: $this->externalCertificationId,
            externalPlatform: $this->externalPlatform,
            correlationId: $this->correlationId,
            tags: $this->tags,
            createdAt: $this->createdAt,
            updatedAt: CarbonImmutable::now(),
            deletedAt: $this->deletedAt,
        );
    }

    public function linkExternalCertification(string $certificationId, string $platform): self
    {
        return new self(
            id: $this->id,
            uuid: $this->uuid,
            tenantId: $this->tenantId,
            masterId: $this->masterId,
            courseId: $this->courseId,
            developmentPlanId: $this->developmentPlanId,
            completionDate: $this->completionDate,
            durationActualHours: $this->durationActualHours,
            score: $this->score,
            grade: $this->grade,
            certificateFile: $this->certificateFile,
            certificateExpiryDate: $this->certificateExpiryDate,
            verificationStatus: $this->verificationStatus,
            verifiedBy: $this->verifiedBy,
            verifiedAt: $this->verifiedAt,
            evidenceFiles: $this->evidenceFiles,
            feedback: $this->feedback,
            externalCertificationId: $certificationId,
            externalPlatform: $platform,
            correlationId: $this->correlationId,
            tags: $this->tags,
            createdAt: $this->createdAt,
            updatedAt: CarbonImmutable::now(),
            deletedAt: $this->deletedAt,
        );
    }

    public function isVerified(): bool
    {
        return $this->verificationStatus === 'verified';
    }

    public function isPending(): bool
    {
        return $this->verificationStatus === 'pending';
    }

    public function isRejected(): bool
    {
        return $this->verificationStatus === 'rejected';
    }

    public function isCertificateExpired(): bool
    {
        return $this->certificateExpiryDate !== null && $this->certificateExpiryDate->isPast();
    }

    public function isCertificateExpiringWithin(int $days): bool
    {
        if ($this->certificateExpiryDate === null) {
            return false;
        }

        return $this->certificateExpiryDate->diffInDays(CarbonImmutable::now()) <= $days;
    }

    public function hasPassed(): bool
    {
        return $this->grade !== null && $this->grade !== 'fail';
    }
}
