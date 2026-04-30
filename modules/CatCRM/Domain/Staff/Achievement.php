<?php

declare(strict_types=1);

namespace Modules\CatCRM\Domain\Staff;

use Carbon\CarbonImmutable;
use Modules\CatCRM\Domain\Staff\ValueObjects\AchievementId;

/**
 * Achievement — Достижение сотрудника
 * 
 * Readonly DDD entity для представления достижения
 */
final readonly class Achievement
{
    public function __construct(
        public AchievementId $id,
        public int $tenantId,
        public int $employeeId,
        public int $badgeId,
        public string $badgeName,
        public string $badgeIcon,
        public int $pointsAwarded,
        public string $achievedAt,
        public array $metadata,
        public CarbonImmutable $createdAt,
    ) {}
}
