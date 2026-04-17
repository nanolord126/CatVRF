<?php declare(strict_types=1);

namespace App\Domains\Taxi\Filament\Resources\TaxiTariffResource\Pages;

use App\Domains\Taxi\Filament\Resources\TaxiTariffResource;
use Filament\Pages\Actions;
use Filament\Resources\Pages\CreateRecord;

final class CreateTaxiTariff extends CreateRecord
{
    protected static string $resource = TaxiTariffResource::class;

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }

    protected function getHeaderActions(): array
    {
        return [
            Actions\ViewAction::make(),
        ];
    }
}
