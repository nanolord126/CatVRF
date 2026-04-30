<?php

declare(strict_types=1);

namespace Modules\Restaurant\Presentation\Resources\BookingResource\Pages;

use Modules\Restaurant\Presentation\Resources\BookingResource;
use Filament\Resources\Pages\ListRecords;

final class ListBookings extends ListRecords
{
    protected static string $resource = BookingResource::class;
}
