<?php

namespace App\Filament\Tenant\Resources\HotelBookingResource\Pages;

use App\Filament\Tenant\Resources\HotelBookingResource;
use Filament\Actions;
use Filament\Resources\Pages\ManageRecords;

class ManageHotelBookings extends ManageRecords
{
    protected static string $resource = HotelBookingResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
