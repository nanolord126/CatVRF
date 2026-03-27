<?php

declare(strict_types=1);

namespace App\Filament\Tenant\Resources\Entertainment\SeatMapResource\Pages;

use App\Filament\Tenant\Resources\Entertainment\SeatMapResource;
use Filament\Resources\Pages\ListRecords;

final class ListSeatMaps extends ListRecords
{
    protected static string $resource = SeatMapResource::class;
}
