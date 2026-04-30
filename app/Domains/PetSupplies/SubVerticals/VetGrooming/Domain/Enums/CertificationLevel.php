<?php

declare(strict_types=1);

namespace Modules\VetGrooming\Domain\Enums;

enum CertificationLevel: string
{
    case CERTIFIED = 'certified';
    case ADVANCED = 'advanced';
    case MASTER = 'master';

    public function getLabel(): string
    {
        return match ($this) {
            self::CERTIFIED => 'Certified Exotic Groomer',
            self::ADVANCED => 'Advanced Exotic Groomer',
            self::MASTER => 'Master Exotic Groomer',
        };
    }

    public function getHierarchy(): int
    {
        return match ($this) {
            self::CERTIFIED => 1,
            self::ADVANCED => 2,
            self::MASTER => 3,
        };
    }

    public function canWorkWithGroup(string $group): bool
    {
        $requiredLevel = match ($group) {
            'group_a' => self::MASTER,
            'group_b' => self::ADVANCED,
            'group_c' => self::CERTIFIED,
            default => self::MASTER,
        };
        return $this->getHierarchy() >= $requiredLevel->getHierarchy();
    }

    public function canSupervise(): bool
    {
        return $this === self::MASTER;
    }

    public function canWorkAloneWithGroup(string $group): bool
    {
        return match ($group) {
            'group_a' => $this === self::MASTER,
            'group_b' => $this->getHierarchy() >= self::ADVANCED->getHierarchy(),
            'group_c' => $this->getHierarchy() >= self::CERTIFIED->getHierarchy(),
            default => false,
        };
    }

    public function canTrainOthers(): bool
    {
        return $this === self::MASTER;
    }
}
