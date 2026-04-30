<?php

declare(strict_types=1);

namespace App\Filament\Resources\WarehouseLicenseResource\Pages;

use App\Filament\Resources\WarehouseLicenseResource;
use Filament\Pages\Actions;
use Filament\Resources\Pages\ListRecords;

final class ListWarehouseLicenses extends ListRecords
{
    protected static string $resource = WarehouseLicenseResource::class;

    protected function getActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
