<?php

declare(strict_types=1);

namespace Modules\CatCRM\Domain\Staff;

use Carbon\CarbonImmutable;
use Modules\CatCRM\Domain\Staff\ValueObjects\MentorshipId;
use Modules\CatCRM\Domain\Staff\ValueObjects\MentorshipStatus;

/**
 * Mentorship — Наставничество
 * 
 * Readonly DDD entity для представления наставничества
 */
final readonly class Mentorship
{
    public function __construct(
        public MentorshipId $id,
        public int $tenantId,
        public int $mentorId,
        public int $menteeId,
        public string $goals,
        public ?string $feedback,
        public MentorshipStatus $status,
        public CarbonImmutable $startDate,
        public ?CarbonImmutable $endDate,
        public array $metadata,
        public CarbonImmutable $createdAt,
        public CarbonImmutable $updatedAt,
    ) {}

    public function isActive(): bool
    {
        return $this->status === MentorshipStatus::Active;
    }

    public function getDurationDays(): int
    {
        $endDate = $this->endDate ?? CarbonImmutable::now();
        return $this->startDate->diffInDays($endDate);
    }
}
