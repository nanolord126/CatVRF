<?php declare(strict_types=1);

namespace App\Domains\Travel\Filament\Resources\TourismBookingResource\Pages;

use App\Domains\Travel\Filament\Resources\TourismBookingResource;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;

/**
 * Create Tourism Booking Page
 * 
 * Filament create page for tourism bookings in admin panel.
 */
final class CreateTourismBooking extends CreateRecord
{
    protected static string $resource = TourismBookingResource::class;

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
