<?php

declare(strict_types=1);

namespace Modules\Warehouse\Filament\Resources\ZoneResource\Pages;

use Modules\Warehouse\Filament\Resources\ZoneResource;
use Filament\Pages\Actions;
use Filament\Resources\Pages\ViewRecord;

class ViewZone extends ViewRecord
{
    protected static string $resource = ZoneResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\EditAction::make(),
            Actions\DeleteAction::make(),
        ];
    }
}
