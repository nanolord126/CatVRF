<?php

namespace App\Filament\Tenant\Resources\Marketplace\EventBookingResource\Pages;

use App\Filament\Tenant\Resources\Marketplace\EventBookingResource;
use Filament\Resources\Pages\CreateRecord;

class CreateEventBooking extends CreateRecord
{
    protected static string $resource = EventBookingResource::class;
}
