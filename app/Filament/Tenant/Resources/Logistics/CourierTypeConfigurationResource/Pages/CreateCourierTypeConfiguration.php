<?php

declare(strict_types=1);

namespace App\Filament\Tenant\Resources\Logistics\CourierTypeConfigurationResource\Pages;

use App\Filament\Tenant\Resources\Logistics\CourierTypeConfigurationResource;
use Filament\Resources\Pages\CreateRecord;

final class CreateCourierTypeConfiguration extends CreateRecord
{
    protected static string $resource = CourierTypeConfigurationResource::class;
}
