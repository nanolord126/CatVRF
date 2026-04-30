<?php

declare(strict_types=1);

namespace Modules\Inventory\Filament\Resources\InventoryBatchResource\Pages;

use Filament\Actions;
use Filament\Resources\Pages\ListRecords;
use Modules\Inventory\Filament\Resources\InventoryBatchResource;

final class ListInventoryBatches extends ListRecords
{
    protected static string $resource = InventoryBatchResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
