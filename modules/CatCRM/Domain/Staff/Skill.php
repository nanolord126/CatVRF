<?php

declare(strict_types=1);

namespace Modules\CatCRM\Domain\Staff;

use Carbon\CarbonImmutable;
use Modules\CatCRM\Domain\Staff\ValueObjects\SkillId;
use Modules\CatCRM\Domain\Staff\ValueObjects\SkillLevel;

/**
 * Skill — Навык сотрудника
 * 
 * Readonly DDD entity для представления навыка
 */
final readonly class Skill
{
    public function __construct(
        public SkillId $id,
        public int $tenantId,
        public string $name,
        public string $category, // technical, soft, domain, language
        public string $description,
        public SkillLevel $level,
        public int $proficiency, // 0-100
        public ?CarbonImmutable $lastAssessed,
        public array $metadata,
        public CarbonImmutable $createdAt,
        public CarbonImmutable $updatedAt,
    ) {}

    public function isExpert(): bool
    {
        return $this->proficiency >= 90;
    }

    public function isAdvanced(): bool
    {
        return $this->proficiency >= 70 && $this->proficiency < 90;
    }

    public function isIntermediate(): bool
    {
        return $this->proficiency >= 40 && $this->proficiency < 70;
    }

    public function isBeginner(): bool
    {
        return $this->proficiency < 40;
    }
}
