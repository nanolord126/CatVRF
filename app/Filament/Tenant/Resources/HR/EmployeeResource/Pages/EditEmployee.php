<?php

namespace App\Filament\Tenant\Resources\HR\EmployeeResource\Pages;

use App\Filament\Tenant\Resources\HR\EmployeeResource;
use Filament\Resources\Pages\EditRecord;

class EditEmployee extends EditRecord
{
    protected static string $resource = EmployeeResource::class;
}
