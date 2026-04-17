<?php declare(strict_types=1);

namespace App\Domains\Taxi\Filament\Resources\TaxiRideResource\Pages;

use App\Domains\Taxi\Filament\Resources\TaxiRideResource;
use Filament\Pages\Actions;
use Filament\Resources\Pages\CreateRecord;

final class CreateTaxiRide extends CreateRecord
{
    protected static string $resource = TaxiRideResource::class;

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
