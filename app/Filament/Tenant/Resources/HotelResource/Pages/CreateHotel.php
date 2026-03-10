<?php

namespace App\Filament\Tenant\Resources\HotelResource\Pages;

use App\Filament\Tenant\Resources\HotelResource;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;

class CreateHotel extends CreateRecord
{
    protected static string $resource = HotelResource::class;
}
