<?php
declare(strict_types=1);
namespace App\Filament\Tenant\Resources\CollectibleStore\Pages;
use App\Filament\Tenant\Resources\CollectibleStoreResource;
use Filament\Resources\Pages\CreateRecord;
final class CreateRecordCollectibleStore extends CreateRecord {
    protected static string $resource = CollectibleStoreResource::class;
}
