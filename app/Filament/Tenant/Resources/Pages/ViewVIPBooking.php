<?php

declare(strict_types=1);

namespace App\Filament\Tenant\Resources\VIPBooking\Pages;

use use App\Filament\Tenant\Resources\VIPBookingResource;;
use Filament\Resources\Pages\ViewRecord;

final class ViewVIPBooking extends ViewRecord
{
    protected static string $resource = VIPBookingResource::class;

    public function getTitle(): string
    {
        return 'View VIPBooking';
    }
}