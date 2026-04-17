<?php declare(strict_types=1);

namespace App\Domains\Travel\Filament\Resources\TourismBookingResource\Pages;

use App\Domains\Travel\Filament\Resources\TourismBookingResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

/**
 * Edit Tourism Booking Page
 * 
 * Filament edit page for tourism bookings in admin panel.
 */
final class EditTourismBooking extends EditRecord
{
    protected static string $resource = TourismBookingResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\ViewAction::make(),
            Actions\DeleteAction::make(),
        ];
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
