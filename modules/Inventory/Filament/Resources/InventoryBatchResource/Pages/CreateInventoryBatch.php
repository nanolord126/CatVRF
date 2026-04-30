<?php

declare(strict_types=1);

namespace Modules\Inventory\Filament\Resources\InventoryBatchResource\Pages;

use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;
use Modules\Inventory\Filament\Resources\InventoryBatchResource;

final class CreateInventoryBatch extends CreateRecord
{
    protected static string $resource = InventoryBatchResource::class;
}
