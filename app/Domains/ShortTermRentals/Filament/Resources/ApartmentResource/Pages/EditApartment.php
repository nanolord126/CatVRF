<?php declare(strict_types=1);

namespace App\Domains\ShortTermRentals\Filament\Resources\ApartmentResource\Pages;

use App\Domains\ShortTermRentals\Filament\Resources\ApartmentResource;
use Filament\Resources\Pages\EditRecord;

final class EditApartment extends EditRecord
{
    protected static string $resource = ApartmentResource::class;

    protected function getHeaderActions(): array
    {
        return [\Filament\Actions\DeleteAction::make()];
    }
}
