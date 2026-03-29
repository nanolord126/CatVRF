<?php

declare(strict_types=1);

namespace App\Filament\Tenant\Resources\Booking\Pages;

use use App\Filament\Tenant\Resources\BookingResource;;
use Filament\Resources\Pages\ViewRecord;

final class ViewBooking extends ViewRecord
{
    protected static string $resource = BookingResource::class;

    public function getTitle(): string
    {
        return 'View Booking';
    }
}