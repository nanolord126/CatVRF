<?php

namespace App\Filament\Tenant\Resources\MedicalCardResource\Pages;

use App\Filament\Tenant\Resources\MedicalCardResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListMedicalCards extends ListRecords
{
    protected static string $resource = MedicalCardResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
