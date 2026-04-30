<?php

declare(strict_types=1);

namespace Modules\Hotels\Filament\Resources\HousekeepingResource\Pages;

use Modules\Hotels\Filament\Resources\HousekeepingResource;
use Filament\Pages\Actions;
use Filament\Resources\Pages\ViewRecord;

final class ViewHousekeepingTask extends ViewRecord
{
    protected static string $resource = HousekeepingResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\EditAction::make(),
            Actions\DeleteAction::make(),
        ];
    }
}
