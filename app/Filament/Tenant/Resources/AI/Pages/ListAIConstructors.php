<?php

namespace App\Filament\Tenant\Resources\AI\Pages;

use App\Filament\Tenant\Resources\AI\AIConstructorResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListAIConstructors extends ListRecords
{
    protected static string $resource = AIConstructorResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}

