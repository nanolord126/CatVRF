<?php

namespace App\Filament\Tenant\Resources\HR\HRExchangeTaskResource\Pages;

use App\Filament\Tenant\Resources\HR\HRExchangeTaskResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListHRExchangeTasks extends ListRecords
{
    protected static string $resource = HRExchangeTaskResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
