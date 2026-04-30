<?php

declare(strict_types=1);

namespace Modules\Warehouse\Filament\Resources\ProductResource\Pages;

use Modules\Warehouse\Filament\Resources\ProductResource;
use Filament\Resources\Pages\CreateRecord;

class CreateProduct extends CreateRecord
{
    protected static string $resource = ProductResource::class;
}
