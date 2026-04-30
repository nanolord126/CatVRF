<?php

declare(strict_types=1);

namespace App\Filament\Resources\WarehouseProductResource\Pages;

use App\Filament\Resources\WarehouseProductResource;
use Filament\Pages\Actions;
use Filament\Resources\Pages\ListRecords;

final class ListWarehouseProducts extends ListRecords
{
    protected static string $resource = WarehouseProductResource::class;

    protected function getActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
