<?php

namespace App\Filament\Tenant\Resources\BeautySalonResource\Pages;

use App\Filament\Tenant\Resources\BeautySalonResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditBeautySalon extends EditRecord
{
    protected static string $resource = BeautySalonResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
