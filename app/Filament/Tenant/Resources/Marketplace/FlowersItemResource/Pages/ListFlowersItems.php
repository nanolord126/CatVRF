<?php

namespace App\Filament\Tenant\Resources\Marketplace\FlowersItemResource\Pages;

use App\Filament\Tenant\Resources\Marketplace\FlowersItemResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListFlowersItems extends ListRecords
{
    protected static string $resource = FlowersItemResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
