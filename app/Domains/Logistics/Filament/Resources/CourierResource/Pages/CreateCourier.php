<?php

declare(strict_types=1);

namespace App\Domains\Logistics\Filament\Resources\CourierResource\Pages;

use App\Domains\Logistics\Filament\Resources\CourierResource;
use Filament\Resources\Pages\CreateRecord;

final class CreateCourier extends CreateRecord
{
    protected static string $resource = CourierResource::class;
}
