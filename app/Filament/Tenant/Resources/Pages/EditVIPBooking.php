<?php

declare(strict_types=1);

namespace App\Filament\Tenant\Resources\VIPBooking\Pages;

use use App\Filament\Tenant\Resources\VIPBookingResource;;
use Filament\Resources\Pages\EditRecord;
use Filament\Actions\{ViewAction, DeleteAction};

final class EditVIPBooking extends EditRecord
{
    protected static string $resource = VIPBookingResource::class;

    public function getTitle(): string
    {
        return 'Edit VIPBooking';
    }

    protected function getHeaderActions(): array
    {
        return [
            ViewAction::make(),
            DeleteAction::make(),
        ];
    }
}