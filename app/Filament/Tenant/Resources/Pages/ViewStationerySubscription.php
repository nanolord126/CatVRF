<?php

declare(strict_types=1);

namespace App\Filament\Tenant\Resources\StationerySubscription\Pages;

use use App\Filament\Tenant\Resources\StationerySubscriptionResource;;
use Filament\Resources\Pages\ViewRecord;

final class ViewStationerySubscription extends ViewRecord
{
    protected static string $resource = StationerySubscriptionResource::class;

    public function getTitle(): string
    {
        return 'View StationerySubscription';
    }
}