<?php

declare(strict_types=1);

namespace Modules\BeautyMasters\Domain\Entities;

enum CertificationLevel: string
{
    case JUNIOR = 'junior';
    case CERTIFIED = 'certified';
    case ADVANCED = 'advanced';
    case MASTER = 'master';
    case EXPERT = 'expert';

    public function getLabel(): string
    {
        return match ($this) {
            self::JUNIOR => 'Junior Specialist',
            self::CERTIFIED => 'Certified Master',
            self::ADVANCED => 'Senior Master',
            self::MASTER => 'Master Trainer',
            self::EXPERT => 'Expert',
        };
    }

    public function canMentor(): bool
    {
        return in_array($this, [self::MASTER, self::EXPERT], true);
    }

    public function requiresSupervision(): bool
    {
        return $this === self::JUNIOR;
    }
}
