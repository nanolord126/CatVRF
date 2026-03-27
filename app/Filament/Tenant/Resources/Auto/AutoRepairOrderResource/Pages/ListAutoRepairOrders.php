<?php

declare(strict_types=1);

namespace App\Filament\Tenant\Resources\Auto\AutoRepairOrderResource\Pages;

use App\Filament\Tenant\Resources\Auto\AutoRepairOrderResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

final class ListAutoRepairOrders extends ListRecords
{
    protected static string $resource = AutoRepairOrderResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
