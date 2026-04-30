<?php

declare(strict_types=1);

namespace Modules\Warehouse\Filament\Resources\ProductResource\Pages;

use Modules\Warehouse\Filament\Resources\ProductResource;
use Filament\Pages\Actions;
use Filament\Resources\Pages\ViewRecord;

class ViewProduct extends ViewRecord
{
    protected static string $resource = ProductResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\EditAction::make(),
            Actions\DeleteAction::make(),
        ];
    }
}
