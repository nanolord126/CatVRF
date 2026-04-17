<?php declare(strict_types=1);

namespace Modules\Taxi\Filament\TaxiRideResource\Pages;

use Modules\Taxi\Filament\TaxiRideResource;
use Filament\Pages\Actions;
use Filament\Resources\Pages\CreateRecord;

final class CreateTaxiRide extends CreateRecord
{
    protected static string $resource = TaxiRideResource::class;

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }

    protected function getFormActions(): array
    {
        return [
            $this->getCreateFormAction(),
            Actions\CancelAction::make(),
        ];
    }
}
