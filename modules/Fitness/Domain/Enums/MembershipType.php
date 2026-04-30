<?php

declare(strict_types=1);

namespace Modules\Fitness\Domain\Enums;

enum MembershipType: string
{
    case MONTHLY = 'monthly';
    case QUARTERLY = 'quarterly';
    case ANNUAL = 'annual';
    case UNLIMITED = 'unlimited';
    case PUNCH_CARD = 'punch_card';
    case CORPORATE = 'corporate';
    case TRIAL = 'trial';
    case SENIOR = 'senior';
    case STUDENT = 'student';

    public function getLabel(): string
    {
        return match ($this) {
            self::MONTHLY => 'Monthly',
            self::QUARTERLY => 'Quarterly',
            self::ANNUAL => 'Annual',
            self::UNLIMITED => 'Unlimited',
            self::PUNCH_CARD => 'Punch Card',
            self::CORPORATE => 'Corporate',
            self::TRIAL => 'Trial',
            self::SENIOR => 'Senior (55+)',
            self::STUDENT => 'Student',
        };
    }

    public function getDefaultDurationDays(): int
    {
        return match ($this) {
            self::MONTHLY => 30,
            self::QUARTERLY => 90,
            self::ANNUAL => 365,
            self::UNLIMITED => 365,
            self::PUNCH_CARD => 365,
            self::CORPORATE => 365,
            self::TRIAL => 7,
            self::SENIOR => 365,
            self::STUDENT => 30,
        };
    }
}
