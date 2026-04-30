<?php

declare(strict_types=1);

namespace Modules\Warehouse\Filament\Resources\WarehouseResource\Pages;

use Modules\Warehouse\Filament\Resources\WarehouseResource;
use Filament\Pages\Actions;
use Filament\Resources\Pages\CreateRecord;

class CreateWarehouse extends CreateRecord
{
    protected static string $resource = WarehouseResource::class;
}
