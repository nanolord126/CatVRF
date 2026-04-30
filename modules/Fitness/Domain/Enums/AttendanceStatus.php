<?php

declare(strict_types=1);

namespace Modules\Fitness\Domain\Enums;

enum AttendanceStatus: string
{
    case PRESENT = 'present';
    case ABSENT = 'absent';
    case LATE = 'late';
    case EXCUSED = 'excused';

    public function getLabel(): string
    {
        return match ($this) {
            self::PRESENT => 'Present',
            self::ABSENT => 'Absent',
            self::LATE => 'Late',
            self::EXCUSED => 'Excused',
        };
    }

    public function countsAsVisit(): bool
    {
        return in_array($this, [self::PRESENT, self::LATE], true);
    }
}
