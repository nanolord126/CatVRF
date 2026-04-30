<?php

declare(strict_types=1);

namespace App\Filament\Resources\WarehouseLicenseResource\Pages;

use App\Filament\Resources\WarehouseLicenseResource;
use Filament\Pages\Actions;
use Filament\Resources\Pages\CreateRecord;

final class CreateWarehouseLicense extends CreateRecord
{
    protected static string $resource = WarehouseLicenseResource::class;

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
