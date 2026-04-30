<?php

declare(strict_types=1);

namespace App\Filament\Resources\WarehouseLicenseResource\Pages;

use App\Filament\Resources\WarehouseLicenseResource;
use Filament\Pages\Actions;
use Filament\Resources\Pages\EditRecord;

final class EditWarehouseLicense extends EditRecord
{
    protected static string $resource = WarehouseLicenseResource::class;

    protected function getActions(): array
    {
        return [
            Actions\ViewAction::make(),
            Actions\DeleteAction::make(),
            Actions\SaveAction::make(),
        ];
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
