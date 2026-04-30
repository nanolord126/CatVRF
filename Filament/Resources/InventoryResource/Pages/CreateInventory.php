<?php

declare(strict_types=1);

namespace App\Filament\Resources\InventoryResource\Pages;

use App\Filament\Resources\InventoryResource;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;

final class CreateInventory extends CreateRecord
{
    protected static string $resource = InventoryResource::class;
}
