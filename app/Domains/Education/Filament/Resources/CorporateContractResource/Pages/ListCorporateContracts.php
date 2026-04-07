<?php

declare(strict_types=1);

namespace App\Domains\Education\Filament\Resources\CorporateContractResource\Pages;

use App\Domains\Education\Filament\Resources\CorporateContractResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

final class ListCorporateContracts extends ListRecords
{
    protected static string $resource = CorporateContractResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
