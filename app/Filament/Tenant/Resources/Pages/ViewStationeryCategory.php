<?php

declare(strict_types=1);

namespace App\Filament\Tenant\Resources\StationeryCategory\Pages;

use use App\Filament\Tenant\Resources\StationeryCategoryResource;;
use Filament\Resources\Pages\ViewRecord;

final class ViewStationeryCategory extends ViewRecord
{
    protected static string $resource = StationeryCategoryResource::class;

    public function getTitle(): string
    {
        return 'View StationeryCategory';
    }
}