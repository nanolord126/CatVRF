<?php

declare(strict_types=1);

namespace App\Filament\Tenant\Resources\RentalContractResource\Pages;

use App\Filament\Tenant\Resources\RentalContractResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

final class ListRentalContracts extends ListRecords
{
    protected static string $resource = RentalContractResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
