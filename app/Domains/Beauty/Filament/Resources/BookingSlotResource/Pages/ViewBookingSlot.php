<?php declare(strict_types=1);

namespace App\Domains\Beauty\Filament\Resources\BookingSlotResource\Pages;

use App\Domains\Beauty\Filament\Resources\BookingSlotResource;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;

final class ViewBookingSlot extends ViewRecord
{
    protected static string $resource = BookingSlotResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\EditAction::make(),
            Actions\DeleteAction::make(),
        ];
    }
}
