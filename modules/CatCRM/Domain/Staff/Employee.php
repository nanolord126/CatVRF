<?php

declare(strict_types=1);

namespace Modules\CatCRM\Domain\Staff;

use Carbon\CarbonImmutable;
use Modules\CatCRM\Domain\Staff\ValueObjects\EmployeeId;
use Modules\CatCRM\Domain\Staff\ValueObjects\EmployeeStatus;
use Modules\CatCRM\Domain\Staff\ValueObjects\EmployeeRole;

/**
 * Employee — Сущность сотрудника в HRM системе
 * 
 * Readonly DDD entity для представления сотрудника
 * Следует принципам CatVRF: immutable value objects, strict typing
 */
final readonly class Employee
{
    public function __construct(
        public EmployeeId $id,
        public int $tenantId,
        public int $userId, // Связь с User модели
        public string $firstName,
        public string $lastName,
        public ?string $middleName,
        public string $email,
        public string $phone,
        public ?string $position,
        public ?string $department,
        public EmployeeRole $role,
        public EmployeeStatus $status,
        public ?int $managerId, // ID руководителя
        public CarbonImmutable $hireDate,
        public ?CarbonImmutable $terminationDate,
        public ?string $avatar,
        public array $skills, // Список ID навыков
        public int $level, // Уровень в системе геймификации
        public int $experiencePoints, // Очки опыта
        public float $performanceScore, // Оценка производительности 0-100
        public float $burnoutRisk, // Риск выгорания 0-100
        public ?string $slackId,
        public ?string $teamsId,
        public array $metadata,
        public CarbonImmutable $createdAt,
        public CarbonImmutable $updatedAt,
    ) {}

    /**
     * Проверить, активен ли сотрудник
     */
    public function isActive(): bool
    {
        return $this->status === EmployeeStatus::Active;
    }

    /**
     * Получить полное имя
     */
    public function getFullName(): string
    {
        return trim("{$this->lastName} {$this->firstName} {$this->middleName}");
    }

    /**
     * Проверить, имеет ли сотрудник указанный навык
     */
    public function hasSkill(int $skillId): bool
    {
        return in_array($skillId, $this->skills, true);
    }

    /**
     * Получить стаж работы в днях
     */
    public function getTenureDays(): int
    {
        $endDate = $this->terminationDate ?? CarbonImmutable::now();
        return $this->hireDate->diffInDays($endDate);
    }

    /**
     * Проверить высокий риск выгорания
     */
    public function hasHighBurnoutRisk(): bool
    {
        return $this->burnoutRisk >= 70.0;
    }

    /**
     * Получить уровень в геймификации
     */
    public function getGamificationLevel(): int
    {
        return $this->level;
    }

    /**
     * Получить прогресс до следующего уровня
     */
    public function getProgressToNextLevel(): int
    {
        $nextLevelXP = $this->level * 1000;
        $currentLevelXP = ($this->level - 1) * 1000;
        $progressInLevel = $this->experiencePoints - $currentLevelXP;
        $totalNeeded = $nextLevelXP - $currentLevelXP;
        
        return $totalNeeded > 0 ? (int) round(($progressInLevel / $totalNeeded) * 100) : 0;
    }
}
