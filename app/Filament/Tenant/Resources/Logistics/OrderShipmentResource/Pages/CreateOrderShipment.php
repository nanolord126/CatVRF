<?php

declare(strict_types=1);

namespace App\Filament\Tenant\Resources\Logistics\OrderShipmentResource\Pages;

use App\Filament\Tenant\Resources\Logistics\OrderShipmentResource;
use Filament\Resources\Pages\CreateRecord;

final class CreateOrderShipment extends CreateRecord
{
    protected static string $resource = OrderShipmentResource::class;
}
