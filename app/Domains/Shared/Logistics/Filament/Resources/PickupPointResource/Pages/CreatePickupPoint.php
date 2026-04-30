<?php

declare(strict_types=1);

namespace App\Domains\Logistics\Filament\Resources\PickupPointResource\Pages;

use App\Domains\Logistics\Filament\Resources\PickupPointResource;
use Filament\Resources\Pages\CreateRecord;

final class CreatePickupPoint extends CreateRecord
{
    protected static string $resource = PickupPointResource::class;
}
