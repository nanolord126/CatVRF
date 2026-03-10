<?php

namespace App\Filament\Tenant\Resources\StaffTaskResource\Pages;

use App\Filament\Tenant\Resources\StaffTaskResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListStaffTasks extends ListRecords
{
    protected static string $resource = StaffTaskResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
