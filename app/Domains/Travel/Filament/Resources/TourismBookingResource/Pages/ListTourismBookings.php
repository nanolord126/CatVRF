<?php declare(strict_types=1);

namespace App\Domains\Travel\Filament\Resources\TourismBookingResource\Pages;

use App\Domains\Travel\Filament\Resources\TourismBookingResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

/**
 * List Tourism Bookings Page
 * 
 * Filament list page for tourism bookings in admin panel.
 */
final class ListTourismBookings extends ListRecords
{
    protected static string $resource = TourismBookingResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
