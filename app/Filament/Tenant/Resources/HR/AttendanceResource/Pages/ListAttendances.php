<?php

namespace App\Filament\Tenant\Resources\HR\AttendanceResource\Pages;

use App\Filament\Tenant\Resources\HR\AttendanceResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListAttendances extends ListRecords
{
    protected static string $resource = AttendanceResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
