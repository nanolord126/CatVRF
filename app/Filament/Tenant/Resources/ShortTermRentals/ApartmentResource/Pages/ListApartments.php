<?php

declare(strict_types=1);

namespace App\Filament\Tenant\Resources\ShortTermRentals\ApartmentResource\Pages;

use App\Filament\Tenant\Resources\ShortTermRentals\ApartmentResource;
use Filament\Resources\Pages\ListRecords;

final class ListApartments extends ListRecords
{
    protected static string $resource = ApartmentResource::class;

    protected function getHeaderActions(): array
    {
        return [
            \Filament\Actions\CreateAction::make(),
        ];
    }
}
