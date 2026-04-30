<?php

declare(strict_types=1);

namespace App\Filament\Tenant\Resources\Logistics\PickupPointResource\Pages;

use App\Filament\Tenant\Resources\Logistics\PickupPointResource;
use Filament\Resources\Pages\CreateRecord;

final class CreatePickupPoint extends CreateRecord
{
    protected static string $resource = PickupPointResource::class;
}
