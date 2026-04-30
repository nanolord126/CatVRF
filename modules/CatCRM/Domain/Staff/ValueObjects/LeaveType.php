<?php

declare(strict_types=1);

namespace Modules\CatCRM\Domain\Staff\ValueObjects;

/**
 * LeaveType — Enum типа отпуска
 */
enum LeaveType: string
{
    case Vacation = 'vacation';
    case SickLeave = 'sick_leave';
    case Personal = 'personal';
    case Unpaid = 'unpaid';
    case Maternity = 'maternity';
    case Paternity = 'paternity';
}
