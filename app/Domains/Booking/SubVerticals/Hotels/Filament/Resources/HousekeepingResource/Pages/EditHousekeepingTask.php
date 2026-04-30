<?php

declare(strict_types=1);

namespace Modules\Hotels\Filament\Resources\HousekeepingResource\Pages;

use Modules\Hotels\Filament\Resources\HousekeepingResource;
use Filament\Pages\Actions;
use Filament\Resources\Pages\EditRecord;

final class EditHousekeepingTask extends EditRecord
{
    protected static string $resource = HousekeepingResource::class;

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
