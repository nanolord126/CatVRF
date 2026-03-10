<?php

namespace App\Filament\Tenant\Resources\HR\EmployeeResource\Pages;

use App\Filament\Tenant\Resources\HR\EmployeeResource;
use Filament\Resources\Pages\ListRecords;

class ListEmployees extends ListRecords
{
    protected static string $resource = EmployeeResource::class;
}
