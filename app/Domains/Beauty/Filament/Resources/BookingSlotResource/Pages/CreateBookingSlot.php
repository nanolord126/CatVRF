<?php declare(strict_types=1);

namespace App\Domains\Beauty\Filament\Resources\BookingSlotResource\Pages;

use App\Domains\Beauty\Filament\Resources\BookingSlotResource;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;

final class CreateBookingSlot extends CreateRecord
{
    protected static string $resource = BookingSlotResource::class;

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
