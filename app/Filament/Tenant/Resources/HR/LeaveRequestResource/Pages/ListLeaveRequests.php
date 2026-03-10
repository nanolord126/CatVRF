<?php

namespace App\Filament\Tenant\Resources\HR\LeaveRequestResource\Pages;

use App\Filament\Tenant\Resources\HR\LeaveRequestResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListLeaveRequests extends ListRecords
{
    protected static string $resource = LeaveRequestResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
