<?php

namespace App\Filament\Tenant\Resources\MedicalCardResource\Pages;

use App\Filament\Tenant\Resources\MedicalCardResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditMedicalCard extends EditRecord
{
    protected static string $resource = MedicalCardResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
