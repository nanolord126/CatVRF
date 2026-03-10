<?php

namespace App\Filament\Tenant\Resources\Marketplace\EventBookingResource\Pages;

use App\Filament\Tenant\Resources\Marketplace\EventBookingResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditEventBooking extends EditRecord
{
    protected static string $resource = EventBookingResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
