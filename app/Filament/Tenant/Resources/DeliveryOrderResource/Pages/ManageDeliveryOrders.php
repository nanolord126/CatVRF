<?php

namespace App\Filament\Tenant\Resources\DeliveryOrderResource\Pages;

use App\Filament\Tenant\Resources\DeliveryOrderResource;
use Filament\Actions;
use Filament\Resources\Pages\ManageRecords;

class ManageDeliveryOrders extends ManageRecords
{
    protected static string $resource = DeliveryOrderResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
