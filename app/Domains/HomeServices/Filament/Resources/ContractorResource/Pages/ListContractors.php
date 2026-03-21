<?php declare(strict_types=1);

namespace App\Domains\HomeServices\Filament\Resources\ContractorResource\Pages;

use App\Domains\HomeServices\Filament\Resources\ContractorResource;
use Filament\Resources\Pages\ListRecords;

final class ListContractors extends ListRecords
{
    protected static string $resource = ContractorResource::class;

    protected function getHeaderActions(): array
    {
        return [\Filament\Actions\CreateAction::make()];
    }
}
