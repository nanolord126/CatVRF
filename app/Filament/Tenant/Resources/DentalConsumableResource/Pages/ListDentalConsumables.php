<?php

declare(strict_types=1);

namespace App\Filament\Tenant\Resources\DentalConsumableResource\Pages;

use App\Filament\Tenant\Resources\DentalConsumableResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

final class ListDentalConsumables extends ListRecords
{
    protected static string $resource = DentalConsumableResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
