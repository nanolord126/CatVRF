<?php

declare(strict_types=1);

namespace App\Filament\Resources\WarehouseZoneResource\Pages;

use App\Filament\Resources\WarehouseZoneResource;
use Filament\Pages\Actions;
use Filament\Resources\Pages\EditRecord;

final class EditWarehouseZone extends EditRecord
{
    protected static string $resource = WarehouseZoneResource::class;

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
