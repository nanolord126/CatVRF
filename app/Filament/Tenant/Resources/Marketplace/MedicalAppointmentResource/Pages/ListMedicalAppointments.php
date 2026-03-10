<?php

namespace App\Filament\Tenant\Resources\Marketplace\MedicalAppointmentResource\Pages;

use App\Filament\Tenant\Resources\Marketplace\MedicalAppointmentResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListMedicalAppointments extends ListRecords
{
    protected static string $resource = MedicalAppointmentResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
