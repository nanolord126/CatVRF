<?php

declare(strict_types=1);

namespace Modules\Inventory\Filament\Resources\InventoryBatchResource\Pages;

use Filament\Actions;
use Filament\Resources\Pages\EditRecord;
use Modules\Inventory\Filament\Resources\InventoryBatchResource;

final class EditInventoryBatch extends EditRecord
{
    protected static string $resource = InventoryBatchResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\ViewAction::make(),
            Actions\DeleteAction::make(),
        ];
    }
}
