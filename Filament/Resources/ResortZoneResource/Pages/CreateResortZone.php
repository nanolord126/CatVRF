<?php

declare(strict_types=1);

namespace Filament\Resources\ResortZoneResource\Pages;

use Filament\Resources\Pages\CreateRecord;
use Filament\Resources\ResortZoneResource;

class CreateResortZone extends CreateRecord
{
    protected static string $resource = ResortZoneResource::class;

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
