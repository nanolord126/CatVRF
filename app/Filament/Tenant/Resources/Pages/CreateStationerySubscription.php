<?php

declare(strict_types=1);

namespace App\Filament\Tenant\Resources\StationerySubscription\Pages;

use use App\Filament\Tenant\Resources\StationerySubscriptionResource;;
use Filament\Resources\Pages\CreateRecord;

final class CreateStationerySubscription extends CreateRecord
{
    protected static string $resource = StationerySubscriptionResource::class;

    public function getTitle(): string
    {
        return 'Create StationerySubscription';
    }
}