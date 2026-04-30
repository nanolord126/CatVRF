<?php

declare(strict_types=1);

namespace Modules\Warehouse\Filament\Resources\ZoneResource\Pages;

use Modules\Warehouse\Filament\Resources\ZoneResource;
use Filament\Pages\Actions;
use Filament\Resources\Pages\EditRecord;

class EditZone extends EditRecord
{
    protected static string $resource = ZoneResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\ViewAction::make(),
            Actions\DeleteAction::make(),
        ];
    }
}
