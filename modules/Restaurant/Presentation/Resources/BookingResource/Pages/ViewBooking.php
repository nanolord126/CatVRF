<?php

declare(strict_types=1);

namespace Modules\Restaurant\Presentation\Resources\BookingResource\Pages;

use Modules\Restaurant\Presentation\Resources\BookingResource;
use Filament\Resources\Pages\ViewRecord;

final class ViewBooking extends ViewRecord
{
    protected static string $resource = BookingResource::class;
}
