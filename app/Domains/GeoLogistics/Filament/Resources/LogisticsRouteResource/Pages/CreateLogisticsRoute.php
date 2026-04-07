<?php

declare(strict_types=1);

namespace App\Domains\GeoLogistics\Filament\Resources\LogisticsRouteResource\Pages;

use App\Domains\GeoLogistics\Filament\Resources\LogisticsRouteResource;
use Filament\Resources\Pages\CreateRecord;

final class CreateLogisticsRoute extends CreateRecord
{
    protected static string $resource = LogisticsRouteResource::class;
}
