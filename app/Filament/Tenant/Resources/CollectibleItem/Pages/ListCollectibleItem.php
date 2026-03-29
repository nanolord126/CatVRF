<?php
declare(strict_types=1);
namespace App\Filament\Tenant\Resources\CollectibleItem\Pages;
use App\Filament\Tenant\Resources\CollectibleItemResource;
use Filament\Resources\Pages\ListRecords;
final class ListRecordsCollectibleItem extends ListRecords {
    protected static string $resource = CollectibleItemResource::class;
}
