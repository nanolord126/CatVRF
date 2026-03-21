<?php declare(strict_types=1);

namespace App\Filament\Tenant\Resources\Auto\TaxiRideResource\Pages;

use App\Filament\Tenant\Resources\Auto\TaxiRideResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

final class EditTaxiRide extends EditRecord
{
    protected static string $resource = TaxiRideResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\ViewAction::make(),
            Actions\DeleteAction::make(),
        ];
    }
}
