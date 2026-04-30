<?php

declare(strict_types=1);

namespace Modules\Inventory\Filament\Resources\InventoryItemResource\Pages;

use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;
use Modules\Inventory\Filament\Resources\InventoryItemResource;

final class CreateInventoryItem extends CreateRecord
{
    protected static string $resource = InventoryItemResource::class;
}
