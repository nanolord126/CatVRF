<?php

namespace App\Filament\B2B\Resources\B2BProductResource\Pages;

use App\Filament\B2B\Resources\B2BProductResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListB2BProducts extends ListRecords
{
    protected static string $resource = B2BProductResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
