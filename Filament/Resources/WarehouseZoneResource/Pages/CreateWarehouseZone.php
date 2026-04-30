<?php

declare(strict_types=1);

namespace App\Filament\Resources\WarehouseZoneResource\Pages;

use App\Filament\Resources\WarehouseZoneResource;
use Filament\Pages\Actions;
use Filament\Resources\Pages\CreateRecord;

final class CreateWarehouseZone extends CreateRecord
{
    protected static string $resource = WarehouseZoneResource::class;

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
