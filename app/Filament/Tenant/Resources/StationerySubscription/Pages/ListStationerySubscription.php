<?php
declare(strict_types=1);
namespace App\Filament\Tenant\Resources\StationerySubscription\Pages;
use App\Filament\Tenant\Resources\StationerySubscriptionResource;
use Filament\Resources\Pages\ListRecords;
final class ListRecordsStationerySubscription extends ListRecords {
    protected static string $resource = StationerySubscriptionResource::class;
}
