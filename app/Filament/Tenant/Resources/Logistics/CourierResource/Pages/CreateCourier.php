<?php declare(strict_types=1);

namespace App\Filament\Tenant\Resources\Logistics\CourierResource\Pages;

use App\Filament\Tenant\Resources\Logistics\CourierResource;
use Filament\Resources\Pages\CreateRecord;

final class CreateCourier extends CreateRecord
{
    protected static string $resource = CourierResource::class;
}
