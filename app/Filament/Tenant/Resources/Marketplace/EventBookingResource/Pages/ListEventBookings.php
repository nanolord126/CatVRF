<?php

namespace App\Filament\Tenant\Resources\Marketplace\EventBookingResource\Pages;

use App\Filament\Tenant\Resources\Marketplace\EventBookingResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListEventBookings extends ListRecords
{
    protected static string $resource = EventBookingResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
