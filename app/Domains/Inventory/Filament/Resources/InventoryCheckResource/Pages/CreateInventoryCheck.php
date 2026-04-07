<?php

declare(strict_types=1);

namespace App\Domains\Inventory\Filament\Resources\InventoryCheckResource\Pages;

use App\Domains\Inventory\Filament\Resources\InventoryCheckResource;
use Filament\Resources\Pages\CreateRecord;

/**
 * Создание инвентаризации.
 */
final class CreateInventoryCheck extends CreateRecord
{
    protected static string $resource = InventoryCheckResource::class;

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
