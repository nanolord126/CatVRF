<?php

declare(strict_types=1);

namespace App\Filament\Tenant\Resources\Psychology\BookingResource\Pages;

use App\Filament\Tenant\Resources\Psychology\BookingResource;
use Filament\Resources\Pages\ListRecords;

final class ListBookings extends ListRecords
{
    protected static string $resource = BookingResource::class;
}
