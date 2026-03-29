<?php

declare(strict_types=1);

namespace App\Filament\Tenant\Resources\StationeryGiftSet\Pages;

use use App\Filament\Tenant\Resources\StationeryGiftSetResource;;
use Filament\Resources\Pages\ViewRecord;

final class ViewStationeryGiftSet extends ViewRecord
{
    protected static string $resource = StationeryGiftSetResource::class;

    public function getTitle(): string
    {
        return 'View StationeryGiftSet';
    }
}