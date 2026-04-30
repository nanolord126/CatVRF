<?php

declare(strict_types=1);

namespace Filament\Resources\ResortZoneResource\Pages;

use Filament\Actions;
use Filament\Resources\Pages\EditRecord;
use Filament\Resources\ResortZoneResource;

class EditResortZone extends EditRecord
{
    protected static string $resource = ResortZoneResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\ViewAction::make(),
            Actions\DeleteAction::make(),
        ];
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
