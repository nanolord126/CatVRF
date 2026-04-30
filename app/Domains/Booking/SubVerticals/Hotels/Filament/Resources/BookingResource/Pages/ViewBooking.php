<?php

declare(strict_types=1);

namespace Modules\Hotels\Filament\Resources\BookingResource\Pages;

use Modules\Hotels\Filament\Resources\BookingResource;
use Filament\Pages\Actions;
use Filament\Resources\Pages\ViewRecord;

final class ViewBooking extends ViewRecord
{
    protected static string $resource = BookingResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\EditAction::make(),
            Actions\DeleteAction::make(),
        ];
    }
}
