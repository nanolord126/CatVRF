<?php

namespace App\Filament\Tenant\Resources\MasterResource\Pages;

use App\Filament\Tenant\Resources\MasterResource;
use Filament\Actions;
use Filament\Resources\Pages\ManageRecords;

class ManageMasters extends ManageRecords
{
    protected static string $resource = MasterResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
