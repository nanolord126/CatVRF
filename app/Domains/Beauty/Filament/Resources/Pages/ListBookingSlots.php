<?php declare(strict_types=1);

namespace App\Domains\Beauty\Filament\Resources\Pages;

use App\Domains\Beauty\Filament\Resources\BookingSlotResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

final class ListBookingSlots extends ListRecords
{
    protected static string $resource = BookingSlotResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
