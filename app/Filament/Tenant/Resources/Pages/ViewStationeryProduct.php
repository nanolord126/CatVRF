<?php

declare(strict_types=1);

namespace App\Filament\Tenant\Resources\StationeryProduct\Pages;

use use App\Filament\Tenant\Resources\StationeryProductResource;;
use Filament\Resources\Pages\ViewRecord;

final class ViewStationeryProduct extends ViewRecord
{
    protected static string $resource = StationeryProductResource::class;

    public function getTitle(): string
    {
        return 'View StationeryProduct';
    }
}