<?php declare(strict_types=1);

namespace App\Domains\Entertainment\Filament\Resources\BookingResource\Pages;

use App\Domains\Entertainment\Filament\Resources\BookingResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

final class EditBooking extends EditRecord
{
    protected static string $resource = BookingResource::class;

    protected function getHeaderActions(): array
    {
        return [Actions\DeleteAction::make()];
    }
}
