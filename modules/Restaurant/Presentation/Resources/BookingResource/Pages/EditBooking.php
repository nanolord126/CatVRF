<?php

declare(strict_types=1);

namespace Modules\Restaurant\Presentation\Resources\BookingResource\Pages;

use Modules\Restaurant\Presentation\Resources\BookingResource;
use Filament\Resources\Pages\EditRecord;

final class EditBooking extends EditRecord
{
    protected static string $resource = BookingResource::class;
}
