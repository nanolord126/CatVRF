<?php

namespace App\Filament\B2B\Resources\B2BOrderResource\Pages;

use App\Filament\B2B\Resources\B2BOrderResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListB2BOrders extends ListRecords
{
    protected static string $resource = B2BOrderResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
