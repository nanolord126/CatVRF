<?php

namespace App\Filament\Tenant\Resources\VenueResource\Pages;

use App\Filament\Tenant\Resources\VenueResource;
use Filament\Actions;
use Filament\Resources\Pages\ManageRecords;

class ManageVenues extends ManageRecords
{
    protected static string $resource = VenueResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make()
                ->label('Добавить площадку')
                ->icon('heroicon-o-plus-circle'),
        ];
    }
}
