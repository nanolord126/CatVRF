<?php declare(strict_types=1);

namespace Modules\RealEstate\Filament\Resources\PropertyBookingResource\Pages;

use Modules\RealEstate\Filament\Resources\PropertyBookingResource;
use Filament\Resources\Pages\ListRecords;

final class ListPropertyBookings extends ListRecords
{
    protected static string $resource = PropertyBookingResource::class;
}
