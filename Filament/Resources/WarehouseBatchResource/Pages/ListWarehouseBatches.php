<?php

declare(strict_types=1);

namespace App\Filament\Resources\WarehouseBatchResource\Pages;

use App\Filament\Resources\WarehouseBatchResource;
use Filament\Pages\Actions;
use Filament\Resources\Pages\ListRecords;

final class ListWarehouseBatches extends ListRecords
{
    protected static string $resource = WarehouseBatchResource::class;

    protected function getActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
