<?php

declare(strict_types=1);

namespace Modules\VetGrooming\Domain\Entities;

use Carbon\CarbonImmutable;

final readonly class ExoticTrainingCompletion
{
    public function __construct(
        public int $id,
        public int $masterId,
        public int $courseId,
        public int $tenantId,
        public float $score,
        public bool $passed,
        public ?string $certificateFile,
        public ?string $practicalExamVideo,
        public ?array $examResults,
        public ?string $instructorNotes,
        public CarbonImmutable $completedAt,
        public ?CarbonImmutable $expiresAt,
        public CarbonImmutable $createdAt,
        public CarbonImmutable $updatedAt,
    ) {}

    public static function create(
        int $masterId,
        int $courseId,
        int $tenantId,
        float $score,
        bool $passed,
        ?string $certificateFile = null,
        ?string $practicalExamVideo = null,
        ?array $examResults = null,
        ?string $instructorNotes = null,
        ?CarbonImmutable $expiresAt = null,
    ): self {
        return new self(
            id: 0,
            masterId: $masterId,
            courseId: $courseId,
            tenantId: $tenantId,
            score: $score,
            passed: $passed,
            certificateFile: $certificateFile,
            practicalExamVideo: $practicalExamVideo,
            examResults: $examResults,
            instructorNotes: $instructorNotes,
            completedAt: CarbonImmutable::now(),
            expiresAt: $expiresAt ?? CarbonImmutable::now()->addYear(),
            createdAt: CarbonImmutable::now(),
            updatedAt: CarbonImmutable::now(),
        );
    }

    public function isPassed(): bool
    {
        return $this->passed;
    }

    public function isExpired(): bool
    {
        return $this->expiresAt !== null && $this->expiresAt->isPast();
    }

    public function requiresRenewal(): bool
    {
        return $this->expiresAt !== null && $this->expiresAt->diffInDays(CarbonImmutable::now()) <= 30;
    }

    public function hasPracticalExam(): bool
    {
        return $this->practicalExamVideo !== null;
    }

    public function hasCertificate(): bool
    {
        return $this->certificateFile !== null;
    }
}
