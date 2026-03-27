<?php

declare(strict_types=1);

namespace App\Filament\Tenant\Resources\DentalServiceResource\Pages;

use App\Filament\Tenant\Resources\DentalServiceResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

final class ListDentalServices extends ListRecords
{
    protected static string $resource = DentalServiceResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
