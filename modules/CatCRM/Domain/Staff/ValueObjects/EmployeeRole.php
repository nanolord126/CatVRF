<?php

declare(strict_types=1);

namespace Modules\CatCRM\Domain\Staff\ValueObjects;

/**
 * EmployeeRole — Enum роли сотрудника
 */
enum EmployeeRole: string
{
    case Admin = 'admin';
    case Manager = 'manager';
    case Specialist = 'specialist';
    case Junior = 'junior';
    case Intern = 'intern';
    case Contractor = 'contractor';
}
