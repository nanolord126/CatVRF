<?php
declare(strict_types=1);
namespace App\Filament\Tenant\Resources\CollectibleItem\Pages;
use App\Filament\Tenant\Resources\CollectibleItemResource;
use Filament\Resources\Pages\ViewRecord;
final class ViewRecordCollectibleItem extends ViewRecord {
    protected static string $resource = CollectibleItemResource::class;
}
