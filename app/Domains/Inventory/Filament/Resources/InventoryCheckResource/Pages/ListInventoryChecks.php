<?php

declare(strict_types=1);

namespace App\Domains\Inventory\Filament\Resources\InventoryCheckResource\Pages;

use App\Domains\Inventory\Filament\Resources\InventoryCheckResource;
use Filament\Resources\Pages\ListRecords;

/**
 * Список инвентаризаций.
 */
final class ListInventoryChecks extends ListRecords
{
    protected static string $resource = InventoryCheckResource::class;

    protected function getHeaderActions(): array
    {
        return [
            \Filament\Actions\CreateAction::make(),
        ];
    }
}
