<?php

declare(strict_types=1);

namespace App\Filament\Tenant\Resources\TaxiRide\Pages;

use use App\Filament\Tenant\Resources\TaxiRideResource;;
use Filament\Resources\Pages\EditRecord;
use Filament\Actions\{ViewAction, DeleteAction};

final class EditTaxiRide extends EditRecord
{
    protected static string $resource = TaxiRideResource::class;

    public function getTitle(): string
    {
        return 'Edit TaxiRide';
    }

    protected function getHeaderActions(): array
    {
        return [
            ViewAction::make(),
            DeleteAction::make(),
        ];
    }
}