<?php

declare(strict_types=1);

namespace Modules\Fitness\Filament\Resources\Corporate\Pages;

use Modules\Fitness\Filament\Resources\Corporate\EmployeeMembershipResource;
use Filament\Resources\Pages\CreateRecord;

final class CreateEmployeeMembership extends CreateRecord
{
    protected static string $resource = EmployeeMembershipResource::class;
}
