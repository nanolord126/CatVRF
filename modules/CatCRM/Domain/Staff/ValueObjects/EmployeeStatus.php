<?php

declare(strict_types=1);

namespace Modules\CatCRM\Domain\Staff\ValueObjects;

/**
 * EmployeeStatus — Enum статуса сотрудника
 */
enum EmployeeStatus: string
{
    case Active = 'active';
    case OnLeave = 'on_leave';
    case Terminated = 'terminated';
    case Suspended = 'suspended';
    case Probation = 'probation';
}
