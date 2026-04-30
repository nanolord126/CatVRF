<?php

declare(strict_types=1);

namespace Modules\Inventory\Filament\Resources\InventoryBatchResource\Pages;

use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;
use Modules\Inventory\Filament\Resources\InventoryBatchResource;

final class ViewInventoryBatch extends ViewRecord
{
    protected static string $resource = InventoryBatchResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\EditAction::make(),
        ];
    }
}
