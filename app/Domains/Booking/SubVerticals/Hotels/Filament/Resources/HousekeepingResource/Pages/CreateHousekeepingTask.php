<?php

declare(strict_types=1);

namespace Modules\Hotels\Filament\Resources\HousekeepingResource\Pages;

use Modules\Hotels\Filament\Resources\HousekeepingResource;
use Filament\Pages\Actions;
use Filament\Resources\Pages\CreateRecord;

final class CreateHousekeepingTask extends CreateRecord
{
    protected static string $resource = HousekeepingResource::class;

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
