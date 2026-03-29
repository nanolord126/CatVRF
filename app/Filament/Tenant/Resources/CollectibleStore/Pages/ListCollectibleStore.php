<?php
declare(strict_types=1);
namespace App\Filament\Tenant\Resources\CollectibleStore\Pages;
use App\Filament\Tenant\Resources\CollectibleStoreResource;
use Filament\Resources\Pages\ListRecords;
final class ListRecordsCollectibleStore extends ListRecords {
    protected static string $resource = CollectibleStoreResource::class;
}
