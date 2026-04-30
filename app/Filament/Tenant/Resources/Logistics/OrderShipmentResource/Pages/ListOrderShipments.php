<?php

declare(strict_types=1);

namespace App\Filament\Tenant\Resources\Logistics\OrderShipmentResource\Pages;

use App\Filament\Tenant\Resources\Logistics\OrderShipmentResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

final class ListOrderShipments extends ListRecords
{
    protected static string $resource = OrderShipmentResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
