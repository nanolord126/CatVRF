<?php declare(strict_types=1);

namespace App\Domains\Travel\Filament\Resources\TourismBookingResource\Pages;

use App\Domains\Travel\Filament\Resources\TourismBookingResource;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;

/**
 * View Tourism Booking Page
 * 
 * Filament view page for tourism bookings in admin panel.
 */
final class ViewTourismBooking extends ViewRecord
{
    protected static string $resource = TourismBookingResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\EditAction::make(),
            Actions\DeleteAction::make(),
        ];
    }
}
