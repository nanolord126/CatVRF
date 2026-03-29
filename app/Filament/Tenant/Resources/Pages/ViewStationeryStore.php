<?php

declare(strict_types=1);

namespace App\Filament\Tenant\Resources\StationeryStore\Pages;

use use App\Filament\Tenant\Resources\StationeryStoreResource;;
use Filament\Resources\Pages\ViewRecord;

final class ViewStationeryStore extends ViewRecord
{
    protected static string $resource = StationeryStoreResource::class;

    public function getTitle(): string
    {
        return 'View StationeryStore';
    }
}