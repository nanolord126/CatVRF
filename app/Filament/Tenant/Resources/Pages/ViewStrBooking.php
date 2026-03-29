<?php

declare(strict_types=1);

namespace App\Filament\Tenant\Resources\StrBooking\Pages;

use use App\Filament\Tenant\Resources\StrBookingResource;;
use Filament\Resources\Pages\ViewRecord;

final class ViewStrBooking extends ViewRecord
{
    protected static string $resource = StrBookingResource::class;

    public function getTitle(): string
    {
        return 'View StrBooking';
    }
}