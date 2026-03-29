<?php

declare(strict_types=1);

namespace App\Filament\Tenant\Resources\Booking\Pages;

use use App\Filament\Tenant\Resources\BookingResource;;
use Filament\Resources\Pages\EditRecord;
use Filament\Actions\{ViewAction, DeleteAction};

final class EditBooking extends EditRecord
{
    protected static string $resource = BookingResource::class;

    public function getTitle(): string
    {
        return 'Edit Booking';
    }

    protected function getHeaderActions(): array
    {
        return [
            ViewAction::make(),
            DeleteAction::make(),
        ];
    }
}