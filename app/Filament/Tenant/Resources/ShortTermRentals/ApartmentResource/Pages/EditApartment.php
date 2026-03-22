<?php

declare(strict_types=1);

namespace App\Filament\Tenant\Resources\ShortTermRentals\ApartmentResource\Pages;

use App\Filament\Tenant\Resources\ShortTermRentals\ApartmentResource;
use Filament\Resources\Pages\EditRecord;

final class EditApartment extends EditRecord
{
    protected static string $resource = ApartmentResource::class;

    protected function getHeaderActions(): array
    {
        return [
            \Filament\Actions\DeleteAction::make(),
        ];
    }
}
