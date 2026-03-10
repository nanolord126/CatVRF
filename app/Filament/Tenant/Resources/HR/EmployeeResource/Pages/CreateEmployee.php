<?php

namespace App\Filament\Tenant\Resources\HR\EmployeeResource\Pages;

use App\Filament\Tenant\Resources\HR\EmployeeResource;
use Filament\Resources\Pages\CreateRecord;

class CreateEmployee extends CreateRecord
{
    protected static string $resource = EmployeeResource::class;
}
