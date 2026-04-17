<?php declare(strict_types=1);

namespace Modules\RealEstate\Filament\Resources\PropertyBookingResource\Pages;

use Modules\RealEstate\Filament\Resources\PropertyBookingResource;
use Filament\Resources\Pages\CreateRecord;

final class CreatePropertyBooking extends CreateRecord
{
    protected static string $resource = PropertyBookingResource::class;
}
