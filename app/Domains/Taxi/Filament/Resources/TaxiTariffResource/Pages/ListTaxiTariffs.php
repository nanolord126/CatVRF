<?php declare(strict_types=1);

namespace App\Domains\Taxi\Filament\Resources\TaxiTariffResource\Pages;

use App\Domains\Taxi\Filament\Resources\TaxiTariffResource;
use Filament\Pages\Actions;
use Filament\Resources\Pages\ListRecords;

final class ListTaxiTariffs extends ListRecords
{
    protected static string $resource = TaxiTariffResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
