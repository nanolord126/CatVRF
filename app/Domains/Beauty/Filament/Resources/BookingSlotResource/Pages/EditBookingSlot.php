<?php declare(strict_types=1);

namespace App\Domains\Beauty\Filament\Resources\BookingSlotResource\Pages;

use App\Domains\Beauty\Filament\Resources\BookingSlotResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

final class EditBookingSlot extends EditRecord
{
    protected static string $resource = BookingSlotResource::class;

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
